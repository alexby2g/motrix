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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MotrixSubscriptionLiquidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_liquidacion_incluye_solo_pagos_cobrados_por_sindicato(): void
    {
        $sindicato =
            $this->crearSindicato(
                'Sindicato Central'
            );

        $this->crearPago(
            $sindicato,
            '920001',
            'sindicato'
        );

        $this->crearPago(
            $sindicato,
            '920002',
            'sindicato'
        );

        $this->crearPago(
            $sindicato,
            '920003',
            'motrix_directo'
        );

        $secretario =
            $this->crearSecretario(
                $sindicato
            );

        Sanctum::actingAs(
            $secretario
        );

        $respuesta =
            $this->postJson(
                '/api/liquidaciones-motrix/preparar',
                [
                    'periodo' =>
                        '2026-09',
                ]
            )
                ->assertCreated()
                ->assertJsonPath(
                    'data.monto_declarado',
                    '30.00'
                )
                ->assertJsonPath(
                    'data.monto_transferido',
                    '0.00'
                );

        $liquidacionId =
            (int)
            $respuesta->json(
                'data.id'
            );

        $this->assertDatabaseCount(
            'liquidacion_pagos_motrix',
            2
        );

        $this->assertDatabaseHas(
            'liquidaciones_motrix',
            [
                'id' =>
                    $liquidacionId,
                'id_sindicato' =>
                    $sindicato->id,
                'periodo' =>
                    '2026-09',
                'monto_declarado' =>
                    30,
                'estado' =>
                    'Pendiente',
            ]
        );

        $this->getJson(
            '/api/liquidaciones-motrix/resumen?periodo=2026-09'
        )
            ->assertOk()
            ->assertJsonPath(
                'data.recaudado_sindicato',
                '30.00'
            )
            ->assertJsonPath(
                'data.motrix_directo',
                '15.00'
            )
            ->assertJsonPath(
                'data.pendiente_liquidar',
                '30.00'
            );
    }

    public function test_secretario_no_puede_preparar_liquidacion_de_otro_sindicato(): void
    {
        $propio =
            $this->crearSindicato(
                'Sindicato Propio'
            );

        $ajeno =
            $this->crearSindicato(
                'Sindicato Ajeno'
            );

        $this->crearPago(
            $ajeno,
            '920010',
            'sindicato'
        );

        Sanctum::actingAs(
            $this->crearSecretario(
                $propio
            )
        );

        $this->postJson(
            '/api/liquidaciones-motrix/preparar',
            [
                'id_sindicato' =>
                    $ajeno->id,
                'periodo' =>
                    '2026-09',
            ]
        )->assertForbidden();

        $this->assertDatabaseMissing(
            'liquidaciones_motrix',
            [
                'id_sindicato' =>
                    $ajeno->id,
                'periodo' =>
                    '2026-09',
            ]
        );
    }

    public function test_liquidacion_parcial_se_actualiza_solo_al_validar_transferencia(): void
    {
        $sindicato =
            $this->crearSindicato(
                'Sindicato Parcial'
            );

        foreach (
            [
                '920021',
                '920022',
                '920023',
            ]
            as $ci
        ) {
            $this->crearPago(
                $sindicato,
                $ci,
                'sindicato'
            );
        }

        $secretario =
            $this->crearSecretario(
                $sindicato
            );

        Sanctum::actingAs(
            $secretario
        );

        $preparada =
            $this->postJson(
                '/api/liquidaciones-motrix/preparar',
                [
                    'periodo' =>
                        '2026-09',
                ]
            )
                ->assertCreated();

        $liquidacionId =
            (int)
            $preparada->json(
                'data.id'
            );

        $transferencia =
            $this->postJson(
                '/api/liquidaciones-motrix/'
                . $liquidacionId
                . '/transferencias',
                [
                    'monto' =>
                        30,
                    'forma_pago' =>
                        'Transferencia',
                    'referencia' =>
                        'TRX-001',
                    'fecha_transferencia' =>
                        '2026-09-30 10:00:00',
                ]
            )
                ->assertCreated()
                ->assertJsonPath(
                    'data.estado',
                    'Pendiente'
                );

        $transferenciaId =
            (int)
            $transferencia->json(
                'data.id'
            );

        $this->assertDatabaseHas(
            'liquidaciones_motrix',
            [
                'id' =>
                    $liquidacionId,
                'monto_declarado' =>
                    45,
                'monto_transferido' =>
                    0,
                'estado' =>
                    'Pendiente',
            ]
        );

        $admin =
            User::factory()->create([
                'role' =>
                    'admin_general',
            ]);

        Sanctum::actingAs(
            $admin
        );

        $this->postJson(
            '/api/transferencias-liquidacion-motrix/'
            . $transferenciaId
            . '/validar'
        )
            ->assertOk()
            ->assertJsonPath(
                'data.estado',
                'Validada'
            );

        $this->assertDatabaseHas(
            'liquidaciones_motrix',
            [
                'id' =>
                    $liquidacionId,
                'monto_declarado' =>
                    45,
                'monto_transferido' =>
                    30,
                'estado' =>
                    'Parcial',
            ]
        );
    }

    public function test_segunda_transferencia_completa_liquidacion(): void
    {
        $sindicato =
            $this->crearSindicato(
                'Sindicato Completo'
            );

        foreach (
            [
                '920031',
                '920032',
                '920033',
            ]
            as $ci
        ) {
            $this->crearPago(
                $sindicato,
                $ci,
                'sindicato'
            );
        }

        Sanctum::actingAs(
            $this->crearSecretario(
                $sindicato
            )
        );

        $preparada =
            $this->postJson(
                '/api/liquidaciones-motrix/preparar',
                [
                    'periodo' =>
                        '2026-09',
                ]
            )
                ->assertCreated();

        $liquidacionId =
            (int)
            $preparada->json(
                'data.id'
            );

        $primera =
            $this->postJson(
                '/api/liquidaciones-motrix/'
                . $liquidacionId
                . '/transferencias',
                [
                    'monto' =>
                        30,
                    'forma_pago' =>
                        'QR',
                    'fecha_transferencia' =>
                        '2026-09-29 12:00:00',
                ]
            )
                ->assertCreated();

        $admin =
            User::factory()->create([
                'role' =>
                    'admin_general',
            ]);

        Sanctum::actingAs(
            $admin
        );

        $this->postJson(
            '/api/transferencias-liquidacion-motrix/'
            . $primera->json('data.id')
            . '/validar'
        )->assertOk();

        Sanctum::actingAs(
            $this->crearSecretario(
                $sindicato
            )
        );

        $segunda =
            $this->postJson(
                '/api/liquidaciones-motrix/'
                . $liquidacionId
                . '/transferencias',
                [
                    'monto' =>
                        15,
                    'forma_pago' =>
                        'Deposito',
                    'fecha_transferencia' =>
                        '2026-09-30 14:00:00',
                ]
            )
                ->assertCreated();

        Sanctum::actingAs(
            $admin
        );

        $this->postJson(
            '/api/transferencias-liquidacion-motrix/'
            . $segunda->json('data.id')
            . '/validar'
        )->assertOk();

        $this->assertDatabaseHas(
            'liquidaciones_motrix',
            [
                'id' =>
                    $liquidacionId,
                'monto_declarado' =>
                    45,
                'monto_transferido' =>
                    45,
                'estado' =>
                    'Liquidada',
            ]
        );
    }

    public function test_no_permite_transferir_mas_del_saldo_disponible(): void
    {
        $sindicato =
            $this->crearSindicato(
                'Sindicato Limite'
            );

        $this->crearPago(
            $sindicato,
            '920041',
            'sindicato'
        );

        $this->crearPago(
            $sindicato,
            '920042',
            'sindicato'
        );

        Sanctum::actingAs(
            $this->crearSecretario(
                $sindicato
            )
        );

        $preparada =
            $this->postJson(
                '/api/liquidaciones-motrix/preparar',
                [
                    'periodo' =>
                        '2026-09',
                ]
            )
                ->assertCreated();

        $liquidacionId =
            (int)
            $preparada->json(
                'data.id'
            );

        $this->postJson(
            '/api/liquidaciones-motrix/'
            . $liquidacionId
            . '/transferencias',
            [
                'monto' =>
                    31,
                'forma_pago' =>
                    'QR',
                'fecha_transferencia' =>
                    '2026-09-30 10:00:00',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'monto'
            );

        $this->assertDatabaseCount(
            'transferencias_liquidacion_motrix',
            0
        );
    }

    public function test_transferencia_observada_no_se_suma_y_libera_saldo(): void
    {
        $sindicato =
            $this->crearSindicato(
                'Sindicato Observado'
            );

        $this->crearPago(
            $sindicato,
            '920051',
            'sindicato'
        );

        Sanctum::actingAs(
            $this->crearSecretario(
                $sindicato
            )
        );

        $preparada =
            $this->postJson(
                '/api/liquidaciones-motrix/preparar',
                [
                    'periodo' =>
                        '2026-09',
                ]
            )
                ->assertCreated();

        $liquidacionId =
            (int)
            $preparada->json(
                'data.id'
            );

        $transferencia =
            $this->postJson(
                '/api/liquidaciones-motrix/'
                . $liquidacionId
                . '/transferencias',
                [
                    'monto' =>
                        15,
                    'forma_pago' =>
                        'QR',
                    'fecha_transferencia' =>
                        '2026-09-30 10:00:00',
                ]
            )
                ->assertCreated();

        Sanctum::actingAs(
            User::factory()->create([
                'role' =>
                    'admin_general',
            ])
        );

        $this->postJson(
            '/api/transferencias-liquidacion-motrix/'
            . $transferencia->json('data.id')
            . '/observar',
            [
                'observacion' =>
                    'Comprobante ilegible.',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.estado',
                'Observada'
            );

        $liquidacion =
            LiquidacionMotrix::query()
                ->findOrFail(
                    $liquidacionId
                );

        $this->assertSame(
            '0.00',
            $liquidacion
                ->monto_transferido
        );

        $this->assertSame(
            'Pendiente',
            $liquidacion->estado
        );

        $this->assertDatabaseHas(
            'transferencias_liquidacion_motrix',
            [
                'id' =>
                    $transferencia
                        ->json('data.id'),
                'estado' =>
                    'Observada',
            ]
        );
    }

    private function crearSindicato(
        string $nombre
    ): Sindicato {
        return Sindicato::query()
            ->create([
                'nombre' =>
                    $nombre,
            ]);
    }

    private function crearSecretario(
        Sindicato $sindicato
    ): User {
        return User::factory()->create([
            'role' =>
                'secretario',
            'sindicato_id' =>
                $sindicato->id,
        ]);
    }

    private function crearPago(
        Sindicato $sindicato,
        string $ci,
        string $canal
    ): PagoSuscripcionMotrix {
        $persona =
            Persona::query()->create([
                'nombre' =>
                    'Conductor',
                'apellidos' =>
                    $ci,
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

        $plan =
            PlanSuscripcionMotrix::query()
                ->create([
                    'nombre' =>
                        'MOTRIX Conductor '
                        . $ci,
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

        $suscripcion =
            SuscripcionMotrix::query()
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
                        '2026-09-30',
                    'estado' =>
                        'Activa',
                ]);

        return
            PagoSuscripcionMotrix::query()
                ->create([
                    'suscripcion_id' =>
                        $suscripcion->id,
                    'id_mototaxista' =>
                        $mototaxista->id,
                    'id_sindicato' =>
                        $sindicato->id,
                    'periodo' =>
                        '2026-09',
                    'monto_esperado' =>
                        15,
                    'monto_pagado' =>
                        15,
                    'fecha_vencimiento' =>
                        '2026-09-30',
                    'fecha_pago' =>
                        '2026-09-10 10:00:00',
                    'estado' =>
                        'Pagado',
                    'forma_pago' =>
                        'QR',
                    'canal_cobro' =>
                        $canal,
                ]);
    }
}
