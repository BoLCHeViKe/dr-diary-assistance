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
    public function index()
    {
        $facturas = Factura::with(['lineas', 'paciente'])->get();
        return response()->json($facturas);
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
                $factura = Factura::create([
                    'id_paciente' => $request->id_paciente,
                    'estado'      => 'borrador',
                    'fecha'       => '1900-01-01',
                    'fact_ref'    => $request->fact_ref ?? null
                ]);

                foreach ($request->lineas as $lineaData) {
                    LineaFactura::create([
                        'num_fact'   => $factura->num_fact,
                        'cantidad'   => $lineaData['cantidad'] ?? 1,
                        'codigo_esp' => $lineaData['codigo_esp'],
                        'id_prest'   => $lineaData['id_prest'],
                        'precio'     => 0,
                        'total'      => 0
                    ]);
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
    public function crearAbono($num_fact)
    {
        $facturaOriginal = Factura::with('lineas')->find($num_fact);

        if (!$facturaOriginal) {
            return response()->json(['error' => 'Factura no encontrada'], 404);
        }

        if ($facturaOriginal->estado !== 'emitida') {
            return response()->json([
                'error' => 'Solo se pueden abonar facturas emitidas'
            ], 422);
        }

        try {
            return DB::transaction(function () use ($facturaOriginal) {
                $abono = Factura::create([
                    'id_paciente' => $facturaOriginal->id_paciente,
                    'estado'      => 'abono',
                    'fecha'       => '1900-01-01',
                    'fact_ref'    => $facturaOriginal->num_fact
                ]);

                foreach ($facturaOriginal->lineas as $linea) {
                    LineaFactura::create([
                        'num_fact'   => $abono->num_fact,
                        'cantidad'   => $linea->cantidad,
                        'codigo_esp' => $linea->codigo_esp,
                        'id_prest'   => $linea->id_prest,
                        'precio'     => 0,
                        'total'      => 0
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