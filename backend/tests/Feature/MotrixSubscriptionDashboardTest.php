<?php

namespace Tests\Feature;

use App\Models\LiquidacionMotrix;
use App\Models\Mototaxista;
use App\Models\PagoSuscripcionMotrix;
use App\Models\Persona;
use App\Models\PlanSuscripcionMotrix;
use App\Models\Sindicato;
use App\Models\SuscripcionMotrix;
use App\Models\TransferenciaLiquidacionMotrix;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MotrixSubscriptionDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_secretario_solo_ve_el_panel_de_su_sindicato(): void
    {
        Carbon::setTestNow(
            '2026-09-25 09:00:00'
        );

        $plan = $this->crearPlan();

        $sindicatoPropio =
            Sindicato::query()->create([
                'nombre' =>
                    'Sindicato Propio',
            ]);

        $sindicatoAjeno =
            Sindicato::query()->create([
                'nombre' =>
                    'Sindicato Ajeno',
            ]);

        $suscripcionPropia =
            $this->crearSuscripcion(
                $sindicatoPropio,
                $plan,
                '920001',
                '2026-09-30'
            );

        $suscripcionAjena =
            $this->crearSuscripcion(
                $sindicatoAjeno,
                $plan,
                '920002',
                '2026-10-31'
            );

        $this->crearPago(
            $suscripcionPropia,
            'Pagado',
            'sindicato',
            15
        );

        $this->crearPago(
            $suscripcionAjena,
            'Pagado',
            'motrix_directo',
            15
        );

        $secretario =
            User::factory()->create([
                'role' =>
                    'secretario',
                'sindicato_id' =>
                    $sindicatoPropio->id,
            ]);

        Sanctum::actingAs(
            $secretario
        );

        $this->getJson(
            '/api/suscripciones-motrix/panel'
            . '?periodo=2026-09'
            . '&id_sindicato='
            . $sindicatoAjeno->id
        )
            ->assertOk()
            ->assertJsonPath(
                'data.id_sindicato',
                $sindicatoPropio->id
            )
            ->assertJsonPath(
                'data.resumen.suscripciones.total',
                1
            )
            ->assertJsonPath(
                'data.resumen.suscripciones.por_vencer',
                1
            )
            ->assertJsonPath(
                'data.resumen.cobranza.recaudado_sindicato',
                '15.00'
            )
            ->assertJsonPath(
                'data.resumen.cobranza.motrix_directo',
                '0.00'
            )
            ->assertJsonCount(
                1,
                'data.sindicatos'
            )
            ->assertJsonPath(
                'data.sindicatos.0.id_sindicato',
                $sindicatoPropio->id
            );
    }

    public function test_admin_general_ve_consolidado_y_separa_canales_de_cobro(): void
    {
        Carbon::setTestNow(
            '2026-09-10 09:00:00'
        );

        $plan = $this->crearPlan();

        $sindicatoUno =
            Sindicato::query()->create([
                'nombre' =>
                    'Sindicato Uno',
            ]);

        $sindicatoDos =
            Sindicato::query()->create([
                'nombre' =>
                    'Sindicato Dos',
            ]);

        $suscripcionUno =
            $this->crearSuscripcion(
                $sindicatoUno,
                $plan,
                '920003',
                '2026-09-30'
            );

        $suscripcionDos =
            $this->crearSuscripcion(
                $sindicatoDos,
                $plan,
                '920004',
                '2026-10-31'
            );

        $this->crearPago(
            $suscripcionUno,
            'Pagado',
            'sindicato',
            15
        );

        $this->crearPago(
            $suscripcionDos,
            'Pagado',
            'motrix_directo',
            15
        );

        Sanctum::actingAs(
            User::factory()->create([
                'role' =>
                    'admin_general',
            ])
        );

        $this->getJson(
            '/api/suscripciones-motrix/panel'
            . '?periodo=2026-09'
        )
            ->assertOk()
            ->assertJsonPath(
                'data.resumen.suscripciones.total',
                2
            )
            ->assertJsonPath(
                'data.resumen.suscripciones.activas',
                2
            )
            ->assertJsonPath(
                'data.resumen.cobranza.pagadas',
                2
            )
            ->assertJsonPath(
                'data.resumen.cobranza.recaudado_total',
                '30.00'
            )
            ->assertJsonPath(
                'data.resumen.cobranza.recaudado_sindicato',
                '15.00'
            )
            ->assertJsonPath(
                'data.resumen.cobranza.motrix_directo',
                '15.00'
            )
            ->assertJsonCount(
                2,
                'data.sindicatos'
            );
    }

    public function test_panel_muestra_liquidado_pendiente_y_disponible(): void
    {
        Carbon::setTestNow(
            '2026-09-15 09:00:00'
        );

        $plan = $this->crearPlan();

        $sindicato =
            Sindicato::query()->create([
                'nombre' =>
                    'Sindicato Liquidación',
            ]);

        $suscripcionUno =
            $this->crearSuscripcion(
                $sindicato,
                $plan,
                '920005',
                '2026-09-30'
            );

        $suscripcionDos =
            $this->crearSuscripcion(
                $sindicato,
                $plan,
                '920006',
                '2026-09-30'
            );

        $this->crearPago(
            $suscripcionUno,
            'Pagado',
            'sindicato',
            15
        );

        $this->crearPago(
            $suscripcionDos,
            'Pagado',
            'sindicato',
            15
        );

        $liquidacion =
            LiquidacionMotrix::query()->create([
                'id_sindicato' =>
                    $sindicato->id,
                'periodo' =>
                    '2026-09',
                'monto_declarado' =>
                    30,
                'monto_transferido' =>
                    10,
                'estado' =>
                    'Parcial',
            ]);

        TransferenciaLiquidacionMotrix::query()
            ->create([
                'liquidacion_id' =>
                    $liquidacion->id,
                'monto' =>
                    10,
                'forma_pago' =>
                    'Transferencia',
                'estado' =>
                    'Validada',
                'fecha_transferencia' =>
                    now(),
                'validado_en' =>
                    now(),
            ]);

        TransferenciaLiquidacionMotrix::query()
            ->create([
                'liquidacion_id' =>
                    $liquidacion->id,
                'monto' =>
                    5,
                'forma_pago' =>
                    'QR',
                'estado' =>
                    'Pendiente',
                'fecha_transferencia' =>
                    now(),
            ]);

        Sanctum::actingAs(
            User::factory()->create([
                'role' =>
                    'admin_general',
            ])
        );

        $this->getJson(
            '/api/suscripciones-motrix/panel'
            . '?periodo=2026-09'
            . '&id_sindicato='
            . $sindicato->id
        )
            ->assertOk()
            ->assertJsonPath(
                'data.resumen.cobranza.recaudado_sindicato',
                '30.00'
            )
            ->assertJsonPath(
                'data.resumen.liquidaciones.liquidado_validado',
                '10.00'
            )
            ->assertJsonPath(
                'data.resumen.liquidaciones.pendiente_validacion',
                '5.00'
            )
            ->assertJsonPath(
                'data.resumen.liquidaciones.pendiente_liquidar',
                '20.00'
            )
            ->assertJsonPath(
                'data.resumen.liquidaciones.disponible_transferir',
                '15.00'
            );
    }

    private function crearPlan(): PlanSuscripcionMotrix
    {
        return PlanSuscripcionMotrix::query()
            ->create([
                'nombre' =>
                    'MOTRIX Conductor',
                'descripcion' =>
                    'Suscripción mensual',
                'monto' =>
                    15,
                'duracion_meses' =>
                    1,
                'dias_gracia' =>
                    3,
                'aviso_dias_antes' =>
                    7,
                'activo' =>
                    true,
            ]);
    }

    private function crearSuscripcion(
        Sindicato $sindicato,
        PlanSuscripcionMotrix $plan,
        string $ci,
        string $vencimiento
    ): SuscripcionMotrix {
        $persona =
            Persona::query()->create([
                'nombre' =>
                    'Conductor',
                'apellidos' =>
                    'Panel',
                'ci' =>
                    $ci,
                'telefono' =>
                    '70000000',
            ]);

        $mototaxista =
            Mototaxista::query()->create([
                'nro_chaleco' =>
                    substr(
                        $ci,
                        -4
                    ),
                'telefono' =>
                    '70000000',
                'estado' =>
                    'Activo',
                'id_persona' =>
                    $persona->id,
                'id_sindicato' =>
                    $sindicato->id,
            ]);

        return SuscripcionMotrix::query()
            ->create([
                'id_mototaxista' =>
                    $mototaxista->id,
                'id_sindicato' =>
                    $sindicato->id,
                'plan_id' =>
                    $plan->id,
                'fecha_inicio' =>
                    '2026-09-01',
                'fecha_vencimiento' =>
                    $vencimiento,
                'estado' =>
                    'Activa',
            ]);
    }

    private function crearPago(
        SuscripcionMotrix $suscripcion,
        string $estado,
        string $canal,
        float $monto
    ): PagoSuscripcionMotrix {
        return PagoSuscripcionMotrix::query()
            ->create([
                'suscripcion_id' =>
                    $suscripcion->id,
                'id_mototaxista' =>
                    $suscripcion
                        ->id_mototaxista,
                'id_sindicato' =>
                    $suscripcion
                        ->id_sindicato,
                'periodo' =>
                    '2026-09',
                'monto_esperado' =>
                    15,
                'monto_pagado' =>
                    $monto,
                'fecha_vencimiento' =>
                    '2026-09-30',
                'fecha_pago' =>
                    $estado === 'Pagado'
                        ? now()
                        : null,
                'estado' =>
                    $estado,
                'forma_pago' =>
                    $estado === 'Pagado'
                        ? 'QR'
                        : null,
                'canal_cobro' =>
                    $canal,
            ]);
    }
}
