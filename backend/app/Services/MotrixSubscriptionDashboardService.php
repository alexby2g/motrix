<?php

namespace App\Services;

use App\Models\PagoSuscripcionMotrix;
use App\Models\PlanSuscripcionMotrix;
use App\Models\Sindicato;
use App\Models\SuscripcionMotrix;
use Illuminate\Support\Facades\DB;

class MotrixSubscriptionDashboardService
{
    public function __construct(
        private readonly MotrixSubscriptionService $subscriptionService
    ) {
    }

    public function resumen(
        string $periodo,
        ?int $sindicatoId = null
    ): array {
        $sindicatos = Sindicato::query()
            ->select([
                'id',
                'nombre',
            ])
            ->when(
                $sindicatoId,
                fn ($query) =>
                    $query->where(
                        'id',
                        $sindicatoId
                    )
            )
            ->orderBy('nombre')
            ->get();

        $filas = [];

        foreach ($sindicatos as $sindicato) {
            $filas[(int) $sindicato->id] =
                $this->filaVacia(
                    (int) $sindicato->id,
                    (string) $sindicato->nombre
                );
        }

        $suscripciones = SuscripcionMotrix::query()
            ->with([
                'plan',
            ])
            ->when(
                $sindicatoId,
                fn ($query) =>
                    $query->where(
                        'id_sindicato',
                        $sindicatoId
                    )
            )
            ->get();

        foreach ($suscripciones as $suscripcion) {
            $idSindicato =
                (int) $suscripcion->id_sindicato;

            $this->asegurarFila(
                $filas,
                $idSindicato
            );

            $filas[$idSindicato]
                ['suscripciones']
                ['total']++;

            $estado =
                $this->subscriptionService
                    ->estadoActual(
                        $suscripcion
                    );

            $clave =
                $this->claveEstado(
                    $estado
                );

            if ($clave !== null) {
                $filas[$idSindicato]
                    ['suscripciones']
                    [$clave]++;
            }
        }

        $pagos = PagoSuscripcionMotrix::query()
            ->where(
                'periodo',
                $periodo
            )
            ->when(
                $sindicatoId,
                fn ($query) =>
                    $query->where(
                        'id_sindicato',
                        $sindicatoId
                    )
            )
            ->get([
                'id_sindicato',
                'monto_esperado',
                'monto_pagado',
                'estado',
                'canal_cobro',
            ]);

        foreach ($pagos as $pago) {
            $idSindicato =
                (int) $pago->id_sindicato;

            $this->asegurarFila(
                $filas,
                $idSindicato
            );

            $estado = strtolower(
                trim(
                    (string) $pago->estado
                )
            );

            $montoEsperado =
                (float) $pago->monto_esperado;

            $montoPagado =
                (float) $pago->monto_pagado;

            if ($estado === 'pagado') {
                $filas[$idSindicato]
                    ['cobranza']
                    ['pagadas']++;

                $filas[$idSindicato]
                    ['cobranza']
                    ['recaudado_total'] +=
                        $montoPagado;

                $canal = strtolower(
                    trim(
                        (string) $pago->canal_cobro
                    )
                );

                if ($canal === 'motrix_directo') {
                    $filas[$idSindicato]
                        ['cobranza']
                        ['motrix_directo'] +=
                            $montoPagado;
                } else {
                    $filas[$idSindicato]
                        ['cobranza']
                        ['recaudado_sindicato'] +=
                            $montoPagado;
                }

                continue;
            }

            if ($estado === 'pendiente') {
                $filas[$idSindicato]
                    ['cobranza']
                    ['pendientes']++;
            }

            if ($estado === 'vencido') {
                $filas[$idSindicato]
                    ['cobranza']
                    ['vencidas']++;
            }

            if (
                in_array(
                    $estado,
                    [
                        'pendiente',
                        'vencido',
                    ],
                    true
                )
            ) {
                $filas[$idSindicato]
                    ['cobranza']
                    ['pendiente_cobro'] +=
                        max(
                            0,
                            $montoEsperado
                            - $montoPagado
                        );
            }
        }

        $transferencias = DB::table(
            'transferencias_liquidacion_motrix as t'
        )
            ->join(
                'liquidaciones_motrix as l',
                'l.id',
                '=',
                't.liquidacion_id'
            )
            ->where(
                'l.periodo',
                $periodo
            )
            ->when(
                $sindicatoId,
                fn ($query) =>
                    $query->where(
                        'l.id_sindicato',
                        $sindicatoId
                    )
            )
            ->get([
                'l.id_sindicato',
                't.monto',
                't.estado',
            ]);

        foreach ($transferencias as $transferencia) {
            $idSindicato =
                (int) $transferencia->id_sindicato;

            $this->asegurarFila(
                $filas,
                $idSindicato
            );

            $estado = strtolower(
                trim(
                    (string) $transferencia->estado
                )
            );

            if ($estado === 'validada') {
                $filas[$idSindicato]
                    ['liquidaciones']
                    ['liquidado_validado'] +=
                        (float) $transferencia->monto;
            }

            if ($estado === 'pendiente') {
                $filas[$idSindicato]
                    ['liquidaciones']
                    ['pendiente_validacion'] +=
                        (float) $transferencia->monto;
            }
        }

        foreach ($filas as &$fila) {
            $recaudado =
                (float) $fila
                    ['cobranza']
                    ['recaudado_sindicato'];

            $validado =
                (float) $fila
                    ['liquidaciones']
                    ['liquidado_validado'];

            $pendienteValidacion =
                (float) $fila
                    ['liquidaciones']
                    ['pendiente_validacion'];

            $fila['liquidaciones']
                ['pendiente_liquidar'] =
                    max(
                        0,
                        $recaudado
                        - $validado
                    );

            $fila['liquidaciones']
                ['disponible_transferir'] =
                    max(
                        0,
                        $recaudado
                        - $validado
                        - $pendienteValidacion
                    );
        }

        unset($fila);

        $resumen =
            $this->filaVacia(
                null,
                'Consolidado MOTRIX'
            );

        foreach ($filas as $fila) {
            $this->acumular(
                $resumen,
                $fila
            );
        }

        $planes =
            PlanSuscripcionMotrix::query()
                ->where(
                    'activo',
                    true
                )
                ->orderBy('monto')
                ->orderBy('id')
                ->get([
                    'id',
                    'nombre',
                    'descripcion',
                    'monto',
                    'duracion_meses',
                    'dias_gracia',
                    'aviso_dias_antes',
                    'activo',
                ]);

        return [
            'periodo' =>
                $periodo,
            'id_sindicato' =>
                $sindicatoId,
            'resumen' =>
                $this->serializarFila(
                    $resumen
                ),
            'sindicatos' =>
                collect(
                    array_values(
                        $filas
                    )
                )
                    ->sortBy('nombre')
                    ->map(
                        fn (array $fila) =>
                            $this->serializarFila(
                                $fila
                            )
                    )
                    ->values()
                    ->all(),
            'planes' =>
                $planes,
        ];
    }

    private function filaVacia(
        ?int $idSindicato,
        string $nombre
    ): array {
        return [
            'id_sindicato' =>
                $idSindicato,
            'nombre' =>
                $nombre,
            'suscripciones' => [
                'total' => 0,
                'activas' => 0,
                'por_vencer' => 0,
                'gracia' => 0,
                'vencidas' => 0,
                'suspendidas' => 0,
            ],
            'cobranza' => [
                'pagadas' => 0,
                'pendientes' => 0,
                'vencidas' => 0,
                'recaudado_total' => 0.0,
                'recaudado_sindicato' => 0.0,
                'motrix_directo' => 0.0,
                'pendiente_cobro' => 0.0,
            ],
            'liquidaciones' => [
                'liquidado_validado' => 0.0,
                'pendiente_validacion' => 0.0,
                'pendiente_liquidar' => 0.0,
                'disponible_transferir' => 0.0,
            ],
        ];
    }

    private function asegurarFila(
        array &$filas,
        int $idSindicato
    ): void {
        if (isset($filas[$idSindicato])) {
            return;
        }

        $nombre = Sindicato::query()
            ->where(
                'id',
                $idSindicato
            )
            ->value('nombre');

        $filas[$idSindicato] =
            $this->filaVacia(
                $idSindicato,
                $nombre
                    ? (string) $nombre
                    : "Sindicato #{$idSindicato}"
            );
    }

    private function claveEstado(
        string $estado
    ): ?string {
        return match (
            strtolower(
                trim(
                    $estado
                )
            )
        ) {
            'activa' =>
                'activas',
            'por vencer' =>
                'por_vencer',
            'periodo de gracia' =>
                'gracia',
            'vencida' =>
                'vencidas',
            'suspendida' =>
                'suspendidas',
            default =>
                null,
        };
    }

    private function acumular(
        array &$destino,
        array $origen
    ): void {
        foreach (
            [
                'suscripciones',
                'cobranza',
                'liquidaciones',
            ]
            as $grupo
        ) {
            foreach (
                $origen[$grupo]
                as $clave => $valor
            ) {
                $destino[$grupo][$clave] +=
                    $valor;
            }
        }
    }

    private function serializarFila(
        array $fila
    ): array {
        foreach (
            [
                'recaudado_total',
                'recaudado_sindicato',
                'motrix_directo',
                'pendiente_cobro',
            ]
            as $campo
        ) {
            $fila['cobranza'][$campo] =
                $this->dinero(
                    $fila['cobranza'][$campo]
                );
        }

        foreach (
            [
                'liquidado_validado',
                'pendiente_validacion',
                'pendiente_liquidar',
                'disponible_transferir',
            ]
            as $campo
        ) {
            $fila['liquidaciones'][$campo] =
                $this->dinero(
                    $fila['liquidaciones'][$campo]
                );
        }

        return $fila;
    }

    private function dinero(
        float|int|string|null $valor
    ): string {
        return number_format(
            (float) $valor,
            2,
            '.',
            ''
        );
    }
}
