<?php

namespace App\Services;

use App\Models\LiquidacionMotrix;
use App\Models\Mototaxista;
use App\Models\PagoSuscripcionMotrix;
use App\Models\Sindicato;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MotrixSubscriptionReportService
{
    public const TIPO_SUSCRIPCIONES = 'suscripciones';
    public const TIPO_PENDIENTES = 'pendientes';
    public const TIPO_RECAUDACION = 'recaudacion';
    public const TIPO_LIQUIDACIONES = 'liquidaciones';
    public const TIPO_HISTORIAL = 'historial';
    public const TIPO_CONSOLIDADO = 'consolidado';

    public function __construct(
        private readonly MotrixSubscriptionDashboardService $dashboardService
    ) {
    }

    public function tipos(): array
    {
        return [
            self::TIPO_SUSCRIPCIONES,
            self::TIPO_PENDIENTES,
            self::TIPO_RECAUDACION,
            self::TIPO_LIQUIDACIONES,
            self::TIPO_HISTORIAL,
            self::TIPO_CONSOLIDADO,
        ];
    }

    public function catalogo(string $rol): array
    {
        $catalogo = [
            [
                'tipo' => self::TIPO_SUSCRIPCIONES,
                'titulo' => 'Suscripciones del periodo',
                'descripcion' => 'Mototaxista, periodo, monto, estado, fecha de pago y forma de pago.',
                'requiere_mototaxista' => false,
            ],
            [
                'tipo' => self::TIPO_PENDIENTES,
                'titulo' => 'Pendientes y vencidos',
                'descripcion' => 'Conductores con deuda o renovación pendiente.',
                'requiere_mototaxista' => false,
            ],
            [
                'tipo' => self::TIPO_RECAUDACION,
                'titulo' => 'Recaudación mensual',
                'descripcion' => 'Cobros sindicales, pagos directos MOTRIX y saldo pendiente.',
                'requiere_mototaxista' => false,
            ],
            [
                'tipo' => self::TIPO_LIQUIDACIONES,
                'titulo' => 'Liquidaciones',
                'descripcion' => 'Monto declarado, validado, pendiente y estado por sindicato.',
                'requiere_mototaxista' => false,
            ],
            [
                'tipo' => self::TIPO_HISTORIAL,
                'titulo' => 'Historial individual',
                'descripcion' => 'Meses pagados, pendientes, vencimientos y medios de pago del conductor.',
                'requiere_mototaxista' => true,
            ],
        ];

        if ($rol === 'admin_general') {
            $catalogo[] = [
                'tipo' => self::TIPO_CONSOLIDADO,
                'titulo' => 'Resumen consolidado',
                'descripcion' => 'Comparativo de sindicatos, recaudación y saldos por liquidar.',
                'requiere_mototaxista' => false,
            ];
        }

        return $catalogo;
    }

    public function generar(
        string $tipo,
        string $periodo,
        ?int $sindicatoId = null,
        ?int $mototaxistaId = null
    ): array {
        return match ($tipo) {
            self::TIPO_SUSCRIPCIONES =>
                $this->suscripcionesPeriodo($periodo, $sindicatoId),
            self::TIPO_PENDIENTES =>
                $this->pendientesVencidos($periodo, $sindicatoId),
            self::TIPO_RECAUDACION =>
                $this->recaudacionMensual($periodo, $sindicatoId),
            self::TIPO_LIQUIDACIONES =>
                $this->liquidaciones($periodo, $sindicatoId),
            self::TIPO_HISTORIAL =>
                $this->historialIndividual($mototaxistaId, $sindicatoId),
            self::TIPO_CONSOLIDADO =>
                $this->resumenConsolidado($periodo, $sindicatoId),
            default => throw ValidationException::withMessages([
                'tipo' => 'El tipo de reporte MOTRIX no es válido.',
            ]),
        };
    }

    public function autorizarMototaxista(
        int $mototaxistaId,
        ?int $sindicatoId
    ): Mototaxista {
        $query = Mototaxista::query()
            ->with([
                'persona:id,nombre,apellidos,ci,telefono',
                'sindicato:id,nombre',
            ])
            ->where('id', $mototaxistaId);

        if ($sindicatoId) {
            $query->where('id_sindicato', $sindicatoId);
        }

        return $query->firstOrFail();
    }

    private function suscripcionesPeriodo(
        string $periodo,
        ?int $sindicatoId
    ): array {
        $pagos = $this->pagosPeriodo($periodo, $sindicatoId);

        $filas = $pagos->map(function (PagoSuscripcionMotrix $pago) {
            return [
                'mototaxista' => $this->nombreMototaxista($pago),
                'ci' => (string) ($pago->mototaxista?->persona?->ci ?? ''),
                'chaleco' => (string) ($pago->mototaxista?->nro_chaleco ?? ''),
                'sindicato' => (string) ($pago->sindicato?->nombre ?? ''),
                'periodo' => (string) $pago->periodo,
                'monto' => $this->dinero($pago->monto_esperado),
                'estado' => (string) $pago->estado,
                'fecha_pago' => $this->fechaHora($pago->fecha_pago),
                'forma_pago' => (string) ($pago->forma_pago ?? ''),
                'canal' => (string) ($pago->canal_cobro ?? ''),
            ];
        })->values()->all();

        return $this->reporte(
            'Suscripciones del periodo',
            $periodo,
            [
                'mototaxista' => 'Mototaxista',
                'ci' => 'CI',
                'chaleco' => 'Chaleco',
                'sindicato' => 'Sindicato',
                'periodo' => 'Periodo',
                'monto' => 'Monto (Bs.)',
                'estado' => 'Estado',
                'fecha_pago' => 'Fecha de pago',
                'forma_pago' => 'Forma de pago',
                'canal' => 'Canal',
            ],
            $filas,
            [
                'registros' => count($filas),
                'monto_esperado' => $this->dinero($pagos->sum('monto_esperado')),
                'monto_pagado' => $this->dinero($pagos->sum('monto_pagado')),
            ]
        );
    }

    private function pendientesVencidos(
        string $periodo,
        ?int $sindicatoId
    ): array {
        $pagos = $this->pagosPeriodo($periodo, $sindicatoId)
            ->filter(fn (PagoSuscripcionMotrix $pago) => in_array(
                strtolower(trim((string) $pago->estado)),
                ['pendiente', 'vencido'],
                true
            ))
            ->values();

        $filas = $pagos->map(function (PagoSuscripcionMotrix $pago) {
            $saldo = max(
                0,
                (float) $pago->monto_esperado - (float) $pago->monto_pagado
            );

            return [
                'mototaxista' => $this->nombreMototaxista($pago),
                'ci' => (string) ($pago->mototaxista?->persona?->ci ?? ''),
                'chaleco' => (string) ($pago->mototaxista?->nro_chaleco ?? ''),
                'sindicato' => (string) ($pago->sindicato?->nombre ?? ''),
                'periodo' => (string) $pago->periodo,
                'estado' => (string) $pago->estado,
                'vencimiento' => $this->fecha($pago->fecha_vencimiento),
                'monto' => $this->dinero($pago->monto_esperado),
                'saldo' => $this->dinero($saldo),
            ];
        })->all();

        return $this->reporte(
            'Pendientes y vencidos',
            $periodo,
            [
                'mototaxista' => 'Mototaxista',
                'ci' => 'CI',
                'chaleco' => 'Chaleco',
                'sindicato' => 'Sindicato',
                'periodo' => 'Periodo',
                'estado' => 'Estado',
                'vencimiento' => 'Vencimiento',
                'monto' => 'Monto (Bs.)',
                'saldo' => 'Saldo (Bs.)',
            ],
            $filas,
            [
                'registros' => count($filas),
                'saldo_pendiente' => $this->dinero(
                    $pagos->sum(fn (PagoSuscripcionMotrix $pago) => max(
                        0,
                        (float) $pago->monto_esperado - (float) $pago->monto_pagado
                    ))
                ),
            ]
        );
    }

    private function recaudacionMensual(
        string $periodo,
        ?int $sindicatoId
    ): array {
        $pagos = $this->pagosPeriodo($periodo, $sindicatoId);
        $ids = $pagos->pluck('id_sindicato')->map(fn ($id) => (int) $id)->unique();

        if ($sindicatoId && ! $ids->contains($sindicatoId)) {
            $ids->push($sindicatoId);
        }

        $sindicatos = Sindicato::query()
            ->when(
                $ids->isNotEmpty(),
                fn ($query) => $query->whereIn('id', $ids->all()),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $filas = $sindicatos->map(function (Sindicato $sindicato) use ($pagos) {
            $items = $pagos->where('id_sindicato', (int) $sindicato->id);
            $pagados = $items->filter(
                fn (PagoSuscripcionMotrix $pago) => strtolower((string) $pago->estado) === 'pagado'
            );
            $sindicatoCobrado = $pagados->where('canal_cobro', 'sindicato');
            $directo = $pagados->where('canal_cobro', 'motrix_directo');
            $pendientes = $items->filter(fn (PagoSuscripcionMotrix $pago) => in_array(
                strtolower((string) $pago->estado),
                ['pendiente', 'vencido'],
                true
            ));

            return [
                'sindicato' => (string) $sindicato->nombre,
                'pagos' => $pagados->count(),
                'cobrado_sindicato' => $this->dinero($sindicatoCobrado->sum('monto_pagado')),
                'motrix_directo' => $this->dinero($directo->sum('monto_pagado')),
                'recaudado_total' => $this->dinero($pagados->sum('monto_pagado')),
                'pendientes' => $pendientes->count(),
                'saldo_pendiente' => $this->dinero(
                    $pendientes->sum(fn (PagoSuscripcionMotrix $pago) => max(
                        0,
                        (float) $pago->monto_esperado - (float) $pago->monto_pagado
                    ))
                ),
            ];
        })->values()->all();

        return $this->reporte(
            'Recaudación mensual',
            $periodo,
            [
                'sindicato' => 'Sindicato',
                'pagos' => 'Pagos',
                'cobrado_sindicato' => 'Cobrado sindicato (Bs.)',
                'motrix_directo' => 'MOTRIX directo (Bs.)',
                'recaudado_total' => 'Total recaudado (Bs.)',
                'pendientes' => 'Pendientes',
                'saldo_pendiente' => 'Pendiente de cobro (Bs.)',
            ],
            $filas,
            [
                'recaudado_total' => $this->dinero(
                    $pagos->where('estado', 'Pagado')->sum('monto_pagado')
                ),
                'pendiente_cobro' => $this->dinero(
                    $pagos->filter(fn (PagoSuscripcionMotrix $pago) => in_array(
                        strtolower((string) $pago->estado),
                        ['pendiente', 'vencido'],
                        true
                    ))->sum(fn (PagoSuscripcionMotrix $pago) => max(
                        0,
                        (float) $pago->monto_esperado - (float) $pago->monto_pagado
                    ))
                ),
            ]
        );
    }

    private function liquidaciones(
        string $periodo,
        ?int $sindicatoId
    ): array {
        $liquidaciones = LiquidacionMotrix::query()
            ->with([
                'sindicato:id,nombre',
                'transferencias',
            ])
            ->where('periodo', $periodo)
            ->when(
                $sindicatoId,
                fn ($query) => $query->where('id_sindicato', $sindicatoId)
            )
            ->orderBy('id_sindicato')
            ->get();

        $filas = $liquidaciones->map(function (LiquidacionMotrix $liquidacion) {
            $validado = (float) $liquidacion->transferencias
                ->where('estado', 'Validada')
                ->sum('monto');
            $pendienteValidacion = (float) $liquidacion->transferencias
                ->where('estado', 'Pendiente')
                ->sum('monto');

            return [
                'sindicato' => (string) ($liquidacion->sindicato?->nombre ?? ''),
                'periodo' => (string) $liquidacion->periodo,
                'declarado' => $this->dinero($liquidacion->monto_declarado),
                'validado' => $this->dinero($validado),
                'pendiente_validacion' => $this->dinero($pendienteValidacion),
                'saldo' => $this->dinero(max(0, (float) $liquidacion->monto_declarado - $validado)),
                'transferencias' => $liquidacion->transferencias->count(),
                'estado' => (string) $liquidacion->estado,
            ];
        })->values()->all();

        return $this->reporte(
            'Liquidaciones Sindicato → MOTRIX',
            $periodo,
            [
                'sindicato' => 'Sindicato',
                'periodo' => 'Periodo',
                'declarado' => 'Declarado (Bs.)',
                'validado' => 'Transferido validado (Bs.)',
                'pendiente_validacion' => 'Pendiente validación (Bs.)',
                'saldo' => 'Saldo por liquidar (Bs.)',
                'transferencias' => 'Transferencias',
                'estado' => 'Estado',
            ],
            $filas,
            [
                'declarado' => $this->dinero($liquidaciones->sum('monto_declarado')),
                'validado' => $this->dinero($liquidaciones->sum(
                    fn (LiquidacionMotrix $liquidacion) => $liquidacion->transferencias
                        ->where('estado', 'Validada')->sum('monto')
                )),
            ]
        );
    }

    private function historialIndividual(
        ?int $mototaxistaId,
        ?int $sindicatoId
    ): array {
        if (! $mototaxistaId) {
            throw ValidationException::withMessages([
                'id_mototaxista' => 'Selecciona un mototaxista para generar el historial.',
            ]);
        }

        $mototaxista = $this->autorizarMototaxista($mototaxistaId, $sindicatoId);

        $pagos = PagoSuscripcionMotrix::query()
            ->where('id_mototaxista', $mototaxistaId)
            ->orderByDesc('periodo')
            ->orderByDesc('id')
            ->get();

        $filas = $pagos->map(function (PagoSuscripcionMotrix $pago) {
            return [
                'periodo' => (string) $pago->periodo,
                'monto' => $this->dinero($pago->monto_esperado),
                'pagado' => $this->dinero($pago->monto_pagado),
                'estado' => (string) $pago->estado,
                'vencimiento' => $this->fecha($pago->fecha_vencimiento),
                'fecha_pago' => $this->fechaHora($pago->fecha_pago),
                'forma_pago' => (string) ($pago->forma_pago ?? ''),
                'canal' => (string) ($pago->canal_cobro ?? ''),
                'referencia' => (string) ($pago->referencia_pago ?? ''),
            ];
        })->all();

        $reporte = $this->reporte(
            'Historial individual de suscripción',
            null,
            [
                'periodo' => 'Periodo',
                'monto' => 'Cuota (Bs.)',
                'pagado' => 'Pagado (Bs.)',
                'estado' => 'Estado',
                'vencimiento' => 'Vencimiento',
                'fecha_pago' => 'Fecha de pago',
                'forma_pago' => 'Forma de pago',
                'canal' => 'Canal',
                'referencia' => 'Referencia',
            ],
            $filas,
            [
                'registros' => count($filas),
                'total_pagado' => $this->dinero($pagos->sum('monto_pagado')),
            ]
        );

        $reporte['subtitulo'] = trim(
            $this->nombrePersona($mototaxista->persona)
            . ' · CI ' . ($mototaxista->persona?->ci ?? '')
            . ' · Chaleco ' . ($mototaxista->nro_chaleco ?? '')
            . ' · ' . ($mototaxista->sindicato?->nombre ?? 'Sindicato')
        );

        return $reporte;
    }

    private function resumenConsolidado(
        string $periodo,
        ?int $sindicatoId
    ): array {
        $panel = $this->dashboardService->resumen($periodo, $sindicatoId);
        $filas = collect($panel['sindicatos'] ?? [])->map(function (array $fila) {
            return [
                'sindicato' => (string) ($fila['nombre'] ?? ''),
                'suscripciones' => (int) ($fila['suscripciones']['total'] ?? 0),
                'activas' => (int) ($fila['suscripciones']['activas'] ?? 0),
                'vencidas' => (int) ($fila['suscripciones']['vencidas'] ?? 0),
                'pagadas' => (int) ($fila['cobranza']['pagadas'] ?? 0),
                'recaudado_sindicato' => (string) ($fila['cobranza']['recaudado_sindicato'] ?? '0.00'),
                'motrix_directo' => (string) ($fila['cobranza']['motrix_directo'] ?? '0.00'),
                'pendiente_cobro' => (string) ($fila['cobranza']['pendiente_cobro'] ?? '0.00'),
                'liquidado' => (string) ($fila['liquidaciones']['liquidado_validado'] ?? '0.00'),
                'pendiente_liquidar' => (string) ($fila['liquidaciones']['pendiente_liquidar'] ?? '0.00'),
            ];
        })->values()->all();

        $resumen = $panel['resumen'] ?? [];

        return $this->reporte(
            'Resumen consolidado de sindicatos',
            $periodo,
            [
                'sindicato' => 'Sindicato',
                'suscripciones' => 'Suscripciones',
                'activas' => 'Activas',
                'vencidas' => 'Vencidas',
                'pagadas' => 'Pagadas',
                'recaudado_sindicato' => 'Cobrado sindicato (Bs.)',
                'motrix_directo' => 'MOTRIX directo (Bs.)',
                'pendiente_cobro' => 'Pendiente cobro (Bs.)',
                'liquidado' => 'Liquidado (Bs.)',
                'pendiente_liquidar' => 'Pendiente liquidar (Bs.)',
            ],
            $filas,
            [
                'suscripciones' => (int) ($resumen['suscripciones']['total'] ?? 0),
                'recaudado_total' => (string) ($resumen['cobranza']['recaudado_total'] ?? '0.00'),
                'liquidado' => (string) ($resumen['liquidaciones']['liquidado_validado'] ?? '0.00'),
                'pendiente_liquidar' => (string) ($resumen['liquidaciones']['pendiente_liquidar'] ?? '0.00'),
            ]
        );
    }

    private function pagosPeriodo(
        string $periodo,
        ?int $sindicatoId
    ): Collection {
        return PagoSuscripcionMotrix::query()
            ->with([
                'mototaxista:id,id_persona,id_sindicato,nro_chaleco,telefono,estado',
                'mototaxista.persona:id,nombre,apellidos,ci,telefono',
                'sindicato:id,nombre',
            ])
            ->where('periodo', $periodo)
            ->when(
                $sindicatoId,
                fn ($query) => $query->where('id_sindicato', $sindicatoId)
            )
            ->orderBy('id_sindicato')
            ->orderBy('id_mototaxista')
            ->get();
    }

    private function reporte(
        string $titulo,
        ?string $periodo,
        array $columnas,
        array $filas,
        array $totales = []
    ): array {
        return [
            'titulo' => $titulo,
            'subtitulo' => $periodo ? "Periodo {$periodo}" : null,
            'periodo' => $periodo,
            'columnas' => $columnas,
            'filas' => $filas,
            'totales' => $totales,
        ];
    }

    private function nombreMototaxista(PagoSuscripcionMotrix $pago): string
    {
        $nombre = $this->nombrePersona($pago->mototaxista?->persona);

        return $nombre !== ''
            ? $nombre
            : 'Mototaxista #' . $pago->id_mototaxista;
    }

    private function nombrePersona($persona): string
    {
        return trim(
            (string) (($persona?->nombre ?? '') . ' ' . ($persona?->apellidos ?? ''))
        );
    }

    private function fecha($valor): string
    {
        if (! $valor) {
            return '';
        }

        try {
            return $valor->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $valor;
        }
    }

    private function fechaHora($valor): string
    {
        if (! $valor) {
            return '';
        }

        try {
            return $valor->format('d/m/Y H:i');
        } catch (\Throwable) {
            return (string) $valor;
        }
    }

    private function dinero(float|int|string|null $valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }
}
