<?php

use App\Http\Controllers\MotrixAccountController;
use Illuminate\Support\Facades\Route;

Route::post(
    '/auth/registro-pasajero-celular',
    [MotrixAccountController::class, 'registroPasajeroCelular']
)->middleware('throttle:motrix-registro-publico');

Route::middleware('auth:sanctum')->group(function () {
    Route::post(
        '/auth/cambiar-password',
        [MotrixAccountController::class, 'cambiarPassword']
    )->middleware('role:conductor,pasajero');

    Route::delete(
        '/pasajero/cuenta-segura',
        [MotrixAccountController::class, 'eliminarCuentaPasajeroSegura']
    )->middleware([
        'role:pasajero',
        'throttle:motrix-eliminar-cuenta',
    ]);

    Route::post(
        '/mototaxistas/{id}/cuenta-conductor-celular',
        [MotrixAccountController::class, 'crearCuentaConductorCelular']
    )->middleware('role:admin_general,admin_registro,secretario');

    Route::post(
        '/pasajeros/{id}/cuenta-pasajero-celular',
        [MotrixAccountController::class, 'crearCuentaPasajeroCelular']
    )->middleware('role:admin_general');
});
