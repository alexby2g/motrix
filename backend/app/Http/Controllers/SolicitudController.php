<?php

namespace App\Http\Controllers;

use App\Events\SolicitudActualizada;
use App\Events\SolicitudCreada;
use App\Http\Requests\SolicitudRequest;
use App\Models\Mototaxista;
use App\Models\Pago;
use App\Models\Servicio;
use App\Models\Solicitud;
use App\Services\AsignacionConductorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitudController extends Controller
{
    public function __construct(
        private readonly AsignacionConductorService $asignacionService
    ) {
    }

    /**
     * Las solicitudes nuevas estarán disponibles durante 15 minutos.
     */
    private const MINUTOS_EXPIRACION = 15;

    /**
     * Tarifa oficial del pasajero.
     *
     * La tarifa se calcula nuevamente en el backend para que el
     * cliente web/móvil no pueda alterar directamente el precio
     * enviado en la petición.
     */
    private function calcularTarifaPasajero(
        float $distanciaKm
    ): float {
        $hora = Carbon::now(
            'America/La_Paz'
        )->hour;

        if ($hora >= 22 || $hora < 6) {
            return 15.00;
        }

        if ($distanciaKm <= 1.2) {
            return 5.00;
        }

        if ($distanciaKm <= 2.8) {
            return 8.00;
        }

        return 10.00;
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIONES ADMINISTRATIVAS
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $consulta = Solicitud::query()
            ->with([
                'pasajero.persona',
                'mototaxista.persona',
            ]);

        /*
         * El administrador de servicios puede abrir el expediente
         * de un pasajero concreto sin recibir solicitudes ajenas.
         */
        if ($request->filled('id_pasajero')) {
            $request->validate([
                'id_pasajero' => [
                    'integer',
                    'exists:pasajeros,id',
                ],
            ]);

            $consulta->where(
                'id_pasajero',
                (int) $request->input(
                    'id_pasajero'
                )
            );
        }

        return $consulta
            ->orderByDesc('id')
            ->get();
    }

    /**
     * El administrador crea una solicitud indicando el pasajero.
     */
    public function store(SolicitudRequest $request)
    {
        $datos = $request->validated();

        $datos['estado'] = 'Pendiente';
        $datos['mototaxista_id'] = null;
        $datos['motivo_cancelacion'] = null;
        $datos['expira_en'] = Carbon::now('UTC')
            ->addMinutes(self::MINUTOS_EXPIRACION)
            ->format('Y-m-d H:i:s');

        $solicitud = Solicitud::create($datos);

        $this->asignacionService
            ->asignarConductorMasCercano($solicitud);

        $solicitud->load([
            'pasajero.persona',
            'mototaxista.persona',
        ]);

        broadcast(
            new SolicitudCreada($solicitud)
        )->toOthers();

        return response()->json($solicitud, 201);
    }

    public function show($id)
    {
        return Solicitud::with([
            'pasajero.persona',
            'mototaxista.persona',
        ])->findOrFail($id);
    }

    public function update(SolicitudRequest $request, $id)
    {
        $solicitud = Solicitud::findOrFail($id);

        $solicitud->update(
            $request->validated()
        );

        $solicitud->load([
            'pasajero.persona',
            'mototaxista.persona',
        ]);

        broadcast(
            new SolicitudActualizada(
                $solicitud,
                'actualizacion_admin'
            )
        )->toOthers();

        return response()->json(
            $solicitud,
            200
        );
    }

    public function destroy($id)
    {
        $solicitud = Solicitud::findOrFail($id);

        $solicitud->delete();

        return response()->json([
            'mensaje' => 'Solicitud eliminada correctamente.',
        ]);
    }


    /**
     * Lista los conductores que pueden recibir manualmente
     * una solicitud pendiente.
     */
    public function conductoresDisponiblesAsignacion($id)
    {
        $solicitud = Solicitud::query()
            ->findOrFail($id);

        if (!in_array(
            $solicitud->estado,
            [
                'Pendiente',
                'Buscando conductor',
            ],
            true
        )) {
            return response()->json([
                'mensaje' => (
                    'Solo se puede asignar un conductor '
                    . 'a una solicitud pendiente.'
                ),
            ], 409);
        }

        $conductores = Mototaxista::query()
            ->with([
                'persona',
                'sindicato',
            ])
            ->where('disponible', 1)
            ->whereNotExists(
                function ($query) use ($solicitud) {
                    $query
                        ->select(DB::raw(1))
                        ->from('solicitudes')
                        ->whereColumn(
                            'solicitudes.mototaxista_id',
                            'mototaxistas.id'
                        )
                        ->where(
                            'solicitudes.id',
                            '<>',
                            $solicitud->id
                        )
                        ->whereIn(
                            'solicitudes.estado',
                            [
                                'Pendiente',
                                'Buscando conductor',
                                'Aceptado',
                                'Llegó',
                                'En Curso',
                            ]
                        );
                }
            )
            ->get()
            ->map(function (
                Mototaxista $mototaxista
            ) use ($solicitud) {
                $distancia = null;

                if (
                    $mototaxista->latitud !== null
                    && $mototaxista->longitud !== null
                    && $solicitud->latitud_origen !== null
                    && $solicitud->longitud_origen !== null
                ) {
                    $distancia =
                        $this->calcularDistanciaKm(
                            (float) $mototaxista->latitud,
                            (float) $mototaxista->longitud,
                            (float) $solicitud->latitud_origen,
                            (float) $solicitud->longitud_origen
                        );
                }

                $minutosSinConexion = null;

                if ($mototaxista->ultima_conexion) {
                    try {
                        $minutosSinConexion = Carbon::parse(
                            $mototaxista->ultima_conexion,
                            'UTC'
                        )->diffInMinutes(
                            Carbon::now('UTC')
                        );
                    } catch (\Throwable) {
                        $minutosSinConexion = null;
                    }
                }

                $estadoGps = 'sin_ubicacion';

                if (
                    $mototaxista->latitud !== null
                    && $mototaxista->longitud !== null
                ) {
                    if (
                        $minutosSinConexion !== null
                        && $minutosSinConexion <= 3
                    ) {
                        $estadoGps = 'actualizado';
                    } elseif (
                        $minutosSinConexion !== null
                        && $minutosSinConexion <= 10
                    ) {
                        $estadoGps = 'atencion';
                    } else {
                        $estadoGps = 'desactualizado';
                    }
                }

                $mototaxista->setAttribute(
                    'distancia_recogida_km',
                    $distancia
                );

                $mototaxista->setAttribute(
                    'minutos_sin_conexion',
                    $minutosSinConexion
                );

                $mototaxista->setAttribute(
                    'estado_gps',
                    $estadoGps
                );

                return $mototaxista;
            })
            ->sort(function ($a, $b) {
                $distanciaA =
                    $a->distancia_recogida_km;

                $distanciaB =
                    $b->distancia_recogida_km;

                if (
                    $distanciaA === null
                    && $distanciaB === null
                ) {
                    return 0;
                }

                if ($distanciaA === null) {
                    return 1;
                }

                if ($distanciaB === null) {
                    return -1;
                }

                return $distanciaA <=> $distanciaB;
            })
            ->values();

        return response()->json([
            'solicitud' => $solicitud->load([
                'pasajero.persona',
                'mototaxista.persona',
            ]),

            'conductores' => $conductores,
        ], 200);
    }

    /**
     * Reserva manualmente una solicitud pendiente
     * para el conductor elegido por el administrador.
     *
     * El conductor todavía debe aceptar el viaje.
     */
    public function asignarManualmente(
        Request $request,
        $id
    ) {
        $datos = $request->validate([
            'mototaxista_id' => [
                'required',
                'integer',
                'exists:mototaxistas,id',
            ],
        ]);

        $solicitud = DB::transaction(
            function () use (
                $id,
                $datos
            ) {
                $solicitud = Solicitud::query()
                    ->lockForUpdate()
                    ->findOrFail($id);

                if (!in_array(
                    $solicitud->estado,
                    [
                        'Pendiente',
                        'Buscando conductor',
                    ],
                    true
                )) {
                    abort(
                        409,
                        'La solicitud ya no está pendiente.'
                    );
                }

                if (
                    $solicitud->expira_en
                    && Carbon::parse(
                        $solicitud->expira_en,
                        'UTC'
                    )->lessThanOrEqualTo(
                        Carbon::now('UTC')
                    )
                ) {
                    $solicitud->estado = 'Expirado';
                    $solicitud->save();

                    abort(
                        409,
                        'La solicitud ya expiró.'
                    );
                }

                $mototaxista = Mototaxista::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        (int) $datos['mototaxista_id']
                    );

                if (!(bool) $mototaxista->disponible) {
                    abort(
                        409,
                        'El conductor seleccionado no está disponible.'
                    );
                }

                $tieneCompromiso = Solicitud::query()
                    ->where(
                        'mototaxista_id',
                        $mototaxista->id
                    )
                    ->where(
                        'id',
                        '<>',
                        $solicitud->id
                    )
                    ->whereIn(
                        'estado',
                        [
                            'Pendiente',
                            'Buscando conductor',
                            'Aceptado',
                            'Llegó',
                            'En Curso',
                        ]
                    )
                    ->exists();

                if ($tieneCompromiso) {
                    abort(
                        409,
                        (
                            'El conductor ya tiene una '
                            . 'solicitud o viaje activo.'
                        )
                    );
                }

                $solicitud->mototaxista_id =
                    $mototaxista->id;

                $solicitud->estado =
                    'Buscando conductor';

                $solicitud->motivo_cancelacion =
                    null;

                $solicitud->expira_en =
                    Carbon::now('UTC')
                        ->addMinutes(
                            self::MINUTOS_EXPIRACION
                        )
                        ->format('Y-m-d H:i:s');

                $solicitud->save();

                $this->asignacionService
                    ->olvidarRechazos(
                        $solicitud->id
                    );

                return $solicitud->load([
                    'pasajero.persona',
                    'mototaxista.persona',
                ]);
            }
        );

        broadcast(
            new SolicitudCreada($solicitud)
        )->toOthers();

        return response()->json([
            'mensaje' => (
                'Solicitud asignada al conductor. '
                . 'Debe aceptar el viaje desde su panel.'
            ),

            'solicitud' => $solicitud,
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIONES PRIVADAS DEL PASAJERO
    |--------------------------------------------------------------------------
    */

    /**
     * Crear una solicitud utilizando automáticamente el pasajero
     * vinculado a la cuenta autenticada.
     */
    public function storePasajero(Request $request)
    {
        $pasajeroId = (int) (
            $request->user()?->pasajero_id ?? 0
        );

        if ($pasajeroId <= 0) {
            return response()->json([
                'mensaje' => 'La cuenta no está vinculada a un pasajero.',
            ], 403);
        }

        $datos = $request->validate([
            'origen' => [
                'required',
                'string',
                'max:150',
            ],

            'latitud_origen' => [
                'required',
                'numeric',
                'between:-90,90',
            ],

            'longitud_origen' => [
                'required',
                'numeric',
                'between:-180,180',
            ],

            'destino' => [
                'required',
                'string',
                'max:150',
            ],

            'latitud_destino' => [
                'required',
                'numeric',
                'between:-90,90',
            ],

            'longitud_destino' => [
                'required',
                'numeric',
                'between:-180,180',
            ],

            'fecha' => [
                'nullable',
                'date',
            ],

            'precio' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'distancia_km' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'metodo_pago' => [
                'nullable',
                'string',
                'in:Efectivo,QR,Transferencia / QR',
            ],
        ]);

        /*
         * La tarifa que llega desde el frontend es solamente visual.
         * El backend impone nuevamente la tarifa oficial antes de
         * guardar la solicitud.
         */
        $datos['precio'] = $this->calcularTarifaPasajero(
            (float) $datos['distancia_km']
        );

        /*
         * Antes de crear otra solicitud, se marcan como expiradas
         * las búsquedas antiguas cuyo tiempo ya terminó.
         */
        $this->marcarSolicitudesExpiradas(
            $pasajeroId
        );

        /*
         * El pasajero solamente puede tener una solicitud
         * pendiente, aceptada o en curso al mismo tiempo.
         */
        $solicitudActiva = Solicitud::query()
            ->where('id_pasajero', $pasajeroId)
            ->whereIn('estado', [
                'Pendiente',
                'Buscando conductor',
                'Aceptado',
                'Llegó',
                'En Curso',
            ])
            ->orderByDesc('id')
            ->first();

        if ($solicitudActiva) {
            return response()->json([
                'mensaje' => 'Ya tienes una solicitud o viaje activo.',
                'solicitud' => $solicitudActiva->load([
                    'pasajero.persona',
                    'mototaxista.persona',
                ]),
            ], 409);
        }

        $datos['id_pasajero'] = $pasajeroId;
        $datos['fecha'] = $datos['fecha']
            ?? Carbon::now('America/La_Paz')
                ->format('Y-m-d');

        $datos['estado'] = 'Pendiente';
        $datos['mototaxista_id'] = null;
        $datos['motivo_cancelacion'] = null;

        $datos['expira_en'] = Carbon::now('UTC')
            ->addMinutes(self::MINUTOS_EXPIRACION)
            ->format('Y-m-d H:i:s');

        $solicitud = Solicitud::create($datos);

        /*
         * Utiliza la misma asignación automática que el
         * módulo administrativo.
         */
        $this->asignacionService
            ->asignarConductorMasCercano($solicitud);

        $solicitud->load([
            'pasajero.persona',
            'mototaxista.persona',
        ]);

        broadcast(
            new SolicitudCreada($solicitud)
        )->toOthers();

        return response()->json([
            'mensaje' => 'Solicitud creada correctamente.',
            'solicitud' => $solicitud,
        ], 201);
    }

    /**
     * Historial exclusivo del pasajero autenticado.
     */
    public function misSolicitudes(Request $request)
    {
        $pasajeroId = (int) (
            $request->user()?->pasajero_id ?? 0
        );

        if ($pasajeroId <= 0) {
            return response()->json([
                'mensaje' => 'La cuenta no está vinculada a un pasajero.',
            ], 403);
        }

        $this->marcarSolicitudesExpiradas(
            $pasajeroId
        );

        $solicitudes = Solicitud::query()
            ->with([
                'pasajero.persona',
                'mototaxista.persona',
            ])
            ->where('id_pasajero', $pasajeroId)
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $solicitudes,
            200
        );
    }

    /**
     * Consultar una solicitud siempre que pertenezca
     * al pasajero autenticado.
     */
    public function showPasajero(Request $request, $id)
    {
        $pasajeroId = (int) (
            $request->user()?->pasajero_id ?? 0
        );

        if ($pasajeroId <= 0) {
            return response()->json([
                'mensaje' => 'La cuenta no está vinculada a un pasajero.',
            ], 403);
        }

        $solicitud = Solicitud::query()
            ->with([
                'pasajero.persona',
                'mototaxista.persona',
            ])
            ->where('id', $id)
            ->where('id_pasajero', $pasajeroId)
            ->first();

        if (!$solicitud) {
            return response()->json([
                'mensaje' => 'La solicitud no existe o no te pertenece.',
            ], 404);
        }

        return response()->json(
            $solicitud,
            200
        );
    }

    /**
     * Obtener la solicitud actual del pasajero.
     */
    public function viajeActivoPasajero(Request $request)
    {
        $pasajeroId = (int) (
            $request->user()?->pasajero_id ?? 0
        );

        if ($pasajeroId <= 0) {
            return response()->json([
                'mensaje' => 'La cuenta no está vinculada a un pasajero.',
            ], 403);
        }

        $this->marcarSolicitudesExpiradas(
            $pasajeroId
        );

        $solicitud = Solicitud::query()
            ->with([
                'pasajero.persona',
                'mototaxista.persona',
            ])
            ->where('id_pasajero', $pasajeroId)
            ->whereIn('estado', [
                'Pendiente',
                'Buscando conductor',
                'Aceptado',
                'Llegó',
                'En Curso',
            ])
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'solicitud' => $solicitud,
        ], 200);
    }

    /**
     * Obtener el último viaje finalizado que todavía no fue calificado
     * por el pasajero autenticado.
     */
    public function ultimoViajeFinalizadoPasajero(
        Request $request
    ) {
        $pasajeroId = (int) (
            $request->user()?->pasajero_id ?? 0
        );

        if ($pasajeroId <= 0) {
            return response()->json([
                'mensaje' => 'La cuenta no está vinculada a un pasajero.',
            ], 403);
        }

        $solicitud = Solicitud::query()
            ->with([
                'pasajero.persona',
                'mototaxista.persona',
            ])
            ->where('id_pasajero', $pasajeroId)
            ->where('estado', 'Finalizado')
            ->whereNull('calificacion')
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'solicitud' => $solicitud,
        ], 200);
    }

    /**
     * Guardar una única calificación para un viaje finalizado
     * que pertenezca al pasajero autenticado.
     */
    public function calificarViajePasajero(
        Request $request,
        $id
    ) {
        $pasajeroId = (int) (
            $request->user()?->pasajero_id ?? 0
        );

        if ($pasajeroId <= 0) {
            return response()->json([
                'mensaje' => 'La cuenta no está vinculada a un pasajero.',
            ], 403);
        }

        $datos = $request->validate([
            'calificacion' => [
                'required',
                'integer',
                'between:1,5',
            ],

            'comentario_calificacion' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        return DB::transaction(
            function () use (
                $id,
                $pasajeroId,
                $datos
            ) {
                $solicitud = Solicitud::query()
                    ->where('id', $id)
                    ->where(
                        'id_pasajero',
                        $pasajeroId
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$solicitud) {
                    return response()->json([
                        'mensaje' => (
                            'El viaje no existe '
                            . 'o no pertenece al pasajero.'
                        ),
                    ], 404);
                }

                if ($solicitud->estado !== 'Finalizado') {
                    return response()->json([
                        'mensaje' => (
                            'Solo puedes calificar '
                            . 'un viaje finalizado.'
                        ),
                    ], 409);
                }

                if ($solicitud->calificacion !== null) {
                    return response()->json([
                        'mensaje' => 'Este viaje ya fue calificado.',
                    ], 409);
                }

                if (!$solicitud->mototaxista_id) {
                    return response()->json([
                        'mensaje' => (
                            'El viaje no tiene '
                            . 'un conductor asignado.'
                        ),
                    ], 409);
                }

                $solicitud->calificacion =
                    (int) $datos['calificacion'];

                $solicitud->comentario_calificacion = (
                    isset($datos['comentario_calificacion'])
                    && trim(
                        (string) $datos[
                            'comentario_calificacion'
                        ]
                    ) !== ''
                )
                    ? trim(
                        (string) $datos[
                            'comentario_calificacion'
                        ]
                    )
                    : null;

                $solicitud->calificado_en = Carbon::now(
                    'America/La_Paz'
                )->format('Y-m-d H:i:s');

                $solicitud->save();

                $promedio = (float) (
                    Solicitud::query()
                        ->where(
                            'mototaxista_id',
                            $solicitud->mototaxista_id
                        )
                        ->where('estado', 'Finalizado')
                        ->whereNotNull('calificacion')
                        ->avg('calificacion')
                    ?? 0
                );

                $totalCalificaciones = Solicitud::query()
                    ->where(
                        'mototaxista_id',
                        $solicitud->mototaxista_id
                    )
                    ->where('estado', 'Finalizado')
                    ->whereNotNull('calificacion')
                    ->count();

                return response()->json([
                    'mensaje' => 'Calificación registrada correctamente.',

                    'solicitud' => $solicitud->load([
                        'pasajero.persona',
                        'mototaxista.persona',
                    ]),

                    'promedio_mototaxista' =>
                        round($promedio, 2),

                    'total_calificaciones' =>
                        $totalCalificaciones,
                ], 200);
            }
        );
    }

    /**
     * El pasajero puede cancelar su solicitud propia
     * mientras el viaje todavía no haya comenzado.
     */
    public function cancelarPasajero(
        Request $request,
        $id
    ) {
        $pasajeroId = (int) (
            $request->user()?->pasajero_id ?? 0
        );

        if ($pasajeroId <= 0) {
            return response()->json([
                'mensaje' => 'La cuenta no está vinculada a un pasajero.',
            ], 403);
        }

        $datos = $request->validate([
            'motivo_cancelacion' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        return DB::transaction(
            function () use (
                $id,
                $pasajeroId,
                $datos
            ) {
                $solicitud = Solicitud::query()
                    ->where('id', $id)
                    ->where(
                        'id_pasajero',
                        $pasajeroId
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$solicitud) {
                    return response()->json([
                        'mensaje' => 'La solicitud no existe o no te pertenece.',
                    ], 404);
                }

                if (!in_array(
                    $solicitud->estado,
                    [
                        'Pendiente',
                        'Buscando conductor',
                        'Aceptado',
                        'Llegó',
                    ],
                    true
                )) {
                    return response()->json([
                        'mensaje' => (
                            'La solicitud ya no puede cancelarse '
                            . 'porque el viaje comenzó o finalizó.'
                        ),
                    ], 409);
                }

                $solicitud->estado = 'Cancelado';

                $solicitud->motivo_cancelacion = (
                    $datos['motivo_cancelacion']
                    ?? 'Cancelado por el pasajero'
                );

                $solicitud->save();

                /*
                 * Si el conductor ya había aceptado,
                 * se cierra el servicio relacionado.
                 */
                $servicio = Servicio::query()
                    ->where(
                        'id_solicitud',
                        $solicitud->id
                    )
                    ->first();

                if ($servicio) {
                    $servicio->estado = 'Cancelado';

                    if (!$servicio->hora_fin) {
                        $servicio->hora_fin = Carbon::now(
                            'America/La_Paz'
                        )->format('H:i:s');
                    }

                    $servicio->save();
                }

                /*
                 * El mototaxista vuelve a estar disponible.
                 */
                if ($solicitud->mototaxista_id) {
                    $mototaxista = Mototaxista::find(
                        $solicitud->mototaxista_id
                    );

                    if ($mototaxista) {
                        $mototaxista->disponible = 1;
                        $mototaxista->save();
                    }
                }

                $this->asignacionService
                    ->olvidarRechazos($solicitud->id);

                $solicitud->load([
                    'pasajero.persona',
                    'mototaxista.persona',
                ]);

                broadcast(
                    new SolicitudActualizada(
                        $solicitud,
                        'cancelado_por_pasajero'
                    )
                )->toOthers();

                return response()->json([
                    'mensaje' => 'Solicitud cancelada correctamente.',
                    'solicitud' => $solicitud,
                ], 200);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIONES DEL CONDUCTOR
    |--------------------------------------------------------------------------
    */

    /**
     * Asignación segura del viaje al conductor reservado.
     */
    public function aceptar(
        Request $request,
        $id
    ) {
        $datos = $request->validate([
            'mototaxista_id' => [
                'nullable',
                'integer',
                'exists:mototaxistas,id',
            ],
        ]);

        $mototaxistaId =
            $this->resolverMototaxistaIdSeguro(
                $request,
                $datos['mototaxista_id'] ?? null
            );

        return DB::transaction(
            function () use (
                $mototaxistaId,
                $id
            ) {
                $solicitud = Solicitud::query()
                    ->lockForUpdate()
                    ->findOrFail($id);

                $mototaxista = Mototaxista::query()
                    ->lockForUpdate()
                    ->findOrFail($mototaxistaId);

                if (
                    $solicitud->mototaxista_id !== null
                    && (int) $solicitud->mototaxista_id
                        !== (int) $mototaxista->id
                ) {
                    return response()->json([
                        'mensaje' => (
                            'La solicitud está reservada '
                            . 'para otro conductor.'
                        ),
                    ], 403);
                }

                if (!in_array(
                    $solicitud->estado,
                    [
                        'Pendiente',
                        'Buscando conductor',
                    ],
                    true
                )) {
                    return response()->json([
                        'mensaje' =>
                            'La solicitud ya no está disponible.',
                    ], 409);
                }

                if (
                    $solicitud->expira_en
                    && Carbon::parse(
                        $solicitud->expira_en,
                        'UTC'
                    )->lessThanOrEqualTo(
                        Carbon::now('UTC')
                    )
                ) {
                    $solicitud->estado = 'Expirado';
                    $solicitud->save();

                    return response()->json([
                        'mensaje' =>
                            'La solicitud ya expiró.',
                    ], 409);
                }

                $tieneViajeActivo = Solicitud::query()
                    ->where(
                        'mototaxista_id',
                        $mototaxista->id
                    )
                    ->whereIn(
                        'estado',
                        [
                            'Aceptado',
                            'Llegó',
                            'En Curso',
                        ]
                    )
                    ->exists();

                if ($tieneViajeActivo) {
                    return response()->json([
                        'mensaje' => (
                            'El conductor ya tiene '
                            . 'un viaje activo.'
                        ),
                    ], 409);
                }

                $solicitud->estado = 'Aceptado';
                $solicitud->mototaxista_id =
                    $mototaxista->id;
                $solicitud->save();

                $mototaxista->disponible = 0;
                $mototaxista->save();

                Servicio::firstOrCreate(
                    [
                        'id_solicitud' =>
                            $solicitud->id,
                    ],
                    [
                        'hora_inicio' =>
                            Carbon::now(
                                'America/La_Paz'
                            )->format('H:i:s'),

                        'hora_fin' => null,
                        'estado' => 'Activo',

                        'id_mototaxista' =>
                            $mototaxista->id,
                    ]
                );

                $this->asignacionService
                    ->olvidarRechazos(
                        $solicitud->id
                    );

                $solicitud->load([
                    'pasajero.persona',
                    'mototaxista.persona',
                ]);

                broadcast(
                    new SolicitudActualizada(
                        $solicitud,
                        'conductor_acepto'
                    )
                )->toOthers();

                return response()->json(
                    $solicitud,
                    200
                );
            }
        );
    }

    /**
     * Rechazar la reserva y enviarla al siguiente
     * conductor cercano.
     */
    public function rechazar(
        Request $request,
        $id
    ) {
        $datos = $request->validate([
            'mototaxista_id' => [
                'nullable',
                'integer',
                'exists:mototaxistas,id',
            ],
        ]);

        $mototaxistaId =
            $this->resolverMototaxistaIdSeguro(
                $request,
                $datos['mototaxista_id'] ?? null
            );

        $solicitud = Solicitud::query()
            ->findOrFail($id);

        if (
            $solicitud->mototaxista_id !== null
            && (int) $solicitud->mototaxista_id
                !== $mototaxistaId
        ) {
            return response()->json([
                'mensaje' => (
                    'No puedes rechazar una solicitud '
                    . 'asignada a otro conductor.'
                ),
            ], 403);
        }

        if (!in_array(
            $solicitud->estado,
            [
                'Pendiente',
                'Buscando conductor',
            ],
            true
        )) {
            return response()->json([
                'mensaje' =>
                    'La solicitud ya no está disponible.',
            ], 409);
        }

        $resultado = $this->asignacionService
            ->rechazarYReasignar(
                (int) $id,
                $mototaxistaId
            );

        $solicitudActualizada = Solicitud::query()
            ->with([
                'pasajero.persona',
                'mototaxista.persona',
            ])
            ->findOrFail($id);

        broadcast(
            new SolicitudActualizada(
                $solicitudActualizada,
                $resultado['conductor'] !== null
                    ? 'conductor_reasignado'
                    : 'conductor_rechazo'
            )
        )->toOthers();

        return response()->json([
            'mensaje' => $resultado['mensaje'],

            'reasignado' =>
                $resultado['conductor'] !== null,

            'nuevo_conductor' =>
                $resultado['conductor']
                    ? $resultado['conductor']
                        ->load('persona')
                    : null,

        ], $resultado['ok'] ? 200 : 409);
    }

    /**
     * Cambiar el estado del viaje.
     */
    public function actualizarEstado(
        Request $request,
        $id
    ) {
        $datos = $request->validate([
            'estado' => [
                'required',
                'string',
                'in:Llegó,En Curso,Finalizado,Cancelado',
            ],

            'mototaxista_id' => [
                'nullable',
                'integer',
                'exists:mototaxistas,id',
            ],

            'metodo_pago' => [
                'nullable',
                'string',
                'in:Efectivo,QR,Transferencia / QR',
            ],

            'motivo_cancelacion' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $mototaxistaId =
            $this->resolverMototaxistaIdSeguro(
                $request,
                $datos['mototaxista_id'] ?? null
            );

        return DB::transaction(
            function () use (
                $datos,
                $id,
                $mototaxistaId
            ) {
                $solicitud = Solicitud::query()
                    ->lockForUpdate()
                    ->findOrFail($id);

                if (
                    (int) $solicitud->mototaxista_id
                    !== $mototaxistaId
                ) {
                    return response()->json([
                        'mensaje' => (
                            'No puedes modificar un viaje '
                            . 'asignado a otro conductor.'
                        ),
                    ], 403);
                }

                $nuevoEstado = $datos['estado'];

                $transicionesPermitidas = [
                    'Aceptado' => [
                        'Llegó',
                        'Cancelado',
                    ],

                    'Llegó' => [
                        'En Curso',
                        'Cancelado',
                    ],

                    'En Curso' => [
                        'Finalizado',
                        'Cancelado',
                    ],
                ];

                $permitidos = $transicionesPermitidas[
                    $solicitud->estado
                ] ?? [];

                if (!in_array(
                    $nuevoEstado,
                    $permitidos,
                    true
                )) {
                    return response()->json([
                        'mensaje' => (
                            'No se puede cambiar el viaje de '
                            . $solicitud->estado
                            . ' a '
                            . $nuevoEstado
                            . '.'
                        ),
                    ], 409);
                }

                $solicitud->estado = $nuevoEstado;

                if (
                    array_key_exists(
                        'metodo_pago',
                        $datos
                    )
                ) {
                    $solicitud->metodo_pago =
                        $datos['metodo_pago'];
                }

                if ($nuevoEstado === 'Cancelado') {
                    $solicitud->motivo_cancelacion = (
                        $datos['motivo_cancelacion']
                        ?? 'Cancelado por el conductor'
                    );
                }

                $solicitud->save();

                if (in_array(
                    $nuevoEstado,
                    [
                        'Llegó',
                        'En Curso',
                    ],
                    true
                )) {
                    Servicio::query()
                        ->where(
                            'id_solicitud',
                            $solicitud->id
                        )
                        ->where(
                            'id_mototaxista',
                            $mototaxistaId
                        )
                        ->whereNull('hora_fin')
                        ->update([
                            'estado' => 'Activo',
                        ]);
                }

                if (in_array(
                    $nuevoEstado,
                    [
                        'Finalizado',
                        'Cancelado',
                    ],
                    true
                )) {
                    $servicio = Servicio::query()
                        ->where(
                            'id_solicitud',
                            $solicitud->id
                        )
                        ->where(
                            'id_mototaxista',
                            $mototaxistaId
                        )
                        ->whereNull('hora_fin')
                        ->first();

                    if ($servicio) {
                        $servicio->hora_fin =
                            Carbon::now(
                                'America/La_Paz'
                            )->format('H:i:s');

                        $servicio->estado =
                            $nuevoEstado;
                        $servicio->save();
                    }

                    if (
                        $nuevoEstado === 'Finalizado'
                        && $servicio
                    ) {
                        $monto = (float) str_replace(
                            [
                                'Bs.',
                                ' ',
                                ',',
                            ],
                            [
                                '',
                                '',
                                '.',
                            ],
                            (string) $solicitud->precio
                        );

                        if ($monto <= 0) {
                            $monto = 8.00;
                        }

                        Pago::updateOrCreate(
                            [
                                'id_servicio' =>
                                    $servicio->id,
                            ],
                            [
                                'monto' => $monto,

                                'metodo' => (
                                    $datos['metodo_pago']
                                    ?? $solicitud
                                        ->metodo_pago
                                    ?? 'Efectivo'
                                ),

                                'estado' =>
                                    'Completado',
                            ]
                        );
                    }

                    $mototaxista = Mototaxista::find(
                        $mototaxistaId
                    );

                    if ($mototaxista) {
                        $mototaxista->disponible = 1;
                        $mototaxista->save();
                    }
                }

                $solicitud->load([
                    'pasajero.persona',
                    'mototaxista.persona',
                ]);

                $tipoEvento = match ($nuevoEstado) {
                    'Llegó' => 'conductor_llego',
                    'En Curso' => 'viaje_iniciado',
                    'Finalizado' => 'viaje_finalizado',
                    'Cancelado' => 'cancelado_por_conductor',
                    default => 'estado_actualizado',
                };

                broadcast(
                    new SolicitudActualizada(
                        $solicitud,
                        $tipoEvento
                    )
                )->toOthers();

                return response()->json(
                    $solicitud,
                    200
                );
            }
        );
    }

    /**
     * Ganancias e historial del conductor.
     */
    public function gananciasConductor(
        Request $request,
        $mototaxistaId = null
    ) {
        $mototaxistaIdSeguro =
            $this->resolverMototaxistaIdSeguro(
                $request,
                $mototaxistaId
            );

        $mototaxista = Mototaxista::findOrFail(
            $mototaxistaIdSeguro
        );

        $pagos = Pago::query()
            ->join(
                'servicios',
                'pagos.id_servicio',
                '=',
                'servicios.id'
            )
            ->join(
                'solicitudes',
                'servicios.id_solicitud',
                '=',
                'solicitudes.id'
            )
            ->where(
                'servicios.id_mototaxista',
                $mototaxista->id
            )
            ->where(
                'servicios.estado',
                'Finalizado'
            )
            ->where(
                'pagos.estado',
                'Completado'
            )
            ->select(
                'pagos.id',
                'pagos.monto',
                'pagos.metodo',

                'solicitudes.id as solicitud_id',
                'solicitudes.fecha',
                'solicitudes.origen',
                'solicitudes.destino',
                'solicitudes.distancia_km',

                'solicitudes.calificacion',
                'solicitudes.comentario_calificacion',
                'solicitudes.calificado_en'
            )
            ->orderByDesc('pagos.id')
            ->get();

        $efectivo = 0.0;
        $digital = 0.0;

        foreach ($pagos as $pago) {
            if ($pago->metodo === 'Efectivo') {
                $efectivo += (float) $pago->monto;
            } else {
                $digital += (float) $pago->monto;
            }

            $pago->calificacion = (
                $pago->calificacion !== null
            )
                ? (int) $pago->calificacion
                : null;
        }

        $consultaCalificaciones = Solicitud::query()
            ->where(
                'mototaxista_id',
                $mototaxista->id
            )
            ->where(
                'estado',
                'Finalizado'
            )
            ->whereNotNull(
                'calificacion'
            );

        $promedioCalificacion = (float) (
            (clone $consultaCalificaciones)
                ->avg('calificacion')
            ?? 0
        );

        $totalCalificaciones = (
            clone $consultaCalificaciones
        )->count();

        $comentariosRecientes = (
            clone $consultaCalificaciones
        )
            ->whereNotNull(
                'comentario_calificacion'
            )
            ->where(
                'comentario_calificacion',
                '<>',
                ''
            )
            ->select(
                'id',
                'calificacion',
                'comentario_calificacion',
                'calificado_en'
            )
            ->orderByDesc(
                'calificado_en'
            )
            ->limit(10)
            ->get();

        return response()->json([
            'viajes_totales' =>
                $pagos->count(),

            'ganancia_efectivo' =>
                round($efectivo, 2),

            'ganancia_qr' =>
                round($digital, 2),

            'total_recaudado' =>
                round(
                    $efectivo + $digital,
                    2
                ),

            'promedio_calificacion' =>
                round(
                    $promedioCalificacion,
                    2
                ),

            'total_calificaciones' =>
                $totalCalificaciones,

            'detalles_pagos' =>
                $pagos,

            'comentarios_recientes' =>
                $comentariosRecientes,
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIONES INTERNAS
    |--------------------------------------------------------------------------
    */

    /**
     * Distancia aproximada entre dos coordenadas,
     * utilizando la fórmula de Haversine.
     */
    private function calcularDistanciaKm(
        float $latitudOrigen,
        float $longitudOrigen,
        float $latitudDestino,
        float $longitudDestino
    ): float {
        $radioTierra = 6371;

        $dLat = deg2rad(
            $latitudDestino - $latitudOrigen
        );

        $dLng = deg2rad(
            $longitudDestino - $longitudOrigen
        );

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($latitudOrigen))
            * cos(deg2rad($latitudDestino))
            * sin($dLng / 2) ** 2;

        return round(
            $radioTierra
                * 2
                * atan2(
                    sqrt($a),
                    sqrt(1 - $a)
                ),
            2
        );
    }

    /**
     * Obtiene el mototaxista permitido para la operación.
     *
     * El conductor siempre utiliza el mototaxista_id de su sesión.
     * El administrador puede indicar un ID para realizar pruebas
     * o consultas administrativas.
     */
    private function resolverMototaxistaIdSeguro(
        Request $request,
        $idSolicitado = null
    ): int {
        $usuario = $request->user();

        if (!$usuario) {
            abort(
                401,
                'Debes iniciar sesión.'
            );
        }

        $rol = strtolower(
            trim(
                (string) $usuario->role
            )
        );

        if ($rol === 'conductor') {
            $idSesion = (int) (
                $usuario->mototaxista_id ?? 0
            );

            if ($idSesion <= 0) {
                abort(
                    403,
                    'La cuenta no está vinculada a un mototaxista.'
                );
            }

            if (
                $idSolicitado !== null
                && (int) $idSolicitado !== $idSesion
            ) {
                abort(
                    403,
                    'No puedes realizar acciones como otro conductor.'
                );
            }

            return $idSesion;
        }

        if (in_array(
            $rol,
            [
                'admin_general',
                'admin_servicios',
            ],
            true
        )) {
            $idAdmin = (int) (
                $idSolicitado ?? 0
            );

            if ($idAdmin <= 0) {
                abort(
                    422,
                    'Debes indicar el mototaxista para esta operación administrativa.'
                );
            }

            return $idAdmin;
        }

        abort(
            403,
            'No tienes autorización para realizar esta acción.'
        );
    }

    /**
     * Cambia a Expirado las solicitudes pendientes
     * cuyo límite de tiempo ya terminó.
     */
    private function marcarSolicitudesExpiradas(
        int $pasajeroId
    ): void {
        Solicitud::query()
            ->where(
                'id_pasajero',
                $pasajeroId
            )
            ->whereIn(
                'estado',
                [
                    'Pendiente',
                    'Buscando conductor',
                ]
            )
            ->whereNotNull('expira_en')
            ->where(
                'expira_en',
                '<=',
                Carbon::now('UTC')
                    ->format('Y-m-d H:i:s')
            )
            ->update([
                'estado' => 'Expirado',
            ]);
    }
}