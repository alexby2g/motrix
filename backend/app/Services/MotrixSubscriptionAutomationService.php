<?php

namespace App\Services;

use App\Events\AlertaSuscripcionMotrixPublicada;
use App\Models\AlertaSuscripcionMotrix;
use App\Models\EjecucionAutomatizacionMotrix;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MotrixSubscriptionAutomationService
{
    private const LOCK_KEY =
        'motrix:suscripciones:automatizacion';

    private const ALERT_LOCK_KEY =
        'motrix:suscripciones:publicacion-alertas';

    public function __construct(
        private readonly MotrixSubscriptionBillingService $billingService
    ) {
    }

    public function ejecutar(
        string $origen = 'scheduler',
        ?int $userId = null
    ): array {
        if (! $this->tablaAuditoriaDisponible()) {
            return $this->resultadoSinMigracion();
        }

        try {
            $lock = Cache::lock(
                self::LOCK_KEY,
                600
            );

            if (! $lock->get()) {
                return [
                    'ocupada' => true,
                    'estado' => 'En ejecución',
                    'procesadas' => 0,
                    'alertas_creadas' => 0,
                    'alertas_enviadas' => 0,
                    'alertas_sin_usuario' => 0,
                    'errores' => 0,
                    'mensaje' =>
                        'Ya existe una sincronización de suscripciones MOTRIX en ejecución.',
                ];
            }
        } catch (Throwable $e) {
            Log::error(
                'No se pudo adquirir el bloqueo de automatización MOTRIX.',
                ['exception' => $e]
            );

            return [
                'ocupada' => false,
                'estado' => 'Fallida',
                'procesadas' => 0,
                'alertas_creadas' => 0,
                'alertas_enviadas' => 0,
                'alertas_sin_usuario' => 0,
                'errores' => 1,
                'mensaje' =>
                    'No se pudo iniciar la automatización MOTRIX de forma segura.',
            ];
        }

        try {
            return $this->ejecutarConAuditoria(
                $origen,
                $userId
            );
        } finally {
            $lock->release();
        }
    }

    public function publicarAlertasPendientes(): array
    {
        try {
            $lock = Cache::lock(
                self::ALERT_LOCK_KEY,
                120
            );

            if (! $lock->get()) {
                return [
                    'ocupada' => true,
                    'alertas_enviadas' => 0,
                    'alertas_sin_usuario' => 0,
                    'errores' => 0,
                ];
            }
        } catch (Throwable $e) {
            Log::warning(
                'No se pudo adquirir el bloqueo de publicación de alertas MOTRIX.',
                ['exception' => $e]
            );

            return [
                'ocupada' => false,
                'alertas_enviadas' => 0,
                'alertas_sin_usuario' => 0,
                'errores' => 1,
            ];
        }

        try {
            return $this->publicarAlertasPendientesSinLock();
        } finally {
            $lock->release();
        }
    }

    private function publicarAlertasPendientesSinLock(): array
    {
        $this->vincularUsuariosPendientes();

        $resultado = [
            'ocupada' => false,
            'alertas_enviadas' => 0,
            'alertas_sin_usuario' => 0,
            'errores' => 0,
        ];

        AlertaSuscripcionMotrix::query()
            ->where('estado', 'Pendiente')
            ->where('canal', 'panel')
            ->whereNotNull('id_mototaxista')
            ->where(function ($query) {
                $query
                    ->whereNull('programada_para')
                    ->orWhere(
                        'programada_para',
                        '<=',
                        now()
                    );
            })
            ->orderBy('id')
            ->chunkById(
                100,
                function ($alertas) use (&$resultado) {
                    foreach ($alertas as $alerta) {
                        if (! $alerta->user_id) {
                            $resultado[
                                'alertas_sin_usuario'
                            ]++;
                            continue;
                        }

                        try {
                            $alerta->update([
                                'estado' => 'Enviada',
                                'enviada_en' => now(),
                            ]);

                            event(
                                new AlertaSuscripcionMotrixPublicada(
                                    $alerta->fresh()
                                )
                            );

                            $resultado[
                                'alertas_enviadas'
                            ]++;
                        } catch (Throwable $e) {
                            $alerta->update([
                                'estado' => 'Pendiente',
                                'enviada_en' => null,
                            ]);

                            $resultado['errores']++;

                            Log::warning(
                                'No se pudo publicar una alerta de suscripción MOTRIX.',
                                [
                                    'alerta_id' => $alerta->id,
                                    'mototaxista_id' =>
                                        $alerta->id_mototaxista,
                                    'exception' => $e,
                                ]
                            );
                        }
                    }
                }
            );

        return $resultado;
    }

    public function estado(): array
    {
        if (! $this->tablaAuditoriaDisponible()) {
            return [
                'configurada' => false,
                'mensaje' =>
                    'Falta aplicar la migración de auditoría de automatización MOTRIX.',
                'ultima_ejecucion' => null,
                'ultima_exitosa' => null,
                'alertas' => [
                    'pendientes_publicar' => 0,
                    'pendientes_sin_usuario' => 0,
                    'no_leidas' => 0,
                ],
            ];
        }

        $pendientes = $this->queryAlertasPendientes();

        return [
            'configurada' => true,
            'programacion' => [
                'sincronizacion_diaria' => '00:10',
                'reintento_alertas_minutos' => 10,
                'zona_horaria' => 'America/La_Paz',
                'canal_tiempo_real' => 'Laravel Reverb',
                'notificaciones_push' => [
                    'arquitectura_preparada' => true,
                    'activas' => false,
                ],
            ],
            'ultima_ejecucion' =>
                EjecucionAutomatizacionMotrix::query()
                    ->latest('iniciada_en')
                    ->first(),
            'ultima_exitosa' =>
                EjecucionAutomatizacionMotrix::query()
                    ->whereIn(
                        'estado',
                        [
                            'Completada',
                            'Completada con errores',
                        ]
                    )
                    ->latest('finalizada_en')
                    ->first(),
            'alertas' => [
                'pendientes_publicar' =>
                    (clone $pendientes)->count(),
                'pendientes_sin_usuario' =>
                    (clone $pendientes)
                        ->whereNull('user_id')
                        ->count(),
                'no_leidas' =>
                    AlertaSuscripcionMotrix::query()
                        ->whereIn(
                            'estado',
                            ['Pendiente', 'Enviada']
                        )
                        ->count(),
            ],
        ];
    }

    private function ejecutarConAuditoria(
        string $origen,
        ?int $userId
    ): array {
        $ejecucion =
            EjecucionAutomatizacionMotrix::query()
                ->create([
                    'tipo' => 'suscripciones',
                    'origen' => substr(
                        trim($origen) ?: 'scheduler',
                        0,
                        30
                    ),
                    'ejecutado_por' => $userId,
                    'fecha_referencia' =>
                        now()->toDateString(),
                    'estado' => 'Ejecutando',
                    'iniciada_en' => now(),
                ]);

        try {
            $alertasAntes =
                AlertaSuscripcionMotrix::query()
                    ->count();

            $sincronizacion =
                $this->billingService
                    ->sincronizarTodas();

            $alertasDespues =
                AlertaSuscripcionMotrix::query()
                    ->count();

            $publicacion =
                $this->publicarAlertasPendientes();

            $errores =
                (int) ($sincronizacion['errores'] ?? 0)
                + (int) ($publicacion['errores'] ?? 0);

            $estado = $errores > 0
                ? 'Completada con errores'
                : 'Completada';

            $resultado = [
                'ocupada' => false,
                'ejecucion_id' => (int) $ejecucion->id,
                'estado' => $estado,
                'procesadas' =>
                    (int) ($sincronizacion['procesadas'] ?? 0),
                'alertas_creadas' => max(
                    0,
                    $alertasDespues - $alertasAntes
                ),
                'alertas_enviadas' =>
                    (int) $publicacion['alertas_enviadas'],
                'alertas_sin_usuario' =>
                    (int) $publicacion['alertas_sin_usuario'],
                'errores' => $errores,
            ];

            $ejecucion->update([
                'estado' => $estado,
                'procesadas' => $resultado['procesadas'],
                'alertas_creadas' =>
                    $resultado['alertas_creadas'],
                'alertas_enviadas' =>
                    $resultado['alertas_enviadas'],
                'alertas_sin_usuario' =>
                    $resultado['alertas_sin_usuario'],
                'errores' => $resultado['errores'],
                'detalle' => [
                    'sincronizacion' => $sincronizacion,
                    'publicacion' => $publicacion,
                ],
                'finalizada_en' => now(),
            ]);

            return $resultado;
        } catch (Throwable $e) {
            $ejecucion->update([
                'estado' => 'Fallida',
                'errores' => 1,
                'detalle' => [
                    'error' => $e->getMessage(),
                ],
                'finalizada_en' => now(),
            ]);

            Log::error(
                'Falló la automatización de suscripciones MOTRIX.',
                [
                    'ejecucion_id' => $ejecucion->id,
                    'exception' => $e,
                ]
            );

            return [
                'ocupada' => false,
                'ejecucion_id' => (int) $ejecucion->id,
                'estado' => 'Fallida',
                'procesadas' => 0,
                'alertas_creadas' => 0,
                'alertas_enviadas' => 0,
                'alertas_sin_usuario' => 0,
                'errores' => 1,
                'mensaje' =>
                    'La automatización MOTRIX terminó con un error. Revisa el log del servidor.',
            ];
        }
    }

    private function vincularUsuariosPendientes(): void
    {
        $idsMototaxista =
            $this->queryAlertasPendientes()
                ->whereNull('user_id')
                ->pluck('id_mototaxista')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

        if ($idsMototaxista->isEmpty()) {
            return;
        }

        $usuarios = User::query()
            ->where('role', 'conductor')
            ->whereIn(
                'mototaxista_id',
                $idsMototaxista
            )
            ->orderByDesc('id')
            ->get([
                'id',
                'mototaxista_id',
            ])
            ->unique(
                fn (User $user) =>
                    (int) $user->mototaxista_id
            )
            ->keyBy(
                fn (User $user) =>
                    (int) $user->mototaxista_id
            );

        foreach ($usuarios as $mototaxistaId => $usuario) {
            AlertaSuscripcionMotrix::query()
                ->where('estado', 'Pendiente')
                ->whereNull('user_id')
                ->where(
                    'id_mototaxista',
                    (int) $mototaxistaId
                )
                ->update([
                    'user_id' => (int) $usuario->id,
                ]);
        }
    }

    private function queryAlertasPendientes()
    {
        return AlertaSuscripcionMotrix::query()
            ->where('estado', 'Pendiente')
            ->where('canal', 'panel')
            ->whereNotNull('id_mototaxista')
            ->where(function ($query) {
                $query
                    ->whereNull('programada_para')
                    ->orWhere(
                        'programada_para',
                        '<=',
                        now()
                    );
            });
    }

    private function tablaAuditoriaDisponible(): bool
    {
        return Schema::hasTable(
            'ejecuciones_automatizacion_motrix'
        );
    }

    private function resultadoSinMigracion(): array
    {
        return [
            'ocupada' => false,
            'estado' => 'No configurada',
            'procesadas' => 0,
            'alertas_creadas' => 0,
            'alertas_enviadas' => 0,
            'alertas_sin_usuario' => 0,
            'errores' => 1,
            'mensaje' =>
                'Falta aplicar la migración V5.6 de automatización MOTRIX.',
        ];
    }
}
