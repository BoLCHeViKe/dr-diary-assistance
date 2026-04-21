<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Models\Hc; // Modelo HC (nhc autoincremental)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PacienteController extends Controller
{
    // GET /api/pacientes
    public function index()
    {
        // Cargamos la relación 'hc' definida en el modelo Paciente
        $pacientes = Paciente::with('hc')->get();
        return response()->json($pacientes);
    }

    // GET /api/pacientes/{id}
    public function show($id)
    {
        $paciente = Paciente::with('hc')->find($id);

        if (!$paciente) {
            return response()->json(['error' => 'Paciente no encontrado'], 404);
        }

        return response()->json($paciente);
    }

    // POST /api/pacientes
    public function store(Request $request)
    {
        $request->validate([
            'nombre'    => 'required|string|max:50',
            'apellido1' => 'required|string|max:50',
            'apellido2' => 'nullable|string|max:50',
            'fecha_nac' => 'nullable|date',
            'dni' => [
                'required',
                'string',
                'unique:paciente,dni', // Único en la tabla paciente
                'regex:/^[0-9]{8}[TRWAGMYFPDXBNJZSQVHLCKE]$/i'
            ],
            'telf'  => 'nullable|string|max:15',
            'email' => 'nullable|email:rfc|max:64|unique:paciente,email', // <-- Único aquí también (OJO!! |email:rfc,dns| si queremos que compruebe email reales)
            'direccion' => 'nullable|string|max:100',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // 1. Crear la Historia Clínica (nhc se genera solo)
                $hc = Hc::create([
                    'fecha_apert' => now()->toDateString(),
                ]);

                // 2. Crear al Paciente asociando el nhc recién creado
                $paciente = Paciente::create([
                    'nombre'    => $request->nombre,
                    'apellido1' => $request->apellido1,
                    'apellido2' => $request->apellido2,
                    'fecha_nac' => $request->fecha_nac,
                    'dni'       => $request->dni,
                    'telf'      => $request->telf,
                    'email'     => $request->email,
                    'direccion' => $request->direccion,
                    'nhc'       => $hc->nhc, 
                ]);

                return response()->json($paciente->load('hc'), 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en el alta del paciente',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // PUT /api/pacientes/{id}
    public function update(Request $request, $id)
    {
        $paciente = Paciente::find($id);

        if (!$paciente) {
            return response()->json(['error' => 'Paciente no encontrado'], 404);
        }

        $request->validate([
            'nombre'    => 'sometimes|string|max:50',
            'apellido1' => 'sometimes|string|max:50',
            'apellido2' => 'sometimes|nullable|string|max:50',
            'fecha_nac' => 'sometimes|nullable|date',
            'dni' => [
                'sometimes',
                'string',
                Rule::unique('paciente', 'dni')->ignore($id, 'id_paciente'), // Ignora al paciente actual
                'regex:/^[0-9]{8}[TRWAGMYFPDXBNJZSQVHLCKE]$/i'
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email:rfc', //OJO!! 'email:rfc,dns', si queremos que compruebe email reales
                'max:64',
                Rule::unique('paciente', 'email')->ignore($id, 'id_paciente') // Ignora al paciente actual
            ],
            'telf'      => 'sometimes|nullable|string|max:15',
            'direccion' => 'sometimes|nullable|string|max:100',
        ]);

        //$paciente->update($request->all());
        $paciente->update($request->only([
            'nombre', 'apellido1', 'apellido2', 'fecha_nac', 'dni', 'telf', 'email', 'direccion'
            ]));

        return response()->json($paciente->load('hc'));
    }

    // DELETE /api/pacientes/{id}
    public function destroy($id)
    {
        $paciente = Paciente::find($id);

        if (!$paciente) {
            return response()->json(['error' => 'Paciente no encontrado'], 404);
        }

        try {
            DB::transaction(function () use ($paciente) {
                $nhcRelacionado = $paciente->nhc;
                
                // Borramos paciente (esto debería limpiar sus datos personales)
                $paciente->delete();
                
                // Borramos la HC (esto limpiará también los detalles_hc si hay CASCADE)
                Hc::where('nhc', $nhcRelacionado)->delete();
            });

            return response()->json(['message' => 'Paciente e Historia Clínica eliminados correctamente']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar el registro',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}