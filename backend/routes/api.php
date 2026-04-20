<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FacturaController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Agrupamos las rutas de FACTURAS
Route::prefix('facturas')->group(function () { //Con el prefix hacemos que sea mas eficiente
    Route::get('/', [FacturaController::class, 'index']);
    Route::get('/{num_fact}', [FacturaController::class, 'show']);
    Route::post('/', [FacturaController::class, 'store']);
    Route::put('/{num_fact}', [FacturaController::class, 'update']);
    Route::delete('/{num_fact}', [FacturaController::class, 'destroy']);
    
    // Ruta especial para abonos
    Route::post('/{num_fact}/abono', [FacturaController::class, 'crearAbono']);
});