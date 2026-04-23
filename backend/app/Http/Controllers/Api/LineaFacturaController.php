<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LineaFactura;
use App\Models\Factura;
use App\Models\Prestacion;
use Illuminate\Http\Request;

class LineaFacturaController extends Controller
{
    // GET /api/facturas/{num_fact}/lineas
    public function index($num_fact)
    {
        $factura = Factura::find($num_fact);
        if (!$factura) {
            return response()->json(['error' => 'Factura no encontrada'], 404);
        }

        $lineas = LineaFactura::where('num_fact', $num_fact)->get();

        return response()->json([
            'factura' => $factura,
            'lineas'  => $lineas
        ]);
    }

    // GET /api/facturas/{num_fact}/lineas/{num_linea}
    public function show($num_fact, $num_linea)
    {
        $linea = LineaFactura::where('num_fact', $num_fact)
                             ->where('num_linea', $num_linea)
                             ->first();

        if (!$linea) {
            return response()->json(['error' => 'Línea no encontrada'], 404);
        }

        return response()->json($linea);
    }

    // POST /api/facturas/{num_fact}/lineas
    public function store(Request $request, $num_fact)
    {
        $factura = Factura::find($num_fact);
        if (!$factura) {
            return response()->json(['error' => 'Factura no encontrada'], 404);
        }

        // Solo se pueden añadir líneas a facturas en borrador
        if ($factura->estado !== 'borrador') {
            return response()->json([
                'error'   => 'Factura cerrada',
                'message' => 'Solo se pueden añadir líneas a facturas en estado borrador.'
            ], 422);
        }

        $request->validate([
            'codigo_esp' => 'required|string|exists:especialidad,codigo_esp',
            'id_prest'   => 'required|integer',
            'cantidad'   => 'integer|min:1',
        ]);

        // Validar que la prestación existe con PK compuesta
        $prestacion = Prestacion::where('codigo_esp', $request->codigo_esp)
                                ->where('id_prest', $request->id_prest)
                                ->first();

        if (!$prestacion) {
            return response()->json(['error' => 'La prestación no existe'], 404);
        }

        // El trigger trg_lineafactura_num_linea_auto asigna num_linea
        // El trigger trg_lineafactura_precio_total_auto asigna precio y total
        $linea = LineaFactura::create([
            'num_fact'   => $num_fact,
            'codigo_esp' => $request->codigo_esp,
            'id_prest'   => $request->id_prest,
            'cantidad'   => $request->cantidad ?? 1,
            'precio'     => 0, // el trigger lo sobreescribe
            'total'      => 0  // el trigger lo sobreescribe
        ]);

        // Recargamos para ver precio y total reales calculados por el trigger
        $linea = LineaFactura::where('num_fact', $num_fact)
                             ->where('num_linea', $linea->num_linea)
                             ->first();

        return response()->json($linea, 201);
    }

    // PUT /api/facturas/{num_fact}/lineas/{num_linea}
    // Solo permite cambiar la cantidad
    public function update(Request $request, $num_fact, $num_linea)
    {
        $factura = Factura::find($num_fact);
        if (!$factura) {
            return response()->json(['error' => 'Factura no encontrada'], 404);
        }

        if ($factura->estado !== 'borrador') {
            return response()->json([
                'error'   => 'Factura cerrada',
                'message' => 'Solo se pueden editar líneas de facturas en estado borrador.'
            ], 422);
        }

        $linea = LineaFactura::where('num_fact', $num_fact)
                             ->where('num_linea', $num_linea)
                             ->first();

        if (!$linea) {
            return response()->json(['error' => 'Línea no encontrada'], 404);
        }

        $request->validate([
            'cantidad' => 'required|integer|min:1',
        ]);

        // Usamos where()->update() por la PK compuesta
        LineaFactura::where('num_fact', $num_fact)
                    ->where('num_linea', $num_linea)
                    ->update([
                        'cantidad' => $request->cantidad,
                        'total'    => $request->cantidad * $linea->precio
                    ]);

        $linea = LineaFactura::where('num_fact', $num_fact)
                             ->where('num_linea', $num_linea)
                             ->first();

        return response()->json($linea);
    }

    // DELETE /api/facturas/{num_fact}/lineas/{num_linea}
    public function destroy($num_fact, $num_linea)
    {
        $factura = Factura::find($num_fact);
        if (!$factura) {
            return response()->json(['error' => 'Factura no encontrada'], 404);
        }

        if ($factura->estado !== 'borrador') {
            return response()->json([
                'error'   => 'Factura cerrada',
                'message' => 'Solo se pueden eliminar líneas de facturas en estado borrador.'
            ], 422);
        }

        $linea = LineaFactura::where('num_fact', $num_fact)
                             ->where('num_linea', $num_linea)
                             ->first();

        if (!$linea) {
            return response()->json(['error' => 'Línea no encontrada'], 404);
        }

        LineaFactura::where('num_fact', $num_fact)
                    ->where('num_linea', $num_linea)
                    ->delete();

        return response()->json(['message' => 'Línea eliminada correctamente']);
    }
}