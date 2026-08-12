<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\PasajeroController;
use App\Http\Controllers\MototaxistaController;
use App\Http\Controllers\SindicatoController;
use App\Http\Controllers\MotocicletaController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PagoSindicalController;
use App\Http\Controllers\ImagenController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\MensajeViajeController;
use App\Http\Controllers\IncidenciaViajeController;
use App\Http\Controllers\FederacionController;
use App\Http\Controllers\UsuarioController;

/*
|--------------------------------------------------------------------------
| AUTENTICACIÓN PÚBLICA
|--------------------------------------------------------------------------
*/

Route::post(
    '/auth/login',
    [AuthController::class, 'login']
);

/*
|--------------------------------------------------------------------------
| VERIFICACIÓN PÚBLICA MEDIANTE QR
|--------------------------------------------------------------------------
*/

Route::get(
    '/verificar/{codigo}',
    [
        MototaxistaController::class,
        'verificarPublico',
    ]
)->middleware(
    'throttle:60,1'
);

/*
|--------------------------------------------------------------------------
| AUTORIZACIÓN REVERB
|--------------------------------------------------------------------------
*/

Broadcast::routes([
    'middleware' => [
        'auth:sanctum',
    ],
]);

/*
|--------------------------------------------------------------------------
| RUTAS AUTENTICADAS
|--------------------------------------------------------------------------
*/

Route::middleware(
    'auth:sanctum'
)->group(function () {

    /*
    |--------------------------------------------------------------------------
    | SESIÓN
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/auth/me',
        [AuthController::class, 'me']
    );

    Route::post(
        '/auth/logout',
        [AuthController::class, 'logout']
    );

    Route::post(
        '/auth/logout-all',
        [
            AuthController::class,
            'logoutAll',
        ]
    );

    /*
    |--------------------------------------------------------------------------
    | CONDUCTOR
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:conductor'
    )
        ->prefix('conductor')
        ->group(function () {
            Route::get(
                '/perfil',
                [
                    MototaxistaController::class,
                    'perfilConductor',
                ]
            );

            Route::patch(
                '/disponibilidad',
                [
                    MototaxistaController::class,
                    'actualizarDisponibilidad',
                ]
            );

            Route::patch(
                '/ubicacion',
                [
                    MototaxistaController::class,
                    'actualizarUbicacion',
                ]
            );

            Route::get(
                '/solicitudes-disponibles',
                [
                    MototaxistaController::class,
                    'solicitudesDisponibles',
                ]
            );

            Route::get(
                '/viaje-activo',
                [
                    MototaxistaController::class,
                    'viajeActivo',
                ]
            );

            Route::get(
                '/ganancias',
                [
                    SolicitudController::class,
                    'gananciasConductor',
                ]
            );

            Route::post(
                '/solicitudes/{id}/aceptar',
                [
                    SolicitudController::class,
                    'aceptar',
                ]
            );

            Route::post(
                '/solicitudes/{id}/rechazar',
                [
                    SolicitudController::class,
                    'rechazar',
                ]
            );

            Route::put(
                '/solicitudes/{id}/estado',
                [
                    SolicitudController::class,
                    'actualizarEstado',
                ]
            );

            /*
             * Chat conservado en backend, pero oculto
             * temporalmente en la interfaz.
             */
            Route::get(
                '/solicitudes/{id}/mensajes',
                [
                    MensajeViajeController::class,
                    'index',
                ]
            );

            Route::post(
                '/solicitudes/{id}/mensajes',
                [
                    MensajeViajeController::class,
                    'store',
                ]
            )->middleware(
                'throttle:30,1'
            );

            Route::post(
                '/solicitudes/{id}/mensajes/leidos',
                [
                    MensajeViajeController::class,
                    'marcarLeidos',
                ]
            );

            Route::get(
                '/solicitudes/{id}/incidencias',
                [
                    IncidenciaViajeController::class,
                    'indexViaje',
                ]
            );

            Route::post(
                '/solicitudes/{id}/incidencias',
                [
                    IncidenciaViajeController::class,
                    'store',
                ]
            )->middleware(
                'throttle:6,1'
            );
        });

    /*
    |--------------------------------------------------------------------------
    | PASAJERO
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:pasajero'
    )
        ->prefix('pasajero')
        ->group(function () {
            Route::post(
                '/solicitudes',
                [
                    SolicitudController::class,
                    'storePasajero',
                ]
            );

            Route::get(
                '/solicitudes',
                [
                    SolicitudController::class,
                    'misSolicitudes',
                ]
            );

            Route::get(
                '/solicitudes/{id}',
                [
                    SolicitudController::class,
                    'showPasajero',
                ]
            );

            Route::get(
                '/viaje-activo',
                [
                    SolicitudController::class,
                    'viajeActivoPasajero',
                ]
            );

            Route::post(
                '/solicitudes/{id}/cancelar',
                [
                    SolicitudController::class,
                    'cancelarPasajero',
                ]
            );

            Route::get(
                '/ultimo-viaje-finalizado',
                [
                    SolicitudController::class,
                    'ultimoViajeFinalizadoPasajero',
                ]
            );

            Route::post(
                '/solicitudes/{id}/calificar',
                [
                    SolicitudController::class,
                    'calificarViajePasajero',
                ]
            );

            /*
             * Chat conservado en backend, pero oculto
             * temporalmente en la interfaz.
             */
            Route::get(
                '/solicitudes/{id}/mensajes',
                [
                    MensajeViajeController::class,
                    'index',
                ]
            );

            Route::post(
                '/solicitudes/{id}/mensajes',
                [
                    MensajeViajeController::class,
                    'store',
                ]
            )->middleware(
                'throttle:30,1'
            );

            Route::post(
                '/solicitudes/{id}/mensajes/leidos',
                [
                    MensajeViajeController::class,
                    'marcarLeidos',
                ]
            );

            Route::get(
                '/solicitudes/{id}/incidencias',
                [
                    IncidenciaViajeController::class,
                    'indexViaje',
                ]
            );

            Route::post(
                '/solicitudes/{id}/incidencias',
                [
                    IncidenciaViajeController::class,
                    'store',
                ]
            )->middleware(
                'throttle:6,1'
            );
        });

    /*
    |--------------------------------------------------------------------------
    | PERSONAS
    |--------------------------------------------------------------------------
    |
    | Admin general:
    |   todas las personas.
    |
    | Secretario:
    |   personas de su sindicato.
    |
    | Admin servicios:
    |   clientes/pasajeros y personas libres para registrar clientes.
    |
    */

    Route::middleware(
        'role:admin_general,secretario,admin_servicios'
    )->group(function () {
        Route::get(
            '/personas',
            [
                PersonaController::class,
                'index',
            ]
        );

        Route::post(
            '/personas',
            [
                PersonaController::class,
                'store',
            ]
        );

        Route::get(
            '/personas/buscar/{ci}',
            [
                PersonaController::class,
                'buscarPorCi',
            ]
        );

        Route::get(
            '/personas/{id}',
            [
                PersonaController::class,
                'show',
            ]
        );

        Route::put(
            '/personas/{id}',
            [
                PersonaController::class,
                'update',
            ]
        );

        Route::delete(
            '/personas/{id}',
            [
                PersonaController::class,
                'destroy',
            ]
        );

        Route::post(
            '/imagen/subir',
            [
                ImagenController::class,
                'subirImagen',
            ]
        );

        Route::post(
            '/imagen/persona/registrar',
            [
                ImagenController::class,
                'registrarPersona',
            ]
        );

        Route::post(
            '/personas/{id}/imagen',
            [
                ImagenController::class,
                'agregarImagenPersona',
            ]
        );

        Route::delete(
            '/imagenes-personas/{id}',
            [
                ImagenController::class,
                'destroy',
            ]
        );
    });

    /*
    |--------------------------------------------------------------------------
    | CONSULTA OPERATIVA DE MOTOTAXISTAS
    |--------------------------------------------------------------------------
    |
    | Admin general y secretario conservan su acceso habitual.
    | El administrador de servicios puede consultar la lista de conductores
    | para asignaciones y control operativo, pero no puede crear, editar,
    | eliminar, cambiar afiliación, generar QR ni crear cuentas de conductor.
    |
    */

    Route::middleware(
        'role:admin_general,secretario,admin_servicios'
    )->group(function () {
        Route::get(
            '/mototaxistas',
            [
                MototaxistaController::class,
                'index',
            ]
        );
    });

    /*
    |--------------------------------------------------------------------------
    | MÓDULO SINDICAL
    | Admin general + secretario del sindicato
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:admin_general,secretario'
    )->group(function () {
        Route::post(
            '/mototaxistas',
            [
                MototaxistaController::class,
                'store',
            ]
        );

        Route::get(
            '/mototaxistas/{id}',
            [
                MototaxistaController::class,
                'show',
            ]
        );

        Route::put(
            '/mototaxistas/{id}',
            [
                MototaxistaController::class,
                'update',
            ]
        );

        Route::delete(
            '/mototaxistas/{id}',
            [
                MototaxistaController::class,
                'destroy',
            ]
        );

        Route::post(
            '/mototaxistas/{id}/cambiar-estado',
            [
                MototaxistaController::class,
                'cambiarEstado',
            ]
        );

        Route::post(
            '/mototaxistas/{id}/generar-qr',
            [
                MototaxistaController::class,
                'generarQr',
            ]
        );

        Route::post(
            '/mototaxistas/{id}/cuenta-conductor',
            [
                MototaxistaController::class,
                'crearCuentaConductor',
            ]
        );

        Route::get(
            '/motocicletas',
            [
                MotocicletaController::class,
                'index',
            ]
        );

        Route::post(
            '/motocicletas',
            [
                MotocicletaController::class,
                'store',
            ]
        );

        Route::get(
            '/motocicletas/{id}',
            [
                MotocicletaController::class,
                'show',
            ]
        );

        Route::put(
            '/motocicletas/{id}',
            [
                MotocicletaController::class,
                'update',
            ]
        );

        Route::delete(
            '/motocicletas/{id}',
            [
                MotocicletaController::class,
                'destroy',
            ]
        );

        Route::get(
            '/sindicatos',
            [
                SindicatoController::class,
                'index',
            ]
        );

        Route::get(
            '/sindicatos/{id}',
            [
                SindicatoController::class,
                'show',
            ]
        );

        Route::put(
            '/sindicatos/{id}',
            [
                SindicatoController::class,
                'update',
            ]
        );

        Route::post(
            '/sindicatos/{id}/logo',
            [
                SindicatoController::class,
                'subirLogo',
            ]
        );

        /*
         * El secretario puede consultar únicamente
         * la federación a la que pertenece su sindicato.
         */
        Route::get(
            '/federaciones',
            [
                FederacionController::class,
                'index',
            ]
        );

        Route::get(
            '/federaciones/{id}',
            [
                FederacionController::class,
                'show',
            ]
        );
    });

    /*
    |--------------------------------------------------------------------------
    | PAGOS SINDICALES
    | Admin general + secretario de sindicato
    |--------------------------------------------------------------------------
    |
    | Son independientes de /pagos, que corresponde a los viajes.
    | El secretario queda limitado en backend a su propio sindicato.
    |
    */

    Route::middleware(
        'role:admin_general,secretario'
    )->group(function () {
        Route::get(
            '/pagos-sindicales',
            [
                PagoSindicalController::class,
                'index',
            ]
        );

        Route::post(
            '/pagos-sindicales',
            [
                PagoSindicalController::class,
                'store',
            ]
        );

        Route::put(
            '/pagos-sindicales/{id}',
            [
                PagoSindicalController::class,
                'update',
            ]
        );

        Route::post(
            '/pagos-sindicales/{id}/anular',
            [
                PagoSindicalController::class,
                'anular',
            ]
        );
    });

    /*
    |--------------------------------------------------------------------------
    | INCIDENCIAS
    | Admin general + secretario de sindicato
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:admin_general,secretario'
    )->group(function () {
        Route::get(
            '/incidencias',
            [
                IncidenciaViajeController::class,
                'indexAdmin',
            ]
        );

        Route::get(
            '/incidencias/{id}',
            [
                IncidenciaViajeController::class,
                'showAdmin',
            ]
        );

        Route::put(
            '/incidencias/{id}/estado',
            [
                IncidenciaViajeController::class,
                'actualizarEstadoAdmin',
            ]
        );
    });

    /*
    |--------------------------------------------------------------------------
    | ADMINISTRADOR GENERAL
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:admin_general'
    )->group(function () {
        Route::post(
            '/federaciones',
            [
                FederacionController::class,
                'store',
            ]
        );

        Route::put(
            '/federaciones/{id}',
            [
                FederacionController::class,
                'update',
            ]
        );

        Route::delete(
            '/federaciones/{id}',
            [
                FederacionController::class,
                'destroy',
            ]
        );

        Route::post(
            '/federaciones/{id}/logo',
            [
                FederacionController::class,
                'subirLogo',
            ]
        );

        Route::post(
            '/sindicatos',
            [
                SindicatoController::class,
                'store',
            ]
        );

        Route::delete(
            '/sindicatos/{id}',
            [
                SindicatoController::class,
                'destroy',
            ]
        );

        Route::get(
            '/usuarios',
            [
                UsuarioController::class,
                'index',
            ]
        );

        Route::post(
            '/usuarios',
            [
                UsuarioController::class,
                'store',
            ]
        );

        Route::get(
            '/usuarios/{id}',
            [
                UsuarioController::class,
                'show',
            ]
        );

        Route::put(
            '/usuarios/{id}',
            [
                UsuarioController::class,
                'update',
            ]
        );

        Route::delete(
            '/usuarios/{id}',
            [
                UsuarioController::class,
                'destroy',
            ]
        );


        /*
         * Cuenta de acceso del pasajero.
         *
         * El perfil del pasajero se administra en el módulo de
         * servicios, pero únicamente el Administrador General puede
         * crear credenciales de inicio de sesión.
         */
        Route::post(
            '/pasajeros/{id}/cuenta-pasajero',
            [
                PasajeroController::class,
                'crearCuentaPasajero',
            ]
        );
    });

    /*
    |--------------------------------------------------------------------------
    | MÓDULO DE SERVICIOS
    | Admin general + administrador de servicios
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:admin_general,admin_servicios'
    )->group(function () {
        Route::get(
            '/pasajeros',
            [
                PasajeroController::class,
                'index',
            ]
        );

        Route::post(
            '/pasajeros',
            [
                PasajeroController::class,
                'store',
            ]
        );

        Route::get(
            '/pasajeros/{id}',
            [
                PasajeroController::class,
                'show',
            ]
        );

        Route::put(
            '/pasajeros/{id}',
            [
                PasajeroController::class,
                'update',
            ]
        );

        Route::delete(
            '/pasajeros/{id}',
            [
                PasajeroController::class,
                'destroy',
            ]
        );

        Route::get(
            '/solicitudes',
            [
                SolicitudController::class,
                'index',
            ]
        );

        Route::post(
            '/solicitudes',
            [
                SolicitudController::class,
                'store',
            ]
        );

        Route::get(
            '/solicitudes/{id}',
            [
                SolicitudController::class,
                'show',
            ]
        );

        Route::put(
            '/solicitudes/{id}',
            [
                SolicitudController::class,
                'update',
            ]
        );

        Route::delete(
            '/solicitudes/{id}',
            [
                SolicitudController::class,
                'destroy',
            ]
        );

        Route::get(
            '/solicitudes/{id}/conductores-disponibles',
            [
                SolicitudController::class,
                'conductoresDisponiblesAsignacion',
            ]
        );

        Route::post(
            '/solicitudes/{id}/asignar-manualmente',
            [
                SolicitudController::class,
                'asignarManualmente',
            ]
        );

        /*
         * No se expone el chat administrativo
         * mientras la función esté oculta.
         */

        Route::get(
            '/servicios',
            [
                ServicioController::class,
                'index',
            ]
        );

        Route::post(
            '/servicios',
            [
                ServicioController::class,
                'store',
            ]
        );

        Route::get(
            '/servicios/{id}',
            [
                ServicioController::class,
                'show',
            ]
        );

        Route::put(
            '/servicios/{id}',
            [
                ServicioController::class,
                'update',
            ]
        );

        Route::delete(
            '/servicios/{id}',
            [
                ServicioController::class,
                'destroy',
            ]
        );

        Route::get(
            '/pagos',
            [
                PagoController::class,
                'index',
            ]
        );

        Route::post(
            '/pagos',
            [
                PagoController::class,
                'store',
            ]
        );

        Route::get(
            '/pagos/{id}',
            [
                PagoController::class,
                'show',
            ]
        );

        Route::put(
            '/pagos/{id}',
            [
                PagoController::class,
                'update',
            ]
        );

        Route::delete(
            '/pagos/{id}',
            [
                PagoController::class,
                'destroy',
            ]
        );

        Route::get(
            '/reportes/dashboard',
            [
                ReporteController::class,
                'obtenerDatosDashboard',
            ]
        );
    });
});
