<?php

namespace App\Services;

use App\Models\Mototaxista;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AsignacionConductorService
{
    private const RADIO_MAXIMO_KM = 8.0;
    private const MINUTOS_CONEXION_VALIDA = 3;
    private const MINUTOS_BLOQUEO_RECHAZO = 15;
    private const SEGUNDOS_RESPUESTA_CONDUCTOR = 30;
    private const MINUTOS_SEGUIMIENTO_ASIGNACION = 5;

    public function asignarConductorMasCercano(
        Solicitud|int $solicitud,
        array $excluir = []
    ): ?Mototaxista {
        $id = $solicitud instanceof Solicitud
            ? $solicitud->id
            : $solicitud;

        return DB::transaction(function () use ($id, $excluir) {
            $solicitud = Solicitud::query()
                ->lockForUpdate()
                ->findOrFail($id);

            if ($solicitud->mototaxista_id !== null) {
                $mototaxistaId = (int) $solicitud->mototaxista_id;

                $this->asegurarSeguimientoAsignacion(
                    $solicitud->id,
                    $mototaxistaId
                );

                return Mototaxista::find($mototaxistaId);
            }

            if (!in_array(
                $solicitud->estado,
                ['Pendiente', 'Buscando conductor'],
                true
            )) {
                return null;
            }

            if (
                $solicitud->latitud_origen === null
                || $solicitud->longitud_origen === null
            ) {
                return null;
            }

            $excluidos = array_values(array_unique(array_map(
                'intval',
                array_merge(
                    $excluir,
                    $this->conductoresRechazados($solicitud->id)
                )
            )));

            $latitud = (float) $solicitud->latitud_origen;
            $longitud = (float) $solicitud->longitud_origen;
            $caja = $this->cajaBusqueda(
                $latitud,
                $longitud
            );
            $conexionMinima = Carbon::now('UTC')
                ->subMinutes(self::MINUTOS_CONEXION_VALIDA)
                ->format('Y-m-d H:i:s');
            $ahoraUtc = Carbon::now('UTC')->format('Y-m-d H:i:s');

            $formula = $this->formulaHaversine(
                'mototaxistas.latitud',
                'mototaxistas.longitud'
            );
            $bindingsDistancia = [
                $latitud,
                $longitud,
                $latitud,
            ];

            /*
             * PostgreSQL no permite usar el alias calculado
             * "distancia_recogida_km" dentro de HAVING como lo hace MySQL.
             * Filtramos y ordenamos usando directamente la fórmula para que
             * la asignación automática funcione de forma nativa en PostgreSQL.
             */
            $conductor = Mototaxista::query()
                ->select('mototaxistas.*')
                ->where('mototaxistas.estado', 'Activo')
                ->where('mototaxistas.documentacion_en_regla', 1)
                ->where('mototaxistas.aportes_al_dia', 1)
                ->where('mototaxistas.estado_sindical', 'Habilitado')
                ->where('mototaxistas.disponible', 1)
                ->whereExists(function ($query) {
                    $query
                        ->selectRaw('1')
                        ->from('users as cuenta_conductor')
                        ->whereColumn(
                            'cuenta_conductor.mototaxista_id',
                            'mototaxistas.id'
                        )
                        ->where('cuenta_conductor.role', 'conductor');
                })
                ->whereNotNull('mototaxistas.latitud')
                ->whereNotNull('mototaxistas.longitud')
                ->whereBetween(
                    'mototaxistas.latitud',
                    [
                        $caja['lat_min'],
                        $caja['lat_max'],
                    ]
                )
                ->whereBetween(
                    'mototaxistas.longitud',
                    [
                        $caja['lng_min'],
                        $caja['lng_max'],
                    ]
                )
                ->whereNotNull('mototaxistas.ultima_conexion')
                ->where('mototaxistas.ultima_conexion', '>=', $conexionMinima)
                ->when($excluidos, function ($query) use ($excluidos) {
                    $query->whereNotIn('mototaxistas.id', $excluidos);
                })
                ->whereNotExists(function ($query) use ($ahoraUtc) {
                    $query
                        ->selectRaw('1')
                        ->from('solicitudes as ocupadas')
                        ->whereColumn(
                            'ocupadas.mototaxista_id',
                            'mototaxistas.id'
                        )
                        ->where(function ($estados) use ($ahoraUtc) {
                            $estados
                                ->whereIn(
                                    'ocupadas.estado',
                                    ['Aceptado', 'Llegó', 'En Curso']
                                )
                                ->orWhere(function ($reservas) use ($ahoraUtc) {
                                    $reservas
                                        ->whereIn(
                                            'ocupadas.estado',
                                            ['Pendiente', 'Buscando conductor']
                                        )
                                        ->where(function ($vigentes) use ($ahoraUtc) {
                                            $vigentes
                                                ->whereNull('ocupadas.expira_en')
                                                ->orWhere(
                                                    'ocupadas.expira_en',
                                                    '>',
                                                    $ahoraUtc
                                                );
                                        });
                                });
                        });
                })
                ->whereRaw(
                    "$formula <= ?",
                    array_merge(
                        $bindingsDistancia,
                        [self::RADIO_MAXIMO_KM]
                    )
                )
                ->orderByRaw(
                    "$formula ASC",
                    $bindingsDistancia
                )
                ->orderBy('mototaxistas.id')
                ->lockForUpdate()
                ->first();

            if (!$conductor) {
                return null;
            }

            $solicitud->mototaxista_id = $conductor->id;
            $solicitud->estado = 'Buscando conductor';
            $solicitud->save();

            $this->guardarSeguimientoAsignacion(
                $solicitud->id,
                $conductor->id
            );

            return $conductor;
        });
    }

    public function asignarSolicitudMasCercanaAlConductor(
        Mototaxista|int $mototaxista
    ): ?Solicitud {
        $id = $mototaxista instanceof Mototaxista
            ? $mototaxista->id
            : $mototaxista;

        return DB::transaction(function () use ($id) {
            $conductor = Mototaxista::query()
                ->lockForUpdate()
                ->findOrFail($id);

            $conductor->loadMissing('usuarioConductor');

            if (
                ! $conductor->habilitadoSindicalmente()
                || $conductor->usuarioConductor === null
            ) {
                if ((bool) $conductor->disponible) {
                    $conductor->disponible = false;
                    $conductor->save();
                }

                return null;
            }

            if (
                !(bool) $conductor->disponible
                || $conductor->latitud === null
                || $conductor->longitud === null
            ) {
                return null;
            }

            $ahoraUtc = Carbon::now('UTC')->format('Y-m-d H:i:s');

            $ocupado = Solicitud::query()
                ->where('mototaxista_id', $conductor->id)
                ->where(function ($query) use ($ahoraUtc) {
                    $query
                        ->whereIn('estado', ['Aceptado', 'Llegó', 'En Curso'])
                        ->orWhere(function ($pendiente) use ($ahoraUtc) {
                            $pendiente
                                ->whereIn(
                                    'estado',
                                    ['Pendiente', 'Buscando conductor']
                                )
                                ->where(function ($vigente) use ($ahoraUtc) {
                                    $vigente
                                        ->whereNull('expira_en')
                                        ->orWhere('expira_en', '>', $ahoraUtc);
                                });
                        });
                })
                ->exists();

            if ($ocupado) {
                return null;
            }

            $latitud = (float) $conductor->latitud;
            $longitud = (float) $conductor->longitud;
            $caja = $this->cajaBusqueda(
                $latitud,
                $longitud
            );
            $formula = $this->formulaHaversine(
                'latitud_origen',
                'longitud_origen'
            );
            $bindingsDistancia = [
                $latitud,
                $longitud,
                $latitud,
            ];

            $candidatas = Solicitud::query()
                ->select('solicitudes.*')
                ->whereNull('mototaxista_id')
                ->whereIn('estado', ['Pendiente', 'Buscando conductor'])
                ->whereNotNull('latitud_origen')
                ->whereNotNull('longitud_origen')
                ->whereBetween(
                    'latitud_origen',
                    [
                        $caja['lat_min'],
                        $caja['lat_max'],
                    ]
                )
                ->whereBetween(
                    'longitud_origen',
                    [
                        $caja['lng_min'],
                        $caja['lng_max'],
                    ]
                )
                ->where(function ($query) use ($ahoraUtc) {
                    $query
                        ->whereNull('expira_en')
                        ->orWhere('expira_en', '>', $ahoraUtc);
                })
                ->whereRaw(
                    "$formula <= ?",
                    array_merge(
                        $bindingsDistancia,
                        [self::RADIO_MAXIMO_KM]
                    )
                )
                ->orderByRaw(
                    "$formula ASC",
                    $bindingsDistancia
                )
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($candidatas as $solicitud) {
                if (in_array(
                    $conductor->id,
                    $this->conductoresRechazados($solicitud->id),
                    true
                )) {
                    continue;
                }

                $solicitud->mototaxista_id = $conductor->id;
                $solicitud->estado = 'Buscando conductor';
                $solicitud->save();

                $this->guardarSeguimientoAsignacion(
                    $solicitud->id,
                    $conductor->id
                );

                return $solicitud;
            }

            return null;
        });
    }

    public function rechazarYReasignar(
        int $solicitudId,
        int $mototaxistaId
    ): array {
        return DB::transaction(function () use (
            $solicitudId,
            $mototaxistaId
        ) {
            $solicitud = Solicitud::query()
                ->lockForUpdate()
                ->findOrFail($solicitudId);

            if ((int) $solicitud->mototaxista_id !== $mototaxistaId) {
                return [
                    'ok' => false,
                    'mensaje' => 'La solicitud ya no está asignada a este conductor.',
                    'conductor' => null,
                ];
            }

            if (!in_array(
                $solicitud->estado,
                ['Pendiente', 'Buscando conductor'],
                true
            )) {
                return [
                    'ok' => false,
                    'mensaje' => 'La solicitud ya no puede rechazarse.',
                    'conductor' => null,
                ];
            }

            $this->registrarRechazo($solicitudId, $mototaxistaId);

            $solicitud->mototaxista_id = null;
            $solicitud->estado = 'Buscando conductor';
            $solicitud->save();

            $this->olvidarSeguimientoAsignacion($solicitudId);

            $nuevo = $this->asignarConductorMasCercano(
                $solicitudId,
                [$mototaxistaId]
            );

            return [
                'ok' => true,
                'mensaje' => $nuevo
                    ? 'Solicitud enviada al siguiente conductor más cercano.'
                    : 'Solicitud liberada. No hay otro conductor disponible.',
                'conductor' => $nuevo,
            ];
        });
    }

    /**
     * Revisa la reserva actual de una solicitud pendiente.
     *
     * Cada conductor dispone de 30 segundos para responder. Si el tiempo
     * vence, la reserva se libera y se intenta asignar al siguiente conductor
     * disponible. El lock de la solicitud evita competir con aceptar().
     */
    public function revisarAsignacionPendiente(int $solicitudId): array
    {
        return DB::transaction(function () use ($solicitudId) {
            $solicitud = Solicitud::query()
                ->lockForUpdate()
                ->findOrFail($solicitudId);

            if (!in_array(
                $solicitud->estado,
                ['Pendiente', 'Buscando conductor'],
                true
            )) {
                $this->olvidarSeguimientoAsignacion($solicitudId);

                return [
                    'cambio' => false,
                    'motivo' => 'estado_no_pendiente',
                    'conductor' => null,
                ];
            }

            $ahora = Carbon::now('UTC');

            if (
                $solicitud->expira_en
                && Carbon::parse(
                    $solicitud->expira_en,
                    'UTC'
                )->lessThanOrEqualTo($ahora)
            ) {
                $solicitud->estado = 'Expirado';
                $solicitud->save();

                $this->olvidarSeguimientoAsignacion($solicitudId);

                return [
                    'cambio' => true,
                    'motivo' => 'expirada',
                    'conductor' => null,
                ];
            }

            if ($solicitud->mototaxista_id === null) {
                $nuevo = $this->asignarConductorMasCercano($solicitudId);

                return [
                    'cambio' => $nuevo !== null,
                    'motivo' => $nuevo
                        ? 'conductor_asignado'
                        : 'sin_conductor_disponible',
                    'conductor' => $nuevo,
                ];
            }

            $mototaxistaId = (int) $solicitud->mototaxista_id;
            $seguimiento = Cache::get(
                $this->claveSeguimientoAsignacion($solicitudId)
            );

            if (
                !is_array($seguimiento)
                || (int) ($seguimiento['mototaxista_id'] ?? 0)
                    !== $mototaxistaId
                || !isset($seguimiento['asignado_en'])
            ) {
                $this->guardarSeguimientoAsignacion(
                    $solicitudId,
                    $mototaxistaId
                );

                return [
                    'cambio' => false,
                    'motivo' => 'temporizador_iniciado',
                    'conductor' => Mototaxista::find($mototaxistaId),
                ];
            }

            $asignadoEn = (int) $seguimiento['asignado_en'];
            $transcurridos = max(
                0,
                $ahora->timestamp - $asignadoEn
            );

            if ($transcurridos < self::SEGUNDOS_RESPUESTA_CONDUCTOR) {
                return [
                    'cambio' => false,
                    'motivo' => 'esperando_respuesta',
                    'conductor' => Mototaxista::find($mototaxistaId),
                ];
            }

            /*
             * El conductor no respondió dentro del tiempo. Se lo excluye de
             * esta solicitud y se libera la reserva antes de buscar al siguiente.
             */
            $this->registrarRechazo($solicitudId, $mototaxistaId);

            $solicitud->mototaxista_id = null;
            $solicitud->estado = 'Buscando conductor';
            $solicitud->save();

            $this->olvidarSeguimientoAsignacion($solicitudId);

            $nuevo = $this->asignarConductorMasCercano(
                $solicitudId,
                [$mototaxistaId]
            );

            return [
                'cambio' => true,
                'motivo' => $nuevo
                    ? 'timeout_reasignado'
                    : 'timeout_sin_conductor',
                'conductor' => $nuevo,
                'conductor_anterior_id' => $mototaxistaId,
            ];
        });
    }

    /**
     * Registra el inicio del tiempo de respuesta para una reserva manual.
     */
    public function registrarSeguimientoAsignacion(
        int $solicitudId,
        int $mototaxistaId
    ): void {
        $this->guardarSeguimientoAsignacion(
            $solicitudId,
            $mototaxistaId
        );
    }

    /**
     * Devuelve el tiempo de respuesta vigente para el conductor asignado.
     *
     * La fecha se expone en ISO 8601 con zona UTC para que el frontend no
     * dependa de la zona horaria local del dispositivo.
     */
    public function obtenerTiempoRespuestaAsignacion(
        int $solicitudId,
        int $mototaxistaId
    ): ?array {
        $seguimiento = Cache::get(
            $this->claveSeguimientoAsignacion($solicitudId)
        );

        if (
            !is_array($seguimiento)
            || (int) ($seguimiento['mototaxista_id'] ?? 0) !== $mototaxistaId
            || !isset($seguimiento['asignado_en'])
        ) {
            return null;
        }

        $asignadoEn = (int) $seguimiento['asignado_en'];
        $expiraEn = $asignadoEn + self::SEGUNDOS_RESPUESTA_CONDUCTOR;
        $ahora = Carbon::now('UTC')->timestamp;

        return [
            'asignado_en' => Carbon::createFromTimestamp(
                $asignadoEn,
                'UTC'
            )->toIso8601String(),
            'expira_en' => Carbon::createFromTimestamp(
                $expiraEn,
                'UTC'
            )->toIso8601String(),
            'segundos_totales' => self::SEGUNDOS_RESPUESTA_CONDUCTOR,
            'segundos_restantes' => max(0, $expiraEn - $ahora),
        ];
    }

    public function olvidarSeguimientoAsignacion(int $solicitudId): void
    {
        Cache::forget($this->claveSeguimientoAsignacion($solicitudId));
    }

    public function olvidarRechazos(int $solicitudId): void
    {
        Cache::forget($this->claveRechazos($solicitudId));
    }

    private function cajaBusqueda(
        float $latitud,
        float $longitud
    ): array {
        $deltaLatitud =
            self::RADIO_MAXIMO_KM / 111.32;

        $cosLatitud = max(
            abs(
                cos(
                    deg2rad($latitud)
                )
            ),
            0.10
        );

        $deltaLongitud =
            self::RADIO_MAXIMO_KM
            / (
                111.32
                * $cosLatitud
            );

        return [
            'lat_min' =>
                $latitud - $deltaLatitud,
            'lat_max' =>
                $latitud + $deltaLatitud,
            'lng_min' =>
                $longitud - $deltaLongitud,
            'lng_max' =>
                $longitud + $deltaLongitud,
        ];
    }

    private function formulaHaversine(
        string $columnaLatitud,
        string $columnaLongitud
    ): string {
        return "(
            6371 * ACOS(
                LEAST(
                    1,
                    GREATEST(
                        -1,
                        COS(RADIANS(?))
                        * COS(RADIANS($columnaLatitud))
                        * COS(RADIANS($columnaLongitud) - RADIANS(?))
                        + SIN(RADIANS(?))
                        * SIN(RADIANS($columnaLatitud))
                    )
                )
            )
        )";
    }

    private function asegurarSeguimientoAsignacion(
        int $solicitudId,
        int $mototaxistaId
    ): void {
        $seguimiento = Cache::get(
            $this->claveSeguimientoAsignacion($solicitudId)
        );

        if (
            is_array($seguimiento)
            && (int) ($seguimiento['mototaxista_id'] ?? 0)
                === $mototaxistaId
            && isset($seguimiento['asignado_en'])
        ) {
            return;
        }

        $this->guardarSeguimientoAsignacion(
            $solicitudId,
            $mototaxistaId
        );
    }

    private function guardarSeguimientoAsignacion(
        int $solicitudId,
        int $mototaxistaId
    ): void {
        Cache::put(
            $this->claveSeguimientoAsignacion($solicitudId),
            [
                'mototaxista_id' => $mototaxistaId,
                'asignado_en' => Carbon::now('UTC')->timestamp,
            ],
            Carbon::now('UTC')->addMinutes(
                self::MINUTOS_SEGUIMIENTO_ASIGNACION
            )
        );
    }

    private function claveSeguimientoAsignacion(int $solicitudId): string
    {
        return "motrix:solicitud:$solicitudId:asignacion";
    }

    private function registrarRechazo(
        int $solicitudId,
        int $mototaxistaId
    ): void {
        $rechazados = $this->conductoresRechazados($solicitudId);
        $rechazados[] = $mototaxistaId;

        Cache::put(
            $this->claveRechazos($solicitudId),
            array_values(array_unique(array_map('intval', $rechazados))),
            Carbon::now()->addMinutes(self::MINUTOS_BLOQUEO_RECHAZO)
        );
    }

    private function conductoresRechazados(int $solicitudId): array
    {
        $valor = Cache::get($this->claveRechazos($solicitudId), []);

        return is_array($valor)
            ? array_values(array_unique(array_map('intval', $valor)))
            : [];
    }

    private function claveRechazos(int $solicitudId): string
    {
        return "motrix:solicitud:$solicitudId:rechazados";
    }
}
