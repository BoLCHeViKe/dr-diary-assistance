<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Agenda;
use App\Models\Factura;
use App\Models\LineaFactura;
use App\Models\Prestacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CitaController extends Controller
{
    // GET /api/agendas/{id_agenda}/citas
    public function index($id_agenda)
    {
        $agenda = Agenda::find($id_agenda);
        if (!$agenda) {
            return response()->json(['error' => 'Agenda no encontrada'], 404);
        }

        $citas = Cita::where('id_agenda', $id_agenda)
                     ->with(['paciente'])
                     ->orderBy('h_cita', 'asc')
                     ->get();

        return response()->json([
            'agenda' => $agenda,
            'citas'  => $citas
        ]);
    }

    // GET /api/agendas/{id_agenda}/citas/{id_cita}
    public function show($id_agenda, $id_cita)
    {
        $cita = Cita::where('id_agenda', $id_agenda)
                    ->where('id_cita', $id_cita)
                    ->with(['paciente', 'agenda'])
                    ->first();

        if (!$cita) {
            return response()->json(['error' => 'Cita no encontrada'], 404);
        }

        return response()->json($cita);
    }

    // POST /api/agendas/{id_agenda}/citas
    public function store(Request $request, $id_agenda)
    {
        $agenda = Agenda::find($id_agenda);
        if (!$agenda) {
            return response()->json(['error' => 'Agenda no encontrada'], 404);
        }

        $request->validate([
            'id_paciente' => 'required|exists:paciente,id_paciente',
            'codigo_esp'  => 'required|string|exists:especialidad,codigo_esp',
            'id_prest'    => 'required|integer',
            'h_cita'      => 'required|date_format:H:i',
        ]);

        // 1. Validar prestación compuesta
        $prestacion = Prestacion::where('codigo_esp', $request->codigo_esp)
                                ->where('id_prest', $request->id_prest)
                                ->first();

        if (!$prestacion) {
            return response()->json(['error' => 'La prestación no existe para esta especialidad'], 404);
        }

        // 2. Validar rango horario
        $hCita   = strtotime($request->h_cita);
        $hInicio = strtotime($agenda->h_inicio);
        $hFin    = strtotime($agenda->h_fin);

        if ($hCita < $hInicio || $hCita >= $hFin) {
            return response()->json([
                'error' => "La hora {$request->h_cita} está fuera del horario de la agenda ({$agenda->h_inicio} - {$agenda->h_fin})."
            ], 422);
        }

        // 3. Validar múltiplo del intervalo
        $minutosDesdeInicio = ($hCita - $hInicio) / 60;
        if ($minutosDesdeInicio % $agenda->min_intervalo !== 0) {
            return response()->json([
                'error' => "La hora {$request->h_cita} no corresponde a ningún hueco válido. El intervalo es de {$agenda->min_intervalo} minutos."
            ], 422);
        }

        // 4. Validar hueco libre
        $ocupado = Cita::where('id_agenda', $id_agenda)
                       ->where('h_cita', $request->h_cita)
                       ->exists();

        if ($ocupado) {
            return response()->json([
                'error' => "El hueco de las {$request->h_cita} ya está ocupado."
            ], 422);
        }

        // 5. Crear la cita
        $cita = Cita::create([
            'id_agenda'   => $id_agenda,
            'id_paciente' => $request->id_paciente,
            'codigo_esp'  => $request->codigo_esp,
            'id_prest'    => $request->id_prest,
            'h_cita'      => $request->h_cita,
            'estado'      => 'citado',
        ]);

        return response()->json($cita->load(['paciente']), 201);
    }

    // PUT /api/agendas/{id_agenda}/citas/{id_cita}
    public function update(Request $request, $id_agenda, $id_cita)
    {
        $cita = Cita::where('id_agenda', $id_agenda)
                    ->where('id_cita', $id_cita)
                    ->first();

        if (!$cita) {
            return response()->json(['error' => 'Cita no encontrada'], 404);
        }

        if ($cita->num_fact) {
            return response()->json([
                'error' => 'No se puede modificar, la cita ya ha sido facturada.',
            ], 422);
        }

        // Edición de campos de la cita (paciente, especialidad, prestación)
        if ($request->has('id_paciente') || $request->has('codigo_esp') || $request->has('id_prest')) {
            $request->validate([
                'id_paciente' => 'required|exists:paciente,id_paciente',
                'codigo_esp'  => 'required|string|exists:especialidad,codigo_esp',
                'id_prest'    => 'required|integer',
            ]);

            $prestacion = Prestacion::where('codigo_esp', $request->codigo_esp)
                                    ->where('id_prest', $request->id_prest)
                                    ->first();

            if (!$prestacion) {
                return response()->json(['error' => 'La prestación no existe para esta especialidad'], 404);
            }

            $cita->id_paciente = $request->id_paciente;
            $cita->codigo_esp  = $request->codigo_esp;
            $cita->id_prest    = $request->id_prest;
            $cita->save();

            return response()->json($cita->load(['paciente']));
        }

        // Actualización de estado
        $request->validate([
            'estado' => 'required|in:citado,en espera,atendido,facturado',
        ]);

        $cita->estado = $request->estado;

        if ($cita->isDirty()) {
            $cita->save();
        }

        return response()->json($cita->load(['paciente']));
    }

    // DELETE /api/agendas/{id_agenda}/citas/{id_cita}
    public function destroy($id_agenda, $id_cita)
    {
        $cita = Cita::where('id_agenda', $id_agenda)
                    ->where('id_cita', $id_cita)
                    ->first();

        if (!$cita) {
            return response()->json(['error' => 'Cita no encontrada'], 404);
        }

        if ($cita->num_fact) {
            return response()->json([
                'error' => '¡El/la paciente ya ha sido facturado/a!'
            ], 422);
        }

        if ($cita->estado === 'atendido') {
            return response()->json([
                'error' => 'El paciente ya ha sido atendido, no se puede eliminar la cita'
            ], 422);
        }

        if (!in_array($cita->estado, ['citado', 'en espera'])) {
            return response()->json([
                'error' => 'No se puede eliminar la cita en el estado actual'
            ], 422);
        }

        $cita->delete();
        return response()->json(['message' => 'Cita eliminada correctamente']);
    }

    // GET /api/pacientes/{id_paciente}/citas
    public function citasPorPaciente($id_paciente)
    {
        return response()->json(
            Cita::join('agenda', 'cita.id_agenda', '=', 'agenda.id_agenda')
                ->where('cita.id_paciente', $id_paciente)
                ->with(['agenda', 'paciente'])
                ->select('cita.*')
                ->orderBy('agenda.fecha', 'asc')
                ->orderBy('cita.h_cita', 'asc')
                ->get()
        );
    }

    // POST /api/agendas/{id_agenda}/citas/{id_cita}/facturar
    public function facturar(Request $request, $id_agenda, $id_cita)
    {
        $cita = Cita::where('id_agenda', $id_agenda)
                    ->where('id_cita', $id_cita)
                    ->with(['paciente'])
                    ->first();

        if (!$cita) {
            return response()->json(['error' => 'Cita no encontrada'], 404);
        }

        if ($cita->num_fact) {
            return response()->json(['error' => 'La cita ya ha sido facturada'], 422);
        }

        $request->validate([
            'cantidad' => 'sometimes|integer|min:1',
            'precio'   => 'sometimes|numeric|min:0',
        ]);

        $prestacion = Prestacion::where('codigo_esp', $cita->codigo_esp)
                                ->where('id_prest', $cita->id_prest)
                                ->first();

        if (!$prestacion) {
            return response()->json(['error' => 'Prestación no encontrada'], 404);
        }

        $cantidad       = (int) ($request->cantidad ?? 1);
        $precioOriginal = (float) $prestacion->precio;
        $precioCustom   = $request->has('precio') ? (float) $request->precio : null;

        try {
            return DB::transaction(function () use ($cita, $cantidad, $precioOriginal, $precioCustom, $prestacion) {
                // 1. Create in 'borrador' so the DB trigger allows line insertion
                $factura = Factura::create([
                    'id_paciente' => $cita->id_paciente,
                    'estado'      => 'borrador',
                    'fecha'       => now()->toDateString(),
                ]);

                // 2. Insert line — trigger auto-fills num_linea, precio, total from prestacion
                LineaFactura::create([
                    'num_fact'   => $factura->num_fact,
                    'cantidad'   => $cantidad,
                    'codigo_esp' => $cita->codigo_esp,
                    'id_prest'   => $cita->id_prest,
                    'precio'     => 0,
                    'total'      => 0,
                ]);

                // 3. If user specified a custom price, override what the trigger set
                if ($precioCustom !== null && abs($precioCustom - $precioOriginal) > 0.001) {
                    $customTotal = round($cantidad * $precioCustom, 2);
                    DB::statement(
                        'UPDATE lineafactura SET precio = ?, total = ? WHERE num_fact = ? AND num_linea = 1',
                        [$precioCustom, $customTotal, $factura->num_fact]
                    );
                } elseif ($cantidad > 1) {
                    // Trigger sets total = 1 * precio; correct it for cantidad > 1
                    DB::statement(
                        'UPDATE lineafactura SET cantidad = ?, total = ? WHERE num_fact = ? AND num_linea = 1',
                        [$cantidad, round($cantidad * $precioOriginal, 2), $factura->num_fact]
                    );
                }

                // 4. Emit the invoice
                $factura->estado = 'emitida';
                $factura->save();

                // 5. Link cita to the invoice and mark as facturado
                $cita->estado   = 'facturado';
                $cita->num_fact = $factura->num_fact;
                $cita->save();

                return response()->json($cita->load(['paciente']), 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Error al facturar la cita',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // GET /api/medicos/{id_medico}/citas
    public function citasPorMedico($id_medico)
    {
        return response()->json(
            Cita::join('agenda', 'cita.id_agenda', '=', 'agenda.id_agenda')
                ->where('agenda.id_med', $id_medico)
                ->with(['agenda', 'paciente'])
                ->select('cita.*')
                ->orderBy('agenda.fecha', 'asc')
                ->orderBy('cita.h_cita', 'asc')
                ->get()
        );
    }
}