<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use App\Models\LineaFactura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FacturaController extends Controller
{
    // GET /api/facturas
    public function index(Request $request)
    {
        $query = Factura::with(['lineas', 'paciente', 'abonos.lineas'])
                        ->orderBy('num_fact', 'desc');

        if ($request->filled('desde_fecha')) {
            $query->where('fecha', '>=', $request->desde_fecha);
        }
        if ($request->filled('hasta_fecha')) {
            $query->where('fecha', '<=', $request->hasta_fecha);
        }
        if ($request->filled('id_paciente')) {
            $query->where('id_paciente', $request->id_paciente);
        }
        if ($request->filled('estados')) {
            $estados = is_array($request->estados)
                ? $request->estados
                : explode(',', $request->estados);
            $query->whereIn('estado', $estados);
        }

        $all = $query->get();

        // Compute totals + per-factura importe/abonado over the ENTIRE filtered result set
        $sumEmitidas = 0.0;
        $sumAnuladas = 0.0;
        $sumAbonos   = 0.0;
        foreach ($all as $f) {
            $importe = (float) $f->lineas->sum('total');
            $abonado = (float) $f->abonos->sum(fn($a) => $a->lineas->sum('total'));
            $f->importe_calc = round($importe, 2);
            $f->abonado_calc = round($abonado, 2);

            if ($f->estado === 'emitida')  $sumEmitidas += $importe;
            if ($f->estado === 'anulada')  $sumAnuladas += $importe;
            if ($f->estado === 'abono')    $sumAbonos   += $importe;
        }

        // Paginate in PHP (avoids a second DB query)
        $perPage = 10;
        $page    = max(1, (int) ($request->page ?? 1));
        $paged   = $all->forPage($page, $perPage)->values();

        return response()->json([
            'facturas'    => $paged,
            'total_items' => $all->count(),
            'totales'     => [
                'emitidas'        => round($sumEmitidas, 2),
                'anuladas_abonos' => round($sumAnuladas + $sumAbonos, 2),
                'neto'            => round($sumEmitidas - $sumAnuladas - $sumAbonos, 2),
            ],
        ]);
    }

    // GET /api/facturas/{num_fact}
    public function show($num_fact)
    {
        $factura = Factura::with(['lineas', 'paciente'])->find($num_fact);

        if (!$factura) {
            return response()->json(['error' => 'Factura no encontrada'], 404);
        }

        return response()->json($factura);
    }

    // POST /api/facturas
    public function store(Request $request)
    {
        $request->validate([
            'id_paciente'         => 'required|exists:paciente,id_paciente',
            'lineas'              => 'required|array|min:1',
            'lineas.*.codigo_esp' => 'required|string|exists:especialidad,codigo_esp',
            'lineas.*.id_prest'   => 'required|integer',
            'lineas.*.cantidad'   => 'integer|min:1'
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // Create in borrador first — DB trigger only allows line insertion on borrador
                $estadoFinal = $request->estado ?? 'emitida';
                $factura = Factura::create([
                    'id_paciente' => $request->id_paciente,
                    'estado'      => 'borrador',
                    'fecha'       => $request->fecha ?? now()->toDateString(),
                    'fact_ref'    => $request->fact_ref ?? null
                ]);

                foreach ($request->lineas as $lineaData) {
                    LineaFactura::create([
                        'num_fact'   => $factura->num_fact,
                        'cantidad'   => (int)($lineaData['cantidad'] ?? 1),
                        'codigo_esp' => $lineaData['codigo_esp'],
                        'id_prest'   => $lineaData['id_prest'],
                        'precio'     => 0,
                        'total'      => 0,
                    ]);
                }

                // Transition to requested estado
                if ($estadoFinal !== 'borrador') {
                    $factura->estado = $estadoFinal;
                    $factura->save();
                }

                return response()->json($factura->load(['lineas', 'paciente']), 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Error al crear la factura',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // PUT /api/facturas/{num_fact}
    public function update(Request $request, $num_fact)
    {
        $factura = Factura::find($num_fact);

        if (!$factura) {
            return response()->json(['error' => 'Factura no encontrada'], 404);
        }

        $request->validate([
            'estado' => 'sometimes|in:borrador,emitida,anulada,abono',
            'fecha'  => 'sometimes|date',
        ]);

        // Protección: solo se puede cambiar la fecha en borrador
        if ($request->has('fecha') && $factura->estado !== 'borrador') {
            return response()->json([
                'error'   => 'Fecha bloqueada',
                'message' => 'Solo se puede cambiar la fecha en facturas en estado borrador.'
            ], 422);
        }

        try {
            if ($request->has('fecha'))  $factura->fecha  = $request->fecha;
            if ($request->has('estado')) $factura->estado = $request->estado;

            $factura->save();

            return response()->json($factura->load(['lineas', 'paciente']));
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'No se puede cambiar el estado',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    // DELETE /api/facturas/{num_fact}
    public function destroy($num_fact)
    {
        $factura = Factura::find($num_fact);

        if (!$factura) {
            return response()->json(['error' => 'Factura no encontrada'], 404);
        }

        if ($factura->estado !== 'borrador') {
            return response()->json([
                'error' => 'Solo se pueden eliminar facturas en estado borrador'
            ], 422);
        }

        try {
            DB::transaction(function () use ($factura) {
                $factura->delete();
            });

            return response()->json(['message' => 'Factura eliminada correctamente']);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Error al eliminar la factura',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // POST /api/facturas/{num_fact}/abono
    // Ahora acepta un body opcional para abonos parciales
    public function crearAbono(Request $request, $num_fact)
    {
        $facturaOriginal = Factura::with(['lineas', 'abonos.lineas'])->find($num_fact);

        if (!$facturaOriginal) {
            return response()->json(['error' => 'Factura no encontrada'], 404);
        }

        if ($facturaOriginal->estado !== 'emitida') {
            return response()->json([
                'error' => 'Solo se pueden abonar facturas emitidas'
            ], 422);
        }

        // 1. Calcular cuánto queda disponible para abonar
        $totalOriginal = $facturaOriginal->lineas->sum('total');
        $totalYaAbonado = $facturaOriginal->abonos->reduce(function ($carry, $abono) {
            return $carry + $abono->lineas->sum('total');
        }, 0);
        
        $disponibleParaAbonar = round($totalOriginal - $totalYaAbonado, 2);

        if ($disponibleParaAbonar <= 0) {
            return response()->json(['error' => 'Esta factura ya ha sido abonada totalmente'], 422);
        }

        // 2. Determinar qué líneas vamos a abonar
        // Si el request trae 'lineas', es un abono parcial. Si no, es total (de lo que quede).
        $lineasAbonar = [];
        if ($request->has('lineas')) {
            $request->validate([
                'lineas' => 'required|array|min:1',
                'lineas.*.codigo_esp' => 'required|string',
                'lineas.*.id_prest'   => 'required|integer',
                'lineas.*.cantidad'   => 'required|integer|min:1',
                'lineas.*.precio'     => 'required|numeric|min:0'
            ]);
            $lineasAbonar = $request->lineas;
        } else {
            // Abono total: usamos las líneas de la original
            foreach ($facturaOriginal->lineas as $l) {
                $lineasAbonar[] = [
                    'codigo_esp' => $l->codigo_esp,
                    'id_prest'   => $l->id_prest,
                    'cantidad'   => $l->cantidad,
                    'precio'     => $l->precio
                ];
            }
        }

        // 3. Validar que el nuevo abono no supere el disponible
        $totalNuevoAbono = collect($lineasAbonar)->reduce(fn($c, $l) => $c + ($l['cantidad'] * $l['precio']), 0);
        
        if (round($totalNuevoAbono, 2) > $disponibleParaAbonar) {
            return response()->json([
                'error' => 'Importe excedido',
                'message' => "No puedes abonar {$totalNuevoAbono}€. El máximo pendiente es {$disponibleParaAbonar}€."
            ], 422);
        }

        try {
            return DB::transaction(function () use ($facturaOriginal, $lineasAbonar) {
                $abono = Factura::create([
                    'id_paciente' => $facturaOriginal->id_paciente,
                    'estado'      => 'abono',
                    'fecha'       => now()->toDateString(),
                    'fact_ref'    => $facturaOriginal->num_fact
                ]);

                foreach ($lineasAbonar as $l) {
                    LineaFactura::create([
                        'num_fact'   => $abono->num_fact,
                        'codigo_esp' => $l['codigo_esp'],
                        'id_prest'   => $l['id_prest'],
                        'cantidad'   => $l['cantidad'],
                        'precio'     => $l['precio'],
                        'total'      => $l['cantidad'] * $l['precio']
                    ]);
                }

                return response()->json($abono->load(['lineas', 'paciente']), 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Error al crear el abono',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}