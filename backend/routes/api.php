<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FacturaController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/facturas', [FacturaController::class, 'index']);
Route::get('/facturas/{id}', [FacturaController::class, 'show']);