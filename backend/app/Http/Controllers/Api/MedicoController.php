<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medico;
use Illuminate\Http\Request;

class MedicoController extends Controller
{
    // GET /api/medicos
    // Devuelve TODOS los usuarios activos cuyo rol tenga perm_agenda_disponible=true.
    // Si un usuario aún no tiene registro en medico (rol nuevo, por ejemplo), se crea
    // automáticamente en este momento para que el sistema de agendas pueda funcionar.
    public function index()
    {
        $users = \App\Models\User::with(['medico.agendas'])
            ->where('activo', true)
            ->whereHas('rol', fn($q) => $q->where('perm_agenda_disponible', true))
            ->get();

        $result = $users->map(function ($user) {
            if ($user->medico) {
                $medico = $user->medico; // ya cargado con agendas eager-loaded
            } else {
                // Rol tiene perm_agenda_disponible pero aún no hay registro medico:
                // se crea automáticamente (ocurre cuando se asigna un rol nuevo con el permiso)
                $medico = Medico::firstOrCreate(
                    ['id' => $user->id],
                    ['num_col' => null]
                );
                $medico->setRelation('agendas', collect());
            }

            return [
                'id'      => $medico->id,
                'num_col' => $medico->num_col,
                'usuario' => [
                    'id'        => $user->id,
                    'nombre'    => $user->nombre,
                    'apellido1' => $user->apellido1,
                    'apellido2' => $user->apellido2,
                ],
                'agendas' => $medico->agendas ?? [],
            ];
        });

        return response()->json($result->values());
    }

    // GET /api/medicos/{id}
    public function show($id)
    {
        $medico = Medico::with(['usuario', 'agendas'])->find($id);
        if (!$medico) return response()->json(['error' => 'Médico no encontrado'], 404);
        return response()->json($medico);
    }

    // PUT /api/medicos/{id}
    public function update(Request $request, $id)
    {
        $medico = Medico::find($id);
        if (!$medico) return response()->json(['error' => 'Médico no encontrado'], 404);

        $request->validate([
            'num_col' => 'required|string|max:10|unique:medico,num_col,' . $id . ',id',
        ]);

        $medico->num_col = trim($request->num_col);
        if ($medico->isDirty()) $medico->save();

        return response()->json($medico->load(['usuario', 'agendas']));
    }
}
