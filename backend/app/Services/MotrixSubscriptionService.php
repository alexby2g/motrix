<?php

namespace App\Services;

use App\Models\SuscripcionMotrix;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class MotrixSubscriptionService
{
    public const ESTADO_ACTIVA = 'Activa';
    public const ESTADO_POR_VENCER = 'Por vencer';
    public const ESTADO_GRACIA = 'Periodo de gracia';
    public const ESTADO_VENCIDA = 'Vencida';
    public const ESTADO_SUSPENDIDA = 'Suspendida';

    public function estadoActual(
        SuscripcionMotrix $suscripcion,
        ?CarbonInterface $fecha = null
    ): string {
        if ($suscripcion->estaSuspendida()) {
            return self::ESTADO_SUSPENDIDA;
        }

        $fecha = $fecha
            ? Carbon::instance($fecha)->startOfDay()
            : now()->startOfDay();

        $vencimiento = Carbon::parse(
            $suscripcion->fecha_vencimiento
        )->startOfDay();

        $plan = $suscripcion->relationLoaded('plan')
            ? $suscripcion->plan
            : $suscripcion->plan()->first();

        $diasAviso = max(
            0,
            (int) ($plan?->aviso_dias_antes ?? 7)
        );

        $diasGracia = max(
            0,
            (int) ($plan?->dias_gracia ?? 3)
        );

        if ($fecha->lte($vencimiento)) {
            $diasRestantes = $fecha->diffInDays(
                $vencimiento,
                false
            );

            if ($diasRestantes <= $diasAviso) {
                return self::ESTADO_POR_VENCER;
            }

            return self::ESTADO_ACTIVA;
        }

        $finGracia = $vencimiento
            ->copy()
            ->addDays($diasGracia);

        if ($fecha->lte($finGracia)) {
            return self::ESTADO_GRACIA;
        }

        return self::ESTADO_VENCIDA;
    }

    public function periodo(CarbonInterface|string|null $fecha = null): string
    {
        $fecha = $fecha
            ? Carbon::parse($fecha)
            : now();

        return $fecha->format('Y-m');
    }

    public function proximoVencimiento(
        CarbonInterface|string $desde,
        int $meses = 1
    ): Carbon {
        return Carbon::parse($desde)
            ->addMonthsNoOverflow(max(1, $meses));
    }
}
