<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Especialidad;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EspecialidadController extends Controller
{
    // GET /api/especialidades           → solo activas (uso normal)
    // GET /api/especialidades?gestion=1 → todas (gestión)
    public function index(Request $request)
    {
        $query = Especialidad::with('prestaciones');

        if (!$request->boolean('gestion')) {
            $query->where('activo', true);
        }

        return response()->json($query->orderBy('nombre')->get());
    }

    // GET /api/especialidades/{codigo_esp}
    public function show($codigo_esp)
    {
        $especialidad = Especialidad::with('prestaciones')->find($codigo_esp);
        if (!$especialidad) return response()->json(['error' => 'Especialidad no encontrada'], 404);
        return response()->json($especialidad);
    }

    // POST /api/especialidades
    public function store(Request $request)
    {
        $request->validate([
            'codigo_esp' => 'required|string|max:4|unique:especialidad,codigo_esp',
            'nombre'     => 'required|string|max:30|unique:especialidad,nombre',
        ]);

        $especialidad = Especialidad::create([
            'codigo_esp' => strtoupper(trim($request->codigo_esp)),
            'nombre'     => trim($request->nombre),
            'activo'     => true,
        ]);

        return response()->json($especialidad->load('prestaciones'), 201);
    }

    // PUT /api/especialidades/{codigo_esp}
    public function update(Request $request, $codigo_esp)
    {
        $especialidad = Especialidad::find($codigo_esp);
        if (!$especialidad) return response()->json(['error' => 'Especialidad no encontrada'], 404);

        $request->validate([
            'nombre' => ['sometimes', 'string', 'max:30', Rule::unique('especialidad', 'nombre')->ignore($codigo_esp, 'codigo_esp')],
            'activo' => 'sometimes|boolean',
        ]);

        if ($request->has('nombre'))  $especialidad->nombre = trim($request->nombre);
        if ($request->has('activo'))  $especialidad->activo = $request->boolean('activo');
        $especialidad->save();

        return response()->json($especialidad->load('prestaciones'));
    }

    // PATCH /api/especialidades/{codigo_esp}/toggle
    public function toggleActivo($codigo_esp)
    {
        $especialidad = Especialidad::find($codigo_esp);
        if (!$especialidad) return response()->json(['error' => 'Especialidad no encontrada'], 404);

        $especialidad->activo = !$especialidad->activo;
        $especialidad->save();

        $estado = $especialidad->activo ? 'activada' : 'desactivada';
        return response()->json(['message' => "Especialidad {$estado} correctamente", 'especialidad' => $especialidad]);
    }

    // DELETE /api/especialidades/{codigo_esp}  (solo si no tiene prestaciones)
    public function destroy($codigo_esp)
    {
        $especialidad = Especialidad::find($codigo_esp);
        if (!$especialidad) return response()->json(['error' => 'Especialidad no encontrada'], 404);

        if ($especialidad->prestaciones()->count() > 0) {
            return response()->json([
                'error'   => 'No se puede eliminar',
                'message' => 'La especialidad tiene prestaciones asociadas. Desactívala o elimínalas primero.'
            ], 422);
        }

        $especialidad->delete();
        return response()->json(['message' => 'Especialidad eliminada correctamente']);
    }
}
