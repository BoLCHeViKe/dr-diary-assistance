<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RolController extends Controller
{
    private const PERM_FIELDS = [
        'perm_agenda', 'perm_hc', 'perm_multi_agenda', 'perm_facturacion',
        'perm_estadisticas', 'perm_gest_roles', 'perm_gest_usuarios',
        'perm_gest_prestaciones', 'perm_gest_especialidades',
    ];

    private function permValidation(): array
    {
        return array_fill_keys(self::PERM_FIELDS, 'sometimes|boolean');
    }

    // GET /api/roles
    public function index()
    {
        return response()->json(Rol::withCount('usuarios')->get());
    }

    // GET /api/roles/{id}
    public function show($id)
    {
        $rol = Rol::withCount('usuarios')->find($id);
        if (!$rol) return response()->json(['error' => 'Rol no encontrado'], 404);
        return response()->json($rol);
    }

    // POST /api/roles
    public function store(Request $request)
    {
        $request->validate(array_merge(
            ['tipo' => 'required|string|max:20|unique:rol,tipo'],
            $this->permValidation()
        ));

        $rol = Rol::create($request->only(array_merge(['tipo'], self::PERM_FIELDS)));
        return response()->json($rol, 201);
    }

    // PUT /api/roles/{id}
    public function update(Request $request, $id)
    {
        // ADMIN: completamente inamovible
        if ($id == 1) {
            return response()->json(['error' => 'El rol ADMIN no puede ser modificado.'], 403);
        }

        $rol = Rol::find($id);
        if (!$rol) return response()->json(['error' => 'Rol no encontrado'], 404);

        $request->validate(array_merge(
            ['tipo' => ['sometimes', 'string', 'max:20', Rule::unique('rol', 'tipo')->ignore($id)]],
            $this->permValidation()
        ));

        // MÉDICO: los permisos mínimos no pueden reducirse a false
        if ($id == 2) {
            foreach (Rol::MEDICO_MIN_PERMS as $perm => $minVal) {
                if ($request->has($perm) && !$request->boolean($perm)) {
                    return response()->json([
                        'error' => "El permiso '{$perm}' es mínimo para el rol MÉDICO y no puede desactivarse."
                    ], 422);
                }
            }
        }

        $rol->fill($request->only(array_merge(['tipo'], self::PERM_FIELDS)));
        $rol->save();

        return response()->json($rol);
    }

    // DELETE /api/roles/{id}
    public function destroy($id)
    {
        if (in_array($id, [1, 2])) {
            return response()->json([
                'error' => 'Los roles ADMIN y MÉDICO no pueden eliminarse del sistema.'
            ], 403);
        }

        $rol = Rol::find($id);
        if (!$rol) return response()->json(['error' => 'Rol no encontrado'], 404);

        if ($rol->usuarios()->count() > 0) {
            return response()->json([
                'error'   => 'No se puede eliminar',
                'message' => 'Existen usuarios asociados a este rol. Cámbialo primero.'
            ], 422);
        }

        $rol->delete();
        return response()->json(['message' => 'Rol eliminado correctamente']);
    }
}
