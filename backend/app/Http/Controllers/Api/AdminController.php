<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // GET /api/admins
    public function index()
    {
        return response()->json(Admin::with('usuario')->get());
    }

    // GET /api/admins/{id}
    public function show($id)
    {
        $admin = Admin::with('usuario')->find($id);

        if (!$admin) {
            return response()->json(['error' => 'Admin no encontrado'], 404);
        }

        return response()->json($admin);
    }

    // PUT /api/admins/{id}
    // Solo actualiza num_auto, crear/eliminar admin va por UsuarioController
    public function update(Request $request, $id)
    {
        $admin = Admin::find($id);

        if (!$admin) {
            return response()->json(['error' => 'Admin no encontrado'], 404);
        }

        $request->validate([
            'num_auto' => 'required|string|max:10|unique:admins,num_auto,' . $id . ',id',
        ]);

        if ($request->has('num_auto')) {
            $admin->num_auto = trim($request->num_auto);
            if ($admin->isDirty()) {
                $admin->save();
            }
        }

        return response()->json($admin->load('usuario'));
    }
}