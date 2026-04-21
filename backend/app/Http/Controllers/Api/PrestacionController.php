<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prestacion;
use App\Models\Cita;
use App\Models\LineaFactura;
use Illuminate\Http\Request;

class PrestacionController extends Controller
{
    // GET /api/prestaciones
    public function index()
    {
        $prestaciones = Prestacion::with('especialidad')->get();
        return response()->json($prestaciones);
    }

    // GET /api/prestaciones/{codigo_esp}/{id_prest}
    public function show($codigo_esp, $id_prest)
    {
        $prestacion = Prestacion::with('especialidad')
                                ->where('codigo_esp', $codigo_esp)
                                ->where('id_prest', $id_prest)
                                ->first();

        if (!$prestacion) {
            return response()->json(['error' => 'Prestación no encontrada'], 404);
        }

        return response()->json($prestacion);
    }

    // POST /api/prestaciones
    public function store(Request $request)
    {
        $request->validate([
            'codigo_esp'  => 'required|string|max:4|exists:especialidad,codigo_esp',
            'nombre'      => 'required|string|max:30',
            'descripcion' => 'nullable|string|max:80',
            'precio'      => 'required|numeric|min:0',
        ]);

        // Calculamos el siguiente id_prest para esa especialidad
        $nextIdPrest = Prestacion::where('codigo_esp', $request->codigo_esp)
                                 ->max('id_prest') + 1;

        $prestacion = Prestacion::create([
            'codigo_esp'  => $request->codigo_esp,
            'id_prest'    => $nextIdPrest,
            'nombre'      => trim($request->nombre),
            'descripcion' => trim($request->descripcion ?? ''),
            'precio'      => $request->precio,
        ]);

        return response()->json($prestacion->load('especialidad'), 201);
    }

    // PUT /api/prestaciones/{codigo_esp}/{id_prest}
    public function update(Request $request, $codigo_esp, $id_prest)
    {
        $prestacion = Prestacion::where('codigo_esp', $codigo_esp)
                                ->where('id_prest', $id_prest)
                                ->first();

        if (!$prestacion) {
            return response()->json(['error' => 'Prestación no encontrada'], 404);
        }

        $request->validate([
            'nombre'      => 'sometimes|string|max:30',
            'descripcion' => 'sometimes|nullable|string|max:80',
            'precio'      => 'sometimes|numeric|min:0',
            // codigo_esp e id_prest no se pueden cambiar, son la PK
        ]);

        if ($request->has('nombre')) {
            $prestacion->nombre = trim($request->nombre);
        }

        if ($request->has('descripcion')) {
            $prestacion->descripcion = trim($request->descripcion ?? '');
        }

        if ($request->has('precio')) {
            $prestacion->precio = $request->precio;
        }

        $prestacion->save();

        return response()->json($prestacion->load('especialidad'));
    }

    // DELETE /api/prestaciones/{codigo_esp}/{id_prest}
    public function destroy($codigo_esp, $id_prest)
    {
        $prestacion = Prestacion::where('codigo_esp', $codigo_esp)
                                ->where('id_prest', $id_prest)
                                ->first();

        if (!$prestacion) {
            return response()->json(['error' => 'Prestación no encontrada'], 404);
        }

        // Protección: no eliminar si tiene citas asociadas
        $tieneCitas = Cita::where('codigo_esp', $codigo_esp)
                          ->where('id_prest', $id_prest)
                          ->count();

        if ($tieneCitas > 0) {
            return response()->json([
                'error'   => 'No se puede eliminar',
                'message' => 'La prestación tiene citas asociadas.'
            ], 422);
        }

        // Protección: no eliminar si tiene líneas de factura asociadas
        $tieneLineas = LineaFactura::where('codigo_esp', $codigo_esp)
                                   ->where('id_prest', $id_prest)
                                   ->count();

        if ($tieneLineas > 0) {
            return response()->json([
                'error'   => 'No se puede eliminar',
                'message' => 'La prestación tiene facturas asociadas.'
            ], 422);
        }

        $prestacion->delete();

        return response()->json(['message' => 'Prestación eliminada correctamente']);
    }
}