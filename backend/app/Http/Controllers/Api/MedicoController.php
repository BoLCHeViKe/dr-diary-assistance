<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medico;
use App\Models\User;
use Illuminate\Http\Request;

class MedicoController extends Controller
{
    // GET /api/medicos
    public function index()
    {
        $medicos = Medico::with(['usuario', 'agendas'])->get();
        return response()->json($medicos);
    }

    // GET /api/medicos/{id}
    public function show($id)
    {
        $medico = Medico::with(['usuario', 'agendas'])->find($id);

        if (!$medico) {
            return response()->json(['error' => 'Médico no encontrado'], 404);
        }

        return response()->json($medico);
    }

    // PUT /api/medicos/{id}
    // Solo actualizamos num_col, el resto va por UsuarioController
    public function update(Request $request, $id)
    {
        $medico = Medico::find($id);

        if (!$medico) {
            return response()->json(['error' => 'Médico no encontrado'], 404);
        }

        $request->validate([
            'num_col' => 'required|string|max:10|unique:medico,num_col,' . $id . ',id',
        ]);

        if ($request->has('num_col')) {
            $medico->num_col = trim($request->num_col);
            if ($medico->isDirty()) {
                $medico->save();
            }
        }

        return response()->json($medico->load(['usuario', 'agendas']));
    }
}