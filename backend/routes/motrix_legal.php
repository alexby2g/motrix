<?php

use App\Http\Controllers\MotrixLegalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MOTRIX - DOCUMENTOS LEGALES
|--------------------------------------------------------------------------
|
| Los documentos pueden consultarse sin iniciar sesión.
| La aceptación se registra únicamente para usuario autenticado.
|
*/

Route::get(
    '/legal/documents',
    [MotrixLegalController::class, 'documents']
);

Route::middleware('auth:sanctum')->group(function () {
    Route::get(
        '/legal/status',
        [MotrixLegalController::class, 'status']
    );

    Route::post(
        '/legal/accept',
        [MotrixLegalController::class, 'accept']
    )->middleware('throttle:20,1');
});
