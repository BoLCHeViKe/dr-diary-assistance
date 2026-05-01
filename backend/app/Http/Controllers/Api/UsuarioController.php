<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Medico;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    // GET /api/usuarios          → solo activos
    // GET /api/usuarios?todos=1  → todos (activos + desactivados)
    public function index(Request $request)
    {
        $query = User::with(['rol', 'medico', 'admin']);

        if (!$request->boolean('todos')) {
            $query->where('activo', true);
        }

        return response()->json($query->get());
    }

    // GET /api/usuarios/{id}
    public function show($id)
    {
        $usuario = User::with(['rol', 'medico', 'admin'])->find($id);
        if (!$usuario) return response()->json(['error' => 'Usuario no encontrado'], 404);
        return response()->json($usuario);
    }

    // POST /api/usuarios
    public function store(Request $request)
    {
        $request->validate([
            'nombre'    => 'required|string|max:30',
            'apellido1' => 'required|string|max:30',
            'apellido2' => 'nullable|string|max:30',
            'email'     => 'required|email:rfc|unique:users,email',
            'password'  => 'required|min:8',
            'dni'       => [
                'required', 'string', 'unique:users,dni',
                'regex:/^[0-9]{8}[TRWAGMYFPDXBNJZSQVHLCKE]$/i'
            ],
            'fecha_nac' => 'sometimes|nullable|date',
            'telf'      => 'sometimes|nullable|string|max:13',
            'direccion' => 'sometimes|nullable|string|max:100',
            'id_rol'    => 'required|exists:rol,id',
            'num_col'   => 'required_if:id_rol,2|string|max:10',
            'num_auto'  => 'required_if:id_rol,1|string|max:10',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $user = User::create([
                    'nombre'    => $request->nombre,
                    'apellido1' => $request->apellido1,
                    'apellido2' => $request->apellido2,
                    'email'     => $request->email,
                    'password'  => $request->password,
                    'dni'       => $request->dni,
                    'fecha_nac' => $request->fecha_nac,
                    'telf'      => $request->telf,
                    'direccion' => $request->direccion,
                    'id_rol'    => $request->id_rol,
                    'activo'    => true,
                ]);

                if ($user->id_rol == 1) {
                    Admin::create(['id' => $user->id, 'num_auto' => $request->num_auto]);
                } else {
                    $rol = \App\Models\Rol::find($user->id_rol);
                    if ($rol && $rol->perm_agenda_disponible) {
                        Medico::create([
                            'id'      => $user->id,
                            'num_col' => $user->id_rol == 2 ? $request->num_col : null,
                        ]);
                    }
                }

                return response()->json($user->load(['rol', 'medico', 'admin']), 201);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al crear el usuario', 'message' => $e->getMessage()], 500);
        }
    }

    // PUT /api/usuarios/{id}
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['error' => 'Usuario no encontrado'], 404);

        // No se puede desactivar al admin principal
        if ($id == 1 && $request->has('activo') && !$request->boolean('activo')) {
            return response()->json(['error' => 'El administrador principal no puede desactivarse.'], 403);
        }

        $request->validate([
            'nombre'    => 'sometimes|string|max:30',
            'apellido1' => 'sometimes|string|max:30',
            'apellido2' => 'sometimes|nullable|string|max:30',
            'email'     => ['sometimes', 'email:rfc', Rule::unique('users', 'email')->ignore($id)],
            'password'  => 'sometimes|min:8',
            'dni'       => ['sometimes', 'string', Rule::unique('users', 'dni')->ignore($id), 'regex:/^[0-9]{8}[TRWAGMYFPDXBNJZSQVHLCKE]$/i'],
            'fecha_nac' => 'sometimes|nullable|date',
            'telf'      => 'sometimes|nullable|string|max:13',
            'direccion' => 'sometimes|nullable|string|max:100',
            'id_rol'    => 'sometimes|exists:rol,id',
            'activo'    => 'sometimes|boolean',
            'num_col'   => 'sometimes|string|max:10',
            'num_auto'  => 'sometimes|string|max:10',
        ]);

        try {
            DB::transaction(function () use ($request, $user) {
                $user->update($request->only([
                    'nombre', 'apellido1', 'apellido2', 'email',
                    'dni', 'fecha_nac', 'telf', 'direccion', 'id_rol', 'activo',
                ]));

                if ($request->has('password')) {
                    $user->password = $request->password;
                    $user->save();
                }

                if ($user->id_rol == 1 && $request->has('num_auto')) {
                    Admin::where('id', $user->id)->update(['num_auto' => $request->num_auto]);
                } else {
                    $rol = \App\Models\Rol::find($user->id_rol);
                    if ($rol && $rol->perm_agenda_disponible) {
                        if (!Medico::find($user->id)) {
                            Medico::create(['id' => $user->id, 'num_col' => null]);
                        } elseif ($user->id_rol == 2 && $request->has('num_col')) {
                            Medico::where('id', $user->id)->update(['num_col' => $request->num_col]);
                        }
                    }
                }
            });

            return response()->json($user->fresh()->load(['rol', 'medico', 'admin']));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar el usuario', 'message' => $e->getMessage()], 500);
        }
    }

    // DELETE /api/usuarios/{id}  → soft-disable (activo = false)
    public function destroy($id)
    {
        if ($id == 1) {
            return response()->json(['error' => 'El administrador principal no puede desactivarse.'], 403);
        }

        $user = User::find($id);
        if (!$user) return response()->json(['error' => 'Usuario no encontrado'], 404);

        $user->update(['activo' => false]);
        // Invalidar todos sus tokens activos
        $user->tokens()->delete();

        return response()->json(['message' => 'Usuario desactivado correctamente']);
    }
}
