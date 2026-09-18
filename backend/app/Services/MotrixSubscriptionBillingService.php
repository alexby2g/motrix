<?php

namespace App\Services;

use App\Models\AlertaSuscripcionMotrix;
use App\Models\PagoSuscripcionMotrix;
use App\Models\SuscripcionMotrix;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MotrixSubscriptionBillingService
{
    public const PAGO_PENDIENTE = 'Pendiente';
    public const PAGO_PAGADO = 'Pagado';
    public const PAGO_VENCIDO = 'Vencido';
    public const PAGO_EXONERADO = 'Exonerado';
    public const PAGO_ANULADO = 'Anulado';

    public const CANAL_SINDICATO = 'sindicato';
    public const CANAL_MOTRIX_DIRECTO = 'motrix_directo';

    public function __construct(
        private readonly MotrixSubscriptionService $subscriptionService
    ) {
    }

    public function asegurarCuotaInicial(
        SuscripcionMotrix $suscripcion
    ): PagoSuscripcionMotrix {
        $suscripcion->loadMissing('plan');

        $inicio = Carbon::parse(
            $suscripcion->fecha_inicio
        )->startOfDay();

        $vencimiento = Carbon::parse(
            $suscripcion->fecha_vencimiento
        )->startOfDay();

        return $this->crearCuotaSiNoExiste(
            $suscripcion,
            $inicio->format('Y-m'),
            $vencimiento
        );
    }

    public function asegurarRenovacion(
        SuscripcionMotrix $suscripcion
    ): PagoSuscripcionMotrix {
        $suscripcion->loadMissing('plan');

        $inicioSiguiente = Carbon::parse(
            $suscripcion->fecha_vencimiento
        )
            ->addDay()
            ->startOfDay();

        /*
         * La fecha límite para pagar la renovación es el vencimiento
         * de la suscripción actual. Al validarse el pago, el sistema
         * extiende la suscripción por la duración del plan.
         */
        $fechaLimitePago = Carbon::parse(
            $suscripcion->fecha_vencimiento
        )->startOfDay();

        return $this->crearCuotaSiNoExiste(
            $suscripcion,
            $inicioSiguiente->format('Y-m'),
            $fechaLimitePago
        );
    }

    public function debePrepararRenovacion(
        SuscripcionMotrix $suscripcion,
        CarbonInterface|string|null $fecha = null
    ): bool {
        $suscripcion->loadMissing('plan');

        $hoy = $fecha
            ? Carbon::parse($fecha)->startOfDay()
            : now()->startOfDay();

        $vencimiento = Carbon::parse(
            $suscripcion->fecha_vencimiento
        )->startOfDay();

        $diasAviso = max(
            0,
            (int) ($suscripcion->plan?->aviso_dias_antes ?? 7)
        );

        return $hoy->gte(
            $vencimiento
                ->copy()
                ->subDays($diasAviso)
        );
    }

    public function registrarPago(
        PagoSuscripcionMotrix $pago,
        array $datos,
        ?int $usuarioId
    ): PagoSuscripcionMotrix {
        return DB::transaction(
            function () use (
                $pago,
                $datos,
                $usuarioId
            ) {
                $pago = PagoSuscripcionMotrix::query()
                    ->lockForUpdate()
                    ->findOrFail($pago->id);

                if (
                    in_array(
                        strtolower(trim((string) $pago->estado)),
                        [
                            strtolower(self::PAGO_PAGADO),
                            strtolower(self::PAGO_EXONERADO),
                            strtolower(self::PAGO_ANULADO),
                        ],
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        'estado' =>
                            'Este periodo ya fue cerrado y no admite un nuevo pago.',
                    ]);
                }

                $montoEsperado = round(
                    (float) $pago->monto_esperado,
                    2
                );

                $montoPagado = round(
                    (float) ($datos['monto_pagado'] ?? 0),
                    2
                );

                if (
                    abs(
                        $montoEsperado
                        - $montoPagado
                    ) > 0.009
                ) {
                    throw ValidationException::withMessages([
                        'monto_pagado' =>
                            'El monto debe coincidir con la cuota pendiente de Bs. '
                            . number_format(
                                $montoEsperado,
                                2,
                                '.',
                                ''
                            )
                            . '.',
                    ]);
                }

                $suscripcion = SuscripcionMotrix::query()
                    ->with('plan')
                    ->lockForUpdate()
                    ->findOrFail(
                        $pago->suscripcion_id
                    );

                $pago->fill([
                    'monto_pagado' =>
                        $montoPagado,
                    'fecha_pago' =>
                        now(),
                    'estado' =>
                        self::PAGO_PAGADO,
                    'forma_pago' =>
                        $datos['forma_pago'],
                    'canal_cobro' =>
                        $datos['canal_cobro'],
                    'referencia_pago' =>
                        $datos['referencia_pago']
                        ?? null,
                    'comprobante_url' =>
                        $datos['comprobante_url']
                        ?? null,
                    'observacion' =>
                        $datos['observacion']
                        ?? null,
                    'registrado_por' =>
                        $usuarioId,
                    'validado_por' =>
                        $usuarioId,
                    'validado_en' =>
                        now(),
                ]);

                $pago->save();

                $periodoInicial = Carbon::parse(
                    $suscripcion->fecha_inicio
                )->format('Y-m');

                /*
                 * El pago del primer periodo confirma la cuota ya vigente.
                 * Un pago de un periodo posterior es una renovación y
                 * extiende la fecha de vencimiento.
                 */
                if (
                    $pago->periodo
                    !== $periodoInicial
                ) {
                    $inicioNuevoPeriodo =
                        Carbon::parse(
                            $suscripcion->fecha_vencimiento
                        )
                            ->addDay()
                            ->startOfDay();

                    $nuevoVencimiento =
                        $this->subscriptionService
                            ->proximoVencimiento(
                                $inicioNuevoPeriodo,
                                (int) (
                                    $suscripcion
                                        ->plan
                                        ?->duracion_meses
                                    ?? 1
                                )
                            )
                            ->subDay()
                            ->startOfDay();

                    $suscripcion->fecha_vencimiento =
                        $nuevoVencimiento
                            ->toDateString();
                }

                $suscripcion->estado =
                    MotrixSubscriptionService::ESTADO_ACTIVA;

                $suscripcion->actualizado_por =
                    $usuarioId;

                $suscripcion->suspendida_en =
                    null;

                $suscripcion->motivo_suspension =
                    null;

                $suscripcion->save();

                AlertaSuscripcionMotrix::query()
                    ->where(
                        'pago_suscripcion_id',
                        $pago->id
                    )
                    ->whereIn(
                        'estado',
                        [
                            'Pendiente',
                            'Enviada',
                        ]
                    )
                    ->update([
                        'estado' =>
                            'Cancelada',
                        'leida_en' =>
                            now(),
                        'updated_at' =>
                            now(),
                    ]);

                return $pago->fresh([
                    'suscripcion',
                    'mototaxista.persona',
                    'sindicato',
                ]);
            }
        );
    }

    public function sincronizarSuscripcion(
        SuscripcionMotrix $suscripcion,
        CarbonInterface|string|null $fecha = null
    ): array {
        $suscripcion->loadMissing([
            'plan',
            'mototaxista',
        ]);

        $hoy = $fecha
            ? Carbon::parse($fecha)->startOfDay()
            : now()->startOfDay();

        $cuotaInicial =
            $this->asegurarCuotaInicial(
                $suscripcion
            );

        $renovacion = null;

        if (
            ! $suscripcion->estaSuspendida()
            && $this->debePrepararRenovacion(
                $suscripcion,
                $hoy
            )
        ) {
            $renovacion =
                $this->asegurarRenovacion(
                    $suscripcion
                );
        }

        PagoSuscripcionMotrix::query()
            ->where(
                'suscripcion_id',
                $suscripcion->id
            )
            ->where(
                'estado',
                self::PAGO_PENDIENTE
            )
            ->whereDate(
                'fecha_vencimiento',
                '<',
                $hoy->toDateString()
            )
            ->update([
                'estado' =>
                    self::PAGO_VENCIDO,
                'updated_at' =>
                    now(),
            ]);

        if (! $suscripcion->estaSuspendida()) {
            $estadoCalculado =
                $this->subscriptionService
                    ->estadoActual(
                        $suscripcion,
                        $hoy
                    );

            if (
                $suscripcion->estado
                !== $estadoCalculado
            ) {
                $suscripcion->estado =
                    $estadoCalculado;

                $suscripcion->save();
            }
        }

        $pagoAviso = $renovacion
            ?? (
                in_array(
                    $cuotaInicial->estado,
                    [
                        self::PAGO_PENDIENTE,
                        self::PAGO_VENCIDO,
                    ],
                    true
                )
                    ? $cuotaInicial
                    : null
            );

        $alerta = null;

        if ($pagoAviso) {
            $alerta =
                $this->crearAlertaSiCorresponde(
                    $suscripcion,
                    $pagoAviso,
                    $hoy
                );
        }

        return [
            'suscripcion_id' =>
                (int) $suscripcion->id,
            'cuota_inicial_id' =>
                (int) $cuotaInicial->id,
            'renovacion_id' =>
                $renovacion
                    ? (int) $renovacion->id
                    : null,
            'alerta_id' =>
                $alerta
                    ? (int) $alerta->id
                    : null,
            'estado' =>
                $suscripcion->fresh()->estado,
        ];
    }

    public function sincronizarTodas(): array
    {
        $resultado = [
            'procesadas' => 0,
            'errores' => 0,
            'alertas_creadas' => 0,
        ];

        SuscripcionMotrix::query()
            ->with([
                'plan',
                'mototaxista',
            ])
            ->orderBy('id')
            ->chunkById(
                100,
                function ($suscripciones) use (
                    &$resultado
                ) {
                    foreach (
                        $suscripciones
                        as $suscripcion
                    ) {
                        try {
                            $sync =
                                $this->sincronizarSuscripcion(
                                    $suscripcion
                                );

                            $resultado[
                                'procesadas'
                            ]++;

                            if (
                                $sync['alerta_id']
                            ) {
                                $resultado[
                                    'alertas_creadas'
                                ]++;
                            }
                        } catch (\Throwable) {
                            $resultado[
                                'errores'
                            ]++;
                        }
                    }
                }
            );

        return $resultado;
    }

    private function crearCuotaSiNoExiste(
        SuscripcionMotrix $suscripcion,
        string $periodo,
        CarbonInterface $fechaLimitePago
    ): PagoSuscripcionMotrix {
        $suscripcion->loadMissing('plan');

        return PagoSuscripcionMotrix::query()
            ->firstOrCreate(
                [
                    'suscripcion_id' =>
                        $suscripcion->id,
                    'periodo' =>
                        $periodo,
                ],
                [
                    'id_mototaxista' =>
                        $suscripcion->id_mototaxista,
                    'id_sindicato' =>
                        $suscripcion->id_sindicato,
                    'monto_esperado' =>
                        $suscripcion->plan?->monto
                        ?? 0,
                    'monto_pagado' =>
                        0,
                    'fecha_vencimiento' =>
                        $fechaLimitePago
                            ->toDateString(),
                    'estado' =>
                        self::PAGO_PENDIENTE,
                    'canal_cobro' =>
                        self::CANAL_SINDICATO,
                ]
            );
    }

    private function crearAlertaSiCorresponde(
        SuscripcionMotrix $suscripcion,
        PagoSuscripcionMotrix $pago,
        CarbonInterface $hoy
    ): ?AlertaSuscripcionMotrix {
        if (
            ! in_array(
                $pago->estado,
                [
                    self::PAGO_PENDIENTE,
                    self::PAGO_VENCIDO,
                ],
                true
            )
        ) {
            return null;
        }

        $fechaLimite = Carbon::parse(
            $pago->fecha_vencimiento
        )->startOfDay();

        $dias = $hoy->diffInDays(
            $fechaLimite,
            false
        );

        $diasAviso = max(
            0,
            (int) (
                $suscripcion
                    ->plan
                    ?->aviso_dias_antes
                ?? 7
            )
        );

        if ($dias > $diasAviso) {
            return null;
        }

        [$tipo, $titulo, $mensaje] =
            $this->contenidoAlerta(
                $pago,
                $dias
            );

        $usuarioConductor =
            User::query()
                ->where(
                    'mototaxista_id',
                    $suscripcion->id_mototaxista
                )
                ->where(
                    'role',
                    'conductor'
                )
                ->first();

        return AlertaSuscripcionMotrix::query()
            ->firstOrCreate(
                [
                    'pago_suscripcion_id' =>
                        $pago->id,
                    'tipo' =>
                        $tipo,
                    'canal' =>
                        'panel',
                ],
                [
                    'suscripcion_id' =>
                        $suscripcion->id,
                    'id_mototaxista' =>
                        $suscripcion->id_mototaxista,
                    'id_sindicato' =>
                        $suscripcion->id_sindicato,
                    'user_id' =>
                        $usuarioConductor?->id,
                    'titulo' =>
                        $titulo,
                    'mensaje' =>
                        $mensaje,
                    'estado' =>
                        'Pendiente',
                    'programada_para' =>
                        now(),
                ]
            );
    }

    private function contenidoAlerta(
        PagoSuscripcionMotrix $pago,
        int $dias
    ): array {
        $monto = number_format(
            (float) $pago->monto_esperado,
            2,
            '.',
            ''
        );

        if ($dias < 0) {
            return [
                'suscripcion_vencida',
                'Suscripción MOTRIX vencida',
                "Tu suscripción MOTRIX tiene una cuota pendiente de Bs. {$monto}. Regulariza el pago para mantener el servicio activo.",
            ];
        }

        if ($dias === 0) {
            return [
                'vence_hoy',
                'Tu suscripción vence hoy',
                "La renovación MOTRIX de Bs. {$monto} vence hoy.",
            ];
        }

        if ($dias === 1) {
            return [
                'vence_manana',
                'Tu suscripción vence mañana',
                "Recuerda renovar MOTRIX. Tu cuota es de Bs. {$monto}.",
            ];
        }

        if ($dias <= 3) {
            return [
                'recordatorio_3d',
                'Tu suscripción vence pronto',
                "Faltan {$dias} días para renovar MOTRIX. Monto: Bs. {$monto}.",
            ];
        }

        return [
            'recordatorio_previo',
            'Recordatorio de suscripción MOTRIX',
            "Tu suscripción vence en {$dias} días. Monto de renovación: Bs. {$monto}.",
        ];
    }
}
