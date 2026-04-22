<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Medico;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    // GET /api/medicos/{id_medico}/agendas
    public function index($id_medico)
    {
        $medico = Medico::find($id_medico);
        if (!$medico) return response()->json(['error' => 'Médico no encontrado'], 404);
        return response()->json($medico->agendas);
    }

    // GET /api/medicos/{id_medico}/agendas/{id_agenda}
    public function show($id_medico, $id_agenda)
    {
        $agenda = Agenda::where('id_med', $id_medico)->where('id_agenda', $id_agenda)->first();
        if (!$agenda) return response()->json(['error' => 'Agenda no encontrada'], 404);
        return response()->json($agenda);
    }

    // POST /api/medicos/{id_medico}/agendas
    public function store(Request $request, $id_medico)
    {
        $medico = Medico::find($id_medico);
        if (!$medico) return response()->json(['error' => 'Médico no encontrado'], 404);

        $request->validate([
            'fecha'    => 'required|date',
            'h_inicio' => 'required|date_format:H:i',
            'h_fin'    => [
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value <= $request->h_inicio) {
                        $fail('La hora de fin debe ser posterior a la de inicio.');
                    }
                },
            ],
            'min_intervalo' => [
                'required',
                'integer',
                'min:5',
                'max:60',
                function ($attribute, $value, $fail) use ($request) {
                    $minutos = (strtotime($request->h_fin) - strtotime($request->h_inicio)) / 60;
                    if ($value > $minutos) $fail("El intervalo no cabe en el tiempo total ({$minutos} min).");
                    if ($minutos % $value !== 0) $fail("El tiempo total ({$minutos} min) debe ser divisible por el intervalo ({$value} min).");
                },
            ],
        ]);

        if (Agenda::where('id_med', $id_medico)->where('fecha', $request->fecha)->exists()) {
            return response()->json(['error' => 'El médico ya tiene una agenda para esta fecha.'], 422);
        }

        $agenda = Agenda::create([
            'id_med' => $id_medico,
            'fecha' => $request->fecha,
            'h_inicio' => $request->h_inicio,
            'h_fin' => $request->h_fin,
            'min_intervalo' => $request->min_intervalo,
        ]);

        return response()->json($agenda, 201);
    }

    // PUT /api/medicos/{id_medico}/agendas/{id_agenda}
    public function update(Request $request, $id_medico, $id_agenda)
    {
        $agenda = Agenda::where('id_med', $id_medico)->where('id_agenda', $id_agenda)->first();
        if (!$agenda) return response()->json(['error' => 'Agenda no encontrada'], 404);

        $request->validate([
            'fecha'    => 'sometimes|date',
            'h_inicio' => 'sometimes|date_format:H:i',
            'h_fin'    => [
                'sometimes',
                'date_format:H:i',
                function ($attribute, $value, $fail) use ($request, $agenda) {
                    $inicio = $request->h_inicio ?? $agenda->h_inicio;
                    if ($value <= $inicio) $fail('La hora de fin debe ser posterior a la de inicio.');
                },
            ],
            'min_intervalo' => [
                'sometimes',
                'integer',
                'min:5',
                'max:60',
                function ($attribute, $value, $fail) use ($request, $agenda) {
                    $inicio = $request->h_inicio ?? $agenda->h_inicio;
                    $fin    = $request->h_fin    ?? $agenda->h_fin;
                    $minutos = (strtotime($fin) - strtotime($inicio)) / 60;
                    if ($value > $minutos) $fail("El intervalo no cabe en el tiempo total.");
                    if ($minutos % $value !== 0) $fail("El tiempo total ({$minutos} min) debe ser divisible por el intervalo.");
                },
            ],
        ]);

        $agenda->fill($request->only(['fecha', 'h_inicio', 'h_fin', 'min_intervalo']));

        if ($agenda->isDirty()) {
            $agenda->save();
        }

        return response()->json($agenda);
    }

    // DELETE /api/medicos/{id_medico}/agendas/{id_agenda}
    public function destroy($id_medico, $id_agenda)
    {
        $agenda = Agenda::where('id_med', $id_medico)->where('id_agenda', $id_agenda)->first();
        if (!$agenda) return response()->json(['error' => 'Agenda no encontrada'], 404);

        if ($agenda->citas()->count() > 0) {
            return response()->json(['error' => 'No se puede eliminar', 'message' => 'La agenda tiene citas asociadas.'], 422);
        }

        $agenda->delete();
        return response()->json(['message' => 'Agenda eliminada correctamente']);
    }
}