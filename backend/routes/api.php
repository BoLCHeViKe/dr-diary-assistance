<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RolController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\PacienteController;
use App\Http\Controllers\Api\EspecialidadController;
use App\Http\Controllers\Api\PrestacionController;
use App\Http\Controllers\Api\MedicoController;
use App\Http\Controllers\Api\AgendaController;
use App\Http\Controllers\Api\CitaController;
use App\Http\Controllers\Api\FacturaController;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS
|--------------------------------------------------------------------------
| Solo el Login es público. Nadie entra sin pasar por aquí. (Ni tampoco pueden registrarse, es todo "backoffice")
*/
// Route::post('login', [AuthController::class, 'login']); // La crearemos luego

/*
|--------------------------------------------------------------------------
| RUTAS PRIVADAS (Todo el sistema está aquí dentro)
|--------------------------------------------------------------------------
*/

//Actualmente lo dejamos comentado!!De esta manera podremos hacer tests facilmente durante el desarrollo
// Route::middleware('auth:sanctum')->group(function () {

    // 1. Identificación: Para saber quién es el usuario logueado
    Route::get('/perfil', function (Request $request) {
        return $request->user()->load('rol');
    });

    // 2. Gestión de Usuarios: SOLO accesible si ya estás logueado como Admin
    Route::prefix('usuarios')->group(function () {
        Route::get('/', [UsuarioController::class, 'index']);
        Route::get('/{id}', [UsuarioController::class, 'show']);
        Route::post('/', [UsuarioController::class, 'store']);    // <-- Ahora crear usuarios está PROTEGIDO
        Route::put('/{id}', [UsuarioController::class, 'update']);
        Route::delete('/{id}', [UsuarioController::class, 'destroy']);
    });

    // Agrupamos las rutas de ROLES
    Route::prefix('roles')->group(function () {
        Route::get('/', [RolController::class, 'index']);
        Route::get('/{id}', [RolController::class, 'show']);
        Route::post('/', [RolController::class, 'store']);
        Route::put('/{id}', [RolController::class, 'update']);
        Route::delete('/{id}', [RolController::class, 'destroy']);
    });

    // Agrupamos las rutas de PACIENTES
    Route::prefix('pacientes')->group(function () {
        Route::get('/', [PacienteController::class, 'index']);
        Route::get('/{id}', [PacienteController::class, 'show']);
        Route::post('/', [PacienteController::class, 'store']);
        Route::put('/{id}', [PacienteController::class, 'update']);
        Route::delete('/{id}', [PacienteController::class, 'destroy']);
    });
    // Agrupamos las rutas de ESPECIALIDADES
    Route::prefix('especialidades')->group(function () {
        Route::get('/', [EspecialidadController::class, 'index']);
        Route::get('/{codigo_esp}', [EspecialidadController::class, 'show']);
        Route::post('/', [EspecialidadController::class, 'store']);
        Route::put('/{codigo_esp}', [EspecialidadController::class, 'update']);
        Route::delete('/{codigo_esp}', [EspecialidadController::class, 'destroy']);
    });
    // Agrupamos las rutas de PRESTACION
    Route::prefix('prestaciones')->group(function () {
        Route::get('/', [PrestacionController::class, 'index']);
        Route::get('/{codigo_esp}/{id_prest}', [PrestacionController::class, 'show']);
        Route::post('/', [PrestacionController::class, 'store']);
        Route::put('/{codigo_esp}/{id_prest}', [PrestacionController::class, 'update']);
        Route::delete('/{codigo_esp}/{id_prest}', [PrestacionController::class, 'destroy']);
    });
    // Agrupamos las rutas de MEDICO
    //POST médico   → se hace desde UsuarioController::store() con id_rol=2
    //DELETE médico → se hace desde UsuarioController::destroy()
    Route::prefix('medicos')->group(function () {
        Route::get('/', [MedicoController::class, 'index']);
        Route::get('/{id}', [MedicoController::class, 'show']);
        Route::put('/{id}', [MedicoController::class, 'update']);
    });
    // Agrupamos las rutas de AGENDA (OJO, depende de medico)
    Route::prefix('medicos/{id_medico}/agendas')->group(function () {
        Route::get('/', [AgendaController::class, 'index']);
        Route::get('/{id_agenda}', [AgendaController::class, 'show']);
        Route::post('/', [AgendaController::class, 'store']);
        Route::put('/{id_agenda}', [AgendaController::class, 'update']);
        Route::delete('/{id_agenda}', [AgendaController::class, 'destroy']);
    });


    // Rutas de Citas (Basado en mi Agenda)
    Route::prefix('agendas/{id_agenda}/citas')->group(function () {
        Route::get('/', [CitaController::class, 'index']);
        Route::post('/', [CitaController::class, 'store']);
        Route::get('/{id_cita}', [CitaController::class, 'show']);
        Route::put('/{id_cita}', [CitaController::class, 'update']);
        Route::delete('/{id_cita}', [CitaController::class, 'destroy']);
    });

    // Consultas Híbridas (Historiales y Paneles)
    Route::get('pacientes/{id_paciente}/citas', [CitaController::class, 'citasPorPaciente']);
    Route::get('medicos/{id_medico}/citas', [CitaController::class, 'citasPorMedico']);


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

// });