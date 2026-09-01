<?php

namespace App\Services;

use App\Models\LiquidacionMotrix;
use App\Models\LiquidacionPagoMotrix;
use App\Models\PagoSuscripcionMotrix;
use App\Models\TransferenciaLiquidacionMotrix;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MotrixLiquidationService
{
    public const LIQUIDACION_PENDIENTE = 'Pendiente';
    public const LIQUIDACION_PARCIAL = 'Parcial';
    public const LIQUIDACION_LIQUIDADA = 'Liquidada';

    public const TRANSFERENCIA_PENDIENTE = 'Pendiente';
    public const TRANSFERENCIA_VALIDADA = 'Validada';
    public const TRANSFERENCIA_OBSERVADA = 'Observada';

    public function resumenPeriodo(
        int $sindicatoId,
        string $periodo
    ): array {
        $base = PagoSuscripcionMotrix::query()
            ->where('id_sindicato', $sindicatoId)
            ->where('periodo', $periodo)
            ->where(
                'estado',
                MotrixSubscriptionBillingService::PAGO_PAGADO
            );

        $recaudadoSindicato = (float) (clone $base)
            ->where(
                'canal_cobro',
                MotrixSubscriptionBillingService::CANAL_SINDICATO
            )
            ->sum('monto_pagado');

        $pagosSindicato = (int) (clone $base)
            ->where(
                'canal_cobro',
                MotrixSubscriptionBillingService::CANAL_SINDICATO
            )
            ->count();

        $motrixDirecto = (float) (clone $base)
            ->where(
                'canal_cobro',
                MotrixSubscriptionBillingService::CANAL_MOTRIX_DIRECTO
            )
            ->sum('monto_pagado');

        $pagosDirectos = (int) (clone $base)
            ->where(
                'canal_cobro',
                MotrixSubscriptionBillingService::CANAL_MOTRIX_DIRECTO
            )
            ->count();

        $liquidacion = LiquidacionMotrix::query()
            ->where('id_sindicato', $sindicatoId)
            ->where('periodo', $periodo)
            ->first();

        $validado = 0.0;
        $pendienteValidacion = 0.0;
        $pagosIncluidos = 0;

        if ($liquidacion) {
            $validado = (float)
                TransferenciaLiquidacionMotrix::query()
                    ->where(
                        'liquidacion_id',
                        $liquidacion->id
                    )
                    ->where(
                        'estado',
                        self::TRANSFERENCIA_VALIDADA
                    )
                    ->sum('monto');

            $pendienteValidacion = (float)
                TransferenciaLiquidacionMotrix::query()
                    ->where(
                        'liquidacion_id',
                        $liquidacion->id
                    )
                    ->where(
                        'estado',
                        self::TRANSFERENCIA_PENDIENTE
                    )
                    ->sum('monto');

            $pagosIncluidos = (int)
                LiquidacionPagoMotrix::query()
                    ->where(
                        'liquidacion_id',
                        $liquidacion->id
                    )
                    ->count();
        }

        return [
            'id_sindicato' => $sindicatoId,
            'periodo' => $periodo,
            'recaudado_sindicato' =>
                $this->dinero($recaudadoSindicato),
            'pagos_sindicato' => $pagosSindicato,
            'motrix_directo' =>
                $this->dinero($motrixDirecto),
            'pagos_directos' => $pagosDirectos,
            'liquidado_validado' =>
                $this->dinero($validado),
            'pendiente_validacion' =>
                $this->dinero($pendienteValidacion),
            'pendiente_liquidar' =>
                $this->dinero(
                    max(
                        0,
                        $recaudadoSindicato - $validado
                    )
                ),
            'disponible_transferir' =>
                $this->dinero(
                    max(
                        0,
                        $recaudadoSindicato
                        - $validado
                        - $pendienteValidacion
                    )
                ),
            'pagos_incluidos' => $pagosIncluidos,
            'liquidacion_id' => $liquidacion?->id,
            'estado' =>
                $liquidacion?->estado
                ?? self::LIQUIDACION_PENDIENTE,
        ];
    }

    public function sincronizarLiquidacion(
        int $sindicatoId,
        string $periodo,
        ?int $usuarioId
    ): LiquidacionMotrix {
        return DB::transaction(
            function () use (
                $sindicatoId,
                $periodo,
                $usuarioId
            ) {
                $liquidacion =
                    LiquidacionMotrix::query()
                        ->firstOrCreate(
                            [
                                'id_sindicato' => $sindicatoId,
                                'periodo' => $periodo,
                            ],
                            [
                                'monto_declarado' => 0,
                                'monto_transferido' => 0,
                                'estado' =>
                                    self::LIQUIDACION_PENDIENTE,
                                'registrado_por' => $usuarioId,
                            ]
                        );

                $liquidacion =
                    LiquidacionMotrix::query()
                        ->lockForUpdate()
                        ->findOrFail($liquidacion->id);

                /*
                 * Solo se vinculan pagos efectivamente pagados y
                 * cobrados por el sindicato. motrix_directo queda
                 * deliberadamente fuera de la liquidación.
                 */
                $pagos =
                    PagoSuscripcionMotrix::query()
                        ->where(
                            'id_sindicato',
                            $sindicatoId
                        )
                        ->where('periodo', $periodo)
                        ->where(
                            'estado',
                            MotrixSubscriptionBillingService::PAGO_PAGADO
                        )
                        ->where(
                            'canal_cobro',
                            MotrixSubscriptionBillingService::CANAL_SINDICATO
                        )
                        ->whereDoesntHave(
                            'detallesLiquidacion'
                        )
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                foreach ($pagos as $pago) {
                    LiquidacionPagoMotrix::query()
                        ->firstOrCreate(
                            [
                                'pago_suscripcion_id' =>
                                    $pago->id,
                            ],
                            [
                                'liquidacion_id' =>
                                    $liquidacion->id,
                                'monto_incluido' =>
                                    $pago->monto_pagado,
                            ]
                        );
                }

                if (
                    ! $liquidacion->registrado_por
                    && $usuarioId
                ) {
                    $liquidacion->registrado_por =
                        $usuarioId;
                    $liquidacion->save();
                }

                $this->recalcularLiquidacion(
                    $liquidacion
                );

                return $this->cargarRelaciones(
                    $liquidacion->fresh()
                );
            }
        );
    }

    public function registrarTransferencia(
        LiquidacionMotrix $liquidacion,
        array $datos,
        ?int $usuarioId
    ): TransferenciaLiquidacionMotrix {
        return DB::transaction(
            function () use (
                $liquidacion,
                $datos,
                $usuarioId
            ) {
                $liquidacion =
                    LiquidacionMotrix::query()
                        ->lockForUpdate()
                        ->findOrFail($liquidacion->id);

                $this->recalcularLiquidacion(
                    $liquidacion
                );

                $montoDeclarado =
                    (float) $liquidacion
                        ->monto_declarado;

                if ($montoDeclarado <= 0) {
                    throw ValidationException::withMessages([
                        'monto' =>
                            'No existen pagos cobrados por el sindicato para liquidar en este periodo.',
                    ]);
                }

                $montoValidado =
                    (float) $liquidacion
                        ->monto_transferido;

                $montoPendiente =
                    (float)
                    TransferenciaLiquidacionMotrix::query()
                        ->where(
                            'liquidacion_id',
                            $liquidacion->id
                        )
                        ->where(
                            'estado',
                            self::TRANSFERENCIA_PENDIENTE
                        )
                        ->sum('monto');

                $disponible = round(
                    max(
                        0,
                        $montoDeclarado
                        - $montoValidado
                        - $montoPendiente
                    ),
                    2
                );

                $monto = round(
                    (float) (
                        $datos['monto'] ?? 0
                    ),
                    2
                );

                if (
                    $monto <= 0
                    || $monto > $disponible + 0.009
                ) {
                    throw ValidationException::withMessages([
                        'monto' =>
                            'El monto supera el saldo disponible para transferir de Bs. '
                            . $this->dinero($disponible)
                            . '.',
                    ]);
                }

                return
                    TransferenciaLiquidacionMotrix::query()
                        ->create([
                            'liquidacion_id' =>
                                $liquidacion->id,
                            'monto' => $monto,
                            'forma_pago' =>
                                $datos['forma_pago'],
                            'referencia' =>
                                $datos['referencia']
                                ?? null,
                            'comprobante_url' =>
                                $datos['comprobante_url']
                                ?? null,
                            'fecha_transferencia' =>
                                $datos[
                                    'fecha_transferencia'
                                ]
                                ?? now(),
                            'estado' =>
                                self::TRANSFERENCIA_PENDIENTE,
                            'observacion' =>
                                $datos['observacion']
                                ?? null,
                            'registrado_por' =>
                                $usuarioId,
                        ])
                        ->fresh([
                            'registradoPor',
                            'validadoPor',
                        ]);
            }
        );
    }

    public function validarTransferencia(
        TransferenciaLiquidacionMotrix $transferencia,
        ?int $usuarioId,
        ?string $observacion = null
    ): TransferenciaLiquidacionMotrix {
        return DB::transaction(
            function () use (
                $transferencia,
                $usuarioId,
                $observacion
            ) {
                $transferencia =
                    TransferenciaLiquidacionMotrix::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $transferencia->id
                        );

                if (
                    $transferencia->estado
                    !== self::TRANSFERENCIA_PENDIENTE
                ) {
                    throw ValidationException::withMessages([
                        'estado' =>
                            'Esta transferencia ya fue revisada.',
                    ]);
                }

                $liquidacion =
                    LiquidacionMotrix::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $transferencia
                                ->liquidacion_id
                        );

                $validadoActual =
                    (float)
                    TransferenciaLiquidacionMotrix::query()
                        ->where(
                            'liquidacion_id',
                            $liquidacion->id
                        )
                        ->where(
                            'estado',
                            self::TRANSFERENCIA_VALIDADA
                        )
                        ->sum('monto');

                $nuevoTotal = round(
                    $validadoActual
                    + (float) $transferencia->monto,
                    2
                );

                if (
                    $nuevoTotal
                    > (
                        (float)
                        $liquidacion->monto_declarado
                        + 0.009
                    )
                ) {
                    throw ValidationException::withMessages([
                        'monto' =>
                            'La validación supera el total recaudado por el sindicato.',
                    ]);
                }

                $transferencia->fill([
                    'estado' =>
                        self::TRANSFERENCIA_VALIDADA,
                    'validado_por' => $usuarioId,
                    'validado_en' => now(),
                    'observacion' =>
                        $observacion
                        ?? $transferencia->observacion,
                ]);
                $transferencia->save();

                /*
                 * Se conservan sincronizados los campos históricos
                 * de liquidaciones_motrix para compatibilidad.
                 */
                $liquidacion->fill([
                    'forma_pago' =>
                        $transferencia->forma_pago,
                    'referencia' =>
                        $transferencia->referencia,
                    'comprobante_url' =>
                        $transferencia
                            ->comprobante_url,
                    'fecha_transferencia' =>
                        $transferencia
                            ->fecha_transferencia,
                    'fecha_validacion' => now(),
                    'validado_por' => $usuarioId,
                ]);
                $liquidacion->save();

                $this->recalcularLiquidacion(
                    $liquidacion
                );

                return $transferencia->fresh([
                    'liquidacion.sindicato',
                    'registradoPor',
                    'validadoPor',
                ]);
            }
        );
    }

    public function observarTransferencia(
        TransferenciaLiquidacionMotrix $transferencia,
        ?int $usuarioId,
        string $observacion
    ): TransferenciaLiquidacionMotrix {
        return DB::transaction(
            function () use (
                $transferencia,
                $usuarioId,
                $observacion
            ) {
                $transferencia =
                    TransferenciaLiquidacionMotrix::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $transferencia->id
                        );

                if (
                    $transferencia->estado
                    !== self::TRANSFERENCIA_PENDIENTE
                ) {
                    throw ValidationException::withMessages([
                        'estado' =>
                            'Esta transferencia ya fue revisada.',
                    ]);
                }

                $transferencia->fill([
                    'estado' =>
                        self::TRANSFERENCIA_OBSERVADA,
                    'validado_por' => $usuarioId,
                    'validado_en' => now(),
                    'observacion' =>
                        trim($observacion),
                ]);
                $transferencia->save();

                $liquidacion =
                    LiquidacionMotrix::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $transferencia
                                ->liquidacion_id
                        );

                $this->recalcularLiquidacion(
                    $liquidacion
                );

                return $transferencia->fresh([
                    'liquidacion.sindicato',
                    'registradoPor',
                    'validadoPor',
                ]);
            }
        );
    }

    public function recalcularLiquidacion(
        LiquidacionMotrix $liquidacion
    ): LiquidacionMotrix {
        $declarado =
            (float)
            LiquidacionPagoMotrix::query()
                ->where(
                    'liquidacion_id',
                    $liquidacion->id
                )
                ->sum('monto_incluido');

        $transferido =
            (float)
            TransferenciaLiquidacionMotrix::query()
                ->where(
                    'liquidacion_id',
                    $liquidacion->id
                )
                ->where(
                    'estado',
                    self::TRANSFERENCIA_VALIDADA
                )
                ->sum('monto');

        $estado =
            self::LIQUIDACION_PENDIENTE;

        if (
            $declarado > 0
            && $transferido
                >= $declarado - 0.009
        ) {
            $estado =
                self::LIQUIDACION_LIQUIDADA;
        } elseif ($transferido > 0) {
            $estado =
                self::LIQUIDACION_PARCIAL;
        }

        $liquidacion->fill([
            'monto_declarado' =>
                round($declarado, 2),
            'monto_transferido' =>
                round($transferido, 2),
            'estado' => $estado,
        ]);
        $liquidacion->save();

        return $liquidacion;
    }

    public function cargarRelaciones(
        LiquidacionMotrix $liquidacion
    ): LiquidacionMotrix {
        return $liquidacion->load([
            'sindicato:id,nombre',
            'registradoPor:id,name,email',
            'validadoPor:id,name,email',
            'detalles.pago:id,suscripcion_id,id_mototaxista,id_sindicato,periodo,monto_pagado,fecha_pago,forma_pago,canal_cobro',
            'detalles.pago.mototaxista:id,id_persona,id_sindicato,nro_chaleco',
            'detalles.pago.mototaxista.persona:id,nombre,apellidos,ci',
            'transferencias' =>
                fn ($query) =>
                    $query
                        ->orderByDesc(
                            'fecha_transferencia'
                        )
                        ->orderByDesc('id'),
            'transferencias.registradoPor:id,name,email',
            'transferencias.validadoPor:id,name,email',
        ]);
    }

    private function dinero(
        float $monto
    ): string {
        return number_format(
            round($monto, 2),
            2,
            '.',
            ''
        );
    }
}
