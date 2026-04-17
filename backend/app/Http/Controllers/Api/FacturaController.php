<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use Illuminate\Http\Request;

class FacturaController extends Controller
{
    public function index()
    {
        // Traemos las facturas con sus líneas (Eager Loading)
        $facturas = Factura::with('lineas')->get();
        return response()->json($facturas);
    }

    public function show($id)
    {
        $factura = Factura::with('lineas')->find($id);
        
        if (!$factura) {
            return response()->json(['error' => 'Factura no encontrada'], 404);
        }

        return response()->json($factura);
    }
}