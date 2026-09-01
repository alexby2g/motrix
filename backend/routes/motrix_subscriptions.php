<?php

use App\Http\Controllers\LiquidacionMotrixController;
use App\Http\Controllers\MotrixSubscriptionDashboardController;
use App\Http\Controllers\PagoSuscripcionMotrixController;
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

            Route::get(
                '/suscripcion-motrix/pagos',
                [
                    PagoSuscripcionMotrixController::class,
                    'misPagos',
                ]
            );

            Route::get(
                '/suscripcion-motrix/alertas',
                [
                    PagoSuscripcionMotrixController::class,
                    'misAlertas',
                ]
            );

            Route::post(
                '/suscripcion-motrix/alertas/{id}/leida',
                [
                    PagoSuscripcionMotrixController::class,
                    'marcarAlertaLeida',
                ]
            )->whereNumber('id');
        });

    /*
    |--------------------------------------------------------------------------
    | CONSULTA Y CONFIGURACIÓN DE SUSCRIPCIONES
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

        Route::get(
            '/suscripciones-motrix/panel',
            [
                MotrixSubscriptionDashboardController::class,
                'resumen',
            ]
        )->middleware(
            'role:admin_general,secretario'
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
    | COBRANZA MOTRIX
    |--------------------------------------------------------------------------
    |
    | El dinero de la suscripción queda separado de pagos_sindicales.
    | El secretario administra únicamente conductores de su sindicato.
    |
    */
    Route::middleware(
        'role:admin_general,secretario'
    )->group(function () {
        Route::get(
            '/pagos-suscripcion-motrix',
            [
                PagoSuscripcionMotrixController::class,
                'index',
            ]
        );

        Route::post(
            '/suscripciones-motrix/{id}/generar-renovacion',
            [
                PagoSuscripcionMotrixController::class,
                'generarRenovacion',
            ]
        )->whereNumber('id');

        Route::post(
            '/pagos-suscripcion-motrix/{id}/registrar',
            [
                PagoSuscripcionMotrixController::class,
                'registrar',
            ]
        )->whereNumber('id');
    });

    /*
    |--------------------------------------------------------------------------
    | LIQUIDACIONES SINDICATO -> MOTRIX - V5.3
    |--------------------------------------------------------------------------
    |
    | Solo se concilian pagos efectivamente cobrados por el sindicato.
    | Los pagos motrix_directo no generan saldo de liquidación sindical.
    |
    */
    Route::middleware(
        'role:admin_general,secretario'
    )->group(function () {
        Route::get(
            '/liquidaciones-motrix',
            [
                LiquidacionMotrixController::class,
                'index',
            ]
        );

        Route::get(
            '/liquidaciones-motrix/resumen',
            [
                LiquidacionMotrixController::class,
                'resumen',
            ]
        );

        Route::post(
            '/liquidaciones-motrix/preparar',
            [
                LiquidacionMotrixController::class,
                'preparar',
            ]
        );

        Route::post(
            '/liquidaciones-motrix/comprobante',
            [
                LiquidacionMotrixController::class,
                'subirComprobante',
            ]
        );

        Route::get(
            '/liquidaciones-motrix/{id}',
            [
                LiquidacionMotrixController::class,
                'show',
            ]
        )->whereNumber('id');

        Route::post(
            '/liquidaciones-motrix/{id}/transferencias',
            [
                LiquidacionMotrixController::class,
                'registrarTransferencia',
            ]
        )->whereNumber('id');
    });

    /*
    |--------------------------------------------------------------------------
    | PLANES, SINCRONIZACIÓN Y VALIDACIÓN - SOLO ADMIN GENERAL
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

            Route::post(
                '/suscripciones-motrix/sincronizar',
                [
                    PagoSuscripcionMotrixController::class,
                    'sincronizar',
                ]
            );

            Route::post(
                '/transferencias-liquidacion-motrix/{id}/validar',
                [
                    LiquidacionMotrixController::class,
                    'validarTransferencia',
                ]
            )->whereNumber('id');

            Route::post(
                '/transferencias-liquidacion-motrix/{id}/observar',
                [
                    LiquidacionMotrixController::class,
                    'observarTransferencia',
                ]
            )->whereNumber('id');
        });
});
