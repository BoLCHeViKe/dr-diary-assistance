<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hc;
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
}