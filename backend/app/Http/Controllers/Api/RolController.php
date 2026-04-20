<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RolController extends Controller
{
    // GET /api/roles
    public function index()
    {
        $roles = Rol::with('usuarios')->get();
        return response()->json($roles);
    }

    // GET /api/roles/{id}
    public function show($id)
    {
        $rol = Rol::with('usuarios')->find($id);

        if (!$rol) {
            return response()->json(['error' => 'Rol no encontrado'], 404);
        }

        return response()->json($rol);
    }

    // POST /api/roles
    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|string|max:20|unique:rol,tipo'
        ]);

        $rol = Rol::create(['tipo' => $request->tipo]);

        return response()->json($rol, 201);
    }

    // PUT /api/roles/{id}
    public function update(Request $request, $id)
    {
        // Bloqueo de roles vitales
        if (in_array($id, [1, 2])) {
            return response()->json([
                'error'   => 'Acción prohibida',
                'message' => 'Los roles básicos no pueden ser modificados.'
            ], 403);
        }

        $rol = Rol::find($id);

        if (!$rol) {
            return response()->json(['error' => 'Rol no encontrado'], 404);
        }

        $request->validate([
                'tipo' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('rol', 'tipo')->ignore($id)
                ]
            ]);

        $rol->tipo = $request->tipo;
        $rol->save();

        return response()->json($rol);
    }

    // DELETE /api/roles/{id}
    public function destroy($id)
    {
        // Bloqueo de roles vitales
        if (in_array($id, [1, 2])) {
            return response()->json([
                'error'   => 'Acción prohibida',
                'message' => 'Los roles básicos (Admin y Médico) no pueden ser eliminados del sistema.'
            ], 403);
        }

        $rol = Rol::find($id);

        if (!$rol) {
            return response()->json(['error' => 'Rol no encontrado'], 404);
        }
        // Capa 2: Bloqueo si tiene usuarios asociados
        if ($rol->usuarios()->count() > 0) {
            return response()->json([
                'error'   => 'No se puede eliminar',
                'message' => 'Existen usuarios asociados a este rol.'
            ], 422);
        }
        $rol->delete();

        return response()->json(['message' => 'Rol eliminado correctamente']);
    }
}