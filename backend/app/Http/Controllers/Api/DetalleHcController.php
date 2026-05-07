<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetalleHc;
use App\Models\Hc;
use App\Models\Cita;
use Illuminate\Http\Request;

class DetalleHcController extends Controller
{
    // GET /api/hcs/{nhc}/detalles
    public function index($nhc)
    {
        $hc = Hc::find($nhc);
        if (!$hc) {
            return response()->json(['error' => 'HC no encontrada'], 404);
        }

        $detalles = DetalleHc::where('nhc', $nhc)
                             ->with(['cita.agenda.medicoUsuario', 'cita.paciente'])
                             ->orderBy('f_consulta', 'desc')
                             ->get();

        return response()->json([
            'hc'       => $hc,
            'detalles' => $detalles
        ]);
    }

    // GET /api/hcs/{nhc}/detalles/{num_orden}
    public function show($nhc, $num_orden)
    {
        $detalle = DetalleHc::where('nhc', $nhc)
                            ->where('num_orden', $num_orden)
                            ->with(['cita.agenda.medicoUsuario', 'cita.paciente'])
                            ->first();

        if (!$detalle) {
            return response()->json(['error' => 'Detalle hc no encontrado'], 404);
        }

        return response()->json($detalle);
    }

    // POST /api/hcs/{nhc}/detalles
    public function store(Request $request, $nhc)
    {
        $hc = Hc::find($nhc);
        if (!$hc) {
            return response()->json(['error' => 'HC no encontrada'], 404);
        }

        $request->validate([
            'mov_consulta' => 'required|string|max:32',
            'tto'          => 'required|string|max:60',
            'f_consulta'   => 'nullable|date',
            'sinto'        => 'nullable|string',
            'diag'         => 'nullable|string|max:80',
            'id_cita'      => 'nullable|exists:cita,id_cita',
        ]);

        // Si viene id_cita, verificamos que la cita pertenece al paciente de esta HC
        $cita = null;
        if ($request->id_cita) {
            $cita     = Cita::with('agenda')->find($request->id_cita);
            $paciente = $hc->paciente;

            if (!$paciente || $cita->id_paciente !== $paciente->id_paciente) {
                return response()->json([
                    'error' => 'La cita no corresponde al paciente de esta HC'
                ], 422);
            }
            if (DetalleHc::where('id_cita', $request->id_cita)->exists()) {
                return response()->json([
                    'error'   => 'Detalle hc duplicado',
                    'message' => 'Esta cita ya tiene un detalle de Historia Clínica asociado.'
                ], 422);
            }
        }

        // Usar la fecha de la agenda de la cita; si no hay cita, fecha de hoy
        $f_consulta = $request->f_consulta
            ?? $cita?->agenda?->fecha
            ?? now()->toDateString();

        $detalle = DetalleHc::create([
            'nhc'          => $nhc,
            'id_cita'      => $request->id_cita ?? null,
            'mov_consulta' => trim($request->mov_consulta),
            'tto'          => trim($request->tto),
            'f_consulta'   => $f_consulta,
            'sinto'        => $request->sinto,
            'diag'         => $request->diag,
        ]);

        return response()->json($detalle->load(['cita.agenda.medicoUsuario', 'cita.paciente']), 201);
    }

    // PUT /api/hcs/{nhc}/detalles/{num_orden}
    public function update(Request $request, $nhc, $num_orden)
    {
        $detalle = DetalleHc::where('nhc', $nhc)
                            ->where('num_orden', $num_orden)
                            ->first();

        if (!$detalle) {
            return response()->json(['error' => 'Detalle hc no encontrado'], 404);
        }

        // Verificar autoría: solo el médico que atendió puede editar
        if ($detalle->id_cita) {
            $cita = Cita::with('agenda')->find($detalle->id_cita);
            if ($cita && $cita->agenda && $cita->agenda->id_med !== auth()->id()) {
                return response()->json([
                    'error' => 'No fue atendido por usted, el informe del paciente no puede ser editado'
                ], 403);
            }
        }

        $request->validate([
            'mov_consulta' => 'sometimes|required|string|max:32',
            'tto'          => 'sometimes|nullable|string|max:60',
            'f_consulta'   => 'sometimes|date',
            'sinto'        => 'sometimes|nullable|string',
            'diag'         => 'sometimes|nullable|string|max:80',
        ]);

        // Actualizamos campo por campo usando where() por la PK compuesta
        DetalleHc::where('nhc', $nhc)
                 ->where('num_orden', $num_orden)
                 ->update($request->only(['mov_consulta', 'tto', 'f_consulta', 'sinto', 'diag']));

        // Recargamos el detalle actualizado
        $detalle = DetalleHc::where('nhc', $nhc)
                            ->where('num_orden', $num_orden)
                            ->with(['cita.agenda.medicoUsuario', 'cita.paciente'])
                            ->first();

        return response()->json($detalle);
    }

    // DELETE /api/hcs/{nhc}/detalles/{num_orden}
    public function destroy($nhc, $num_orden)
    {
        $detalle = DetalleHc::where('nhc', $nhc)
                            ->where('num_orden', $num_orden)
                            ->first();

        if (!$detalle) {
            return response()->json(['error' => 'Detalle hc no encontrado'], 404);
        }

        DetalleHc::where('nhc', $nhc)
                 ->where('num_orden', $num_orden)
                 ->delete();

        return response()->json(['message' => 'Detalle hc eliminado correctamente']);
    }
}