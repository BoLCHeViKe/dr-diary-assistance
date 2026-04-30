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
        if ($request->filled('especialidades')) {
            $especialidades = is_array($request->especialidades)
                ? $request->especialidades
                : explode(',', $request->especialidades);
            if (in_array('__disabled__', $especialidades)) {
                $especialidades = array_values(array_filter($especialidades, fn($e) => $e !== '__disabled__'));
                $disabledCodes  = \App\Models\Especialidad::where('activo', false)->pluck('codigo_esp')->toArray();
                $especialidades = array_merge($especialidades, $disabledCodes);
            }
            if (!empty($especialidades)) {
                $query->whereHas('lineas', fn($q) => $q->whereIn('codigo_esp', $especialidades));
            }
        }

        $all = $query->get();

        // Compute totals + per-factura importe/abonado over the ENTIRE filtered result set
        // facturado = emitidas + anuladas (ambas representan facturación real emitida)
        // abonos    = solo facturas de tipo abono (notas de crédito)
        // neto      = facturado - abonos
        $sumFacturado = 0.0;
        $sumAbonos    = 0.0;
        foreach ($all as $f) {
            $importe = (float) $f->lineas->sum('total');
            $abonado = (float) $f->abonos->sum(fn($a) => $a->lineas->sum('total'));
            $f->importe_calc = round($importe, 2);
            $f->abonado_calc = round($abonado, 2);

            if ($f->estado === 'emitida' || $f->estado === 'anulada') $sumFacturado += $importe;
            if ($f->estado === 'abono')                                $sumAbonos    += $importe;
        }

        // Paginate in PHP (avoids a second DB query)
        $perPage = 10;
        $page    = max(1, (int) ($request->page ?? 1));
        $paged   = $all->forPage($page, $perPage)->values();

        return response()->json([
            'facturas'    => $paged,
            'total_items' => $all->count(),
            'totales'     => [
                'facturado' => round($sumFacturado, 2),
                'abonos'    => round($sumAbonos, 2),
                'neto'      => round($sumFacturado - $sumAbonos, 2),
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
    // Body: { "importe": float }  → abono parcial por ese importe
    // Body: {}                    → abono total (cubre todo el importe restante)
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

        $totalOriginal = round((float) $facturaOriginal->lineas->sum('total'), 2);
        $totalAbonado  = round((float) $facturaOriginal->abonos->sum(fn($a) => $a->lineas->sum('total')), 2);
        $maxAbono      = round($totalOriginal - $totalAbonado, 2);

        if ($maxAbono <= 0) {
            return response()->json(['error' => 'La factura ya está completamente abonada'], 422);
        }

        // Si no se pasa importe, abono total (lo que quede)
        $importeEfectivo = $request->has('importe')
            ? round((float) $request->importe, 2)
            : $maxAbono;

        if ($importeEfectivo <= 0 || $importeEfectivo > $maxAbono) {
            return response()->json([
                'error'   => 'Importe inválido',
                'message' => "El importe debe estar entre 0.01 y {$maxAbono} €"
            ], 422);
        }

        $primeraLinea = $facturaOriginal->lineas->first();
        if (!$primeraLinea) {
            return response()->json(['error' => 'La factura no tiene líneas'], 422);
        }

        try {
            return DB::transaction(function () use ($facturaOriginal, $importeEfectivo, $maxAbono, $primeraLinea) {
                // Create as 'borrador' so the DB security trigger allows both
                // INSERT and UPDATE on lineafactura (trigger blocks UPDATE on non-borrador)
                $abono = Factura::create([
                    'id_paciente' => $facturaOriginal->id_paciente,
                    'estado'      => 'borrador',
                    'fecha'       => now()->toDateString(),
                    'fact_ref'    => $facturaOriginal->num_fact,
                ]);

                // INSERT line — trg_lineafactura_precio_total_auto fills precio/total from prestacion
                LineaFactura::create([
                    'num_fact'   => $abono->num_fact,
                    'cantidad'   => 1,
                    'codigo_esp' => $primeraLinea->codigo_esp,
                    'id_prest'   => $primeraLinea->id_prest,
                    'precio'     => 0,
                    'total'      => 0,
                ]);

                // Override trigger-set values with the actual abono amount (allowed on borrador)
                DB::table('lineafactura')
                    ->where('num_fact', $abono->num_fact)
                    ->update(['precio' => $importeEfectivo, 'total' => $importeEfectivo]);

                // Now transition to 'abono' (no more line operations after this)
                $abono->estado = 'abono';
                $abono->save();

                // Full abono: mark original as anulada + free citas back to atendido
                if ($importeEfectivo >= $maxAbono) {
                    $facturaOriginal->estado = 'anulada';
                    $facturaOriginal->save();
                    DB::table('cita')
                        ->where('num_fact', $facturaOriginal->num_fact)
                        ->update(['estado' => 'atendido', 'num_fact' => null]);
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