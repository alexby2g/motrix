<?php

use App\Http\Controllers\SuscripcionMotrixController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SUSCRIPCIONES MOTRIX - V5
|--------------------------------------------------------------------------
|
| Este módulo es totalmente independiente de pagos_sindicales.
| Maneja exclusivamente la suscripción comercial al servicio MOTRIX.
|
*/

Route::middleware('auth:sanctum')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | CONDUCTOR
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:conductor')
        ->prefix('conductor')
        ->group(function () {
            Route::get(
                '/suscripcion-motrix',
                [
                    SuscripcionMotrixController::class,
                    'miSuscripcion',
                ]
            );
        });

    /*
    |--------------------------------------------------------------------------
    | CONSULTA Y CONFIGURACIÓN SINDICAL
    |--------------------------------------------------------------------------
    |
    | El secretario queda limitado en backend a su propio sindicato.
    |
    */
    Route::middleware(
        'role:admin_general,admin_registro,secretario'
    )->group(function () {
        Route::get(
            '/planes-suscripcion-motrix',
            [
                SuscripcionMotrixController::class,
                'planes',
            ]
        );

        Route::get(
            '/suscripciones-motrix',
            [
                SuscripcionMotrixController::class,
                'index',
            ]
        );

        Route::post(
            '/suscripciones-motrix/configurar',
            [
                SuscripcionMotrixController::class,
                'configurar',
            ]
        );

        Route::get(
            '/suscripciones-motrix/{id}',
            [
                SuscripcionMotrixController::class,
                'show',
            ]
        )->whereNumber('id');
    });

    /*
    |--------------------------------------------------------------------------
    | PLANES COMERCIALES - SOLO ADMIN GENERAL
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin_general')
        ->group(function () {
            Route::post(
                '/planes-suscripcion-motrix',
                [
                    SuscripcionMotrixController::class,
                    'storePlan',
                ]
            );

            Route::put(
                '/planes-suscripcion-motrix/{id}',
                [
                    SuscripcionMotrixController::class,
                    'updatePlan',
                ]
            )->whereNumber('id');
        });
});
