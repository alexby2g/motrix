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
                return Mototaxista::find($solicitud->mototaxista_id);
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
            $conexionMinima = Carbon::now('UTC')
                ->subMinutes(self::MINUTOS_CONEXION_VALIDA)
                ->format('Y-m-d H:i:s');
            $ahoraUtc = Carbon::now('UTC')->format('Y-m-d H:i:s');

            $formula = $this->formulaHaversine(
                'mototaxistas.latitud',
                'mototaxistas.longitud'
            );

            $conductor = Mototaxista::query()
                ->select('mototaxistas.*')
                ->selectRaw(
                    "$formula AS distancia_recogida_km",
                    [$latitud, $longitud, $latitud]
                )
                ->where('mototaxistas.disponible', 1)
                ->whereNotNull('mototaxistas.latitud')
                ->whereNotNull('mototaxistas.longitud')
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
                ->having(
                    'distancia_recogida_km',
                    '<=',
                    self::RADIO_MAXIMO_KM
                )
                ->orderBy('distancia_recogida_km')
                ->orderBy('mototaxistas.id')
                ->lockForUpdate()
                ->first();

            if (!$conductor) {
                return null;
            }

            $solicitud->mototaxista_id = $conductor->id;
            $solicitud->save();

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
            $formula = $this->formulaHaversine(
                'latitud_origen',
                'longitud_origen'
            );

            $candidatas = Solicitud::query()
                ->select('solicitudes.*')
                ->selectRaw(
                    "$formula AS distancia_recogida_km",
                    [$latitud, $longitud, $latitud]
                )
                ->whereNull('mototaxista_id')
                ->whereIn('estado', ['Pendiente', 'Buscando conductor'])
                ->whereNotNull('latitud_origen')
                ->whereNotNull('longitud_origen')
                ->where(function ($query) use ($ahoraUtc) {
                    $query
                        ->whereNull('expira_en')
                        ->orWhere('expira_en', '>', $ahoraUtc);
                })
                ->having(
                    'distancia_recogida_km',
                    '<=',
                    self::RADIO_MAXIMO_KM
                )
                ->orderBy('distancia_recogida_km')
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
                $solicitud->save();

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
            $solicitud->save();

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

    public function olvidarRechazos(int $solicitudId): void
    {
        Cache::forget($this->claveRechazos($solicitudId));
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
