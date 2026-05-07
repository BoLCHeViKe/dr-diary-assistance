<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hc;
use App\Models\Paciente;
use App\Models\DetalleHc;
use Illuminate\Http\Request;

class HcController extends Controller
{
    // GET /api/hcs
    public function index()
    {
        $hcs = Hc::with(['paciente', 'detalles'])->get();
        return response()->json($hcs);
    }

    // GET /api/hcs/{nhc}
    public function show($nhc)
    {
        $hc = Hc::with(['paciente', 'detalles'])->find($nhc);

        if (!$hc) {
            return response()->json(['error' => 'Historia Clínica no encontrada'], 404);
        }

        return response()->json($hc);
    }

    // GET /api/pacientes/{id}/hc
    public function showByPaciente($id_paciente)
    {
        $paciente = Paciente::find($id_paciente);

        if (!$paciente) {
            return response()->json(['error' => 'Paciente no encontrado'], 404);
        }

        $hc = Hc::with(['paciente'])->find($paciente->nhc);

        if (!$hc) {
            return response()->json(['error' => 'Historia Clínica no encontrada'], 404);
        }

        $detalles = DetalleHc::where('nhc', $hc->nhc)
                             ->with(['cita.agenda.medicoUsuario', 'cita.paciente'])
                             ->orderBy('f_consulta', 'desc')
                             ->get();

        return response()->json([
            'hc'       => $hc,
            'detalles' => $detalles,
        ]);
    }
}