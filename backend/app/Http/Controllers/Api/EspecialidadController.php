<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Especialidad;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EspecialidadController extends Controller
{
    // GET /api/especialidades
    public function index()
    {
        $especialidades = Especialidad::with('prestaciones')->get();
        return response()->json($especialidades);
    }

    // GET /api/especialidades/{codigo_esp}
    public function show($codigo_esp)
    {
        $especialidad = Especialidad::with('prestaciones')->find($codigo_esp);

        if (!$especialidad) {
            return response()->json(['error' => 'Especialidad no encontrada'], 404);
        }

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
        ]);

        return response()->json($especialidad->load('prestaciones'), 201);
    }

    // PUT /api/especialidades/{codigo_esp}
    public function update(Request $request, $codigo_esp)
    {
        $especialidad = Especialidad::find($codigo_esp);

        if (!$especialidad) {
            return response()->json(['error' => 'Especialidad no encontrada'], 404);
        }

        $request->validate([
            'nombre' => [
                'sometimes',
                'string',
                'max:30',
                Rule::unique('especialidad', 'nombre')->ignore($codigo_esp, 'codigo_esp')
            ],
        ]);

        // El codigo_esp no se puede cambiar, es la PK
        if ($request->has('nombre')) {
            $especialidad->nombre = trim($request->nombre);
            $especialidad->save();
            }

        return response()->json($especialidad->load('prestaciones'));
    }

    // DELETE /api/especialidades/{codigo_esp}
    public function destroy($codigo_esp)
    {
        $especialidad = Especialidad::find($codigo_esp);

        if (!$especialidad) {
            return response()->json(['error' => 'Especialidad no encontrada'], 404);
        }

        // Protección: no eliminar si tiene prestaciones asociadas
        if ($especialidad->prestaciones()->count() > 0) {
            return response()->json([
                'error'   => 'No se puede eliminar',
                'message' => 'La especialidad tiene prestaciones asociadas. Elimínalas primero.'
            ], 422);
        }

        $especialidad->delete();

        return response()->json(['message' => 'Especialidad eliminada correctamente']);
    }
}