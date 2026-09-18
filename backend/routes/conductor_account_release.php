<?php

use App\Http\Controllers\ConductorCuentaController;
use Illuminate\Support\Facades\Route;

Route::delete(
    '/conductor/cuenta',
    [ConductorCuentaController::class, 'destroy']
)->middleware([
    'auth:sanctum',
    'role:conductor',
    'throttle:motrix-eliminar-cuenta',
]);
