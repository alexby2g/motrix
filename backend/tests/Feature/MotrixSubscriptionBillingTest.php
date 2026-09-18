<?php

namespace Tests\Feature;

use App\Models\AlertaSuscripcionMotrix;
use App\Models\Mototaxista;
use App\Models\PagoSuscripcionMotrix;
use App\Models\Persona;
use App\Models\PlanSuscripcionMotrix;
use App\Models\Sindicato;
use App\Models\SuscripcionMotrix;
use App\Models\User;
use App\Services\MotrixSubscriptionBillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MotrixSubscriptionBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_genera_cuota_inicial_de_quince_bolivianos(): void
    {
        [$suscripcion] =
            $this->crearSuscripcion();

        $pago = app(
            MotrixSubscriptionBillingService::class
        )->asegurarCuotaInicial(
            $suscripcion
        );

        $this->assertSame(
            '2026-09',
            $pago->periodo
        );

        $this->assertSame(
            '15.00',
            $pago->monto_esperado
        );

        $this->assertSame(
            'Pendiente',
            $pago->estado
        );
    }

    public function test_pago_de_renovacion_extiende_la_suscripcion(): void
    {
        Carbon::setTestNow(
            '2026-09-25 10:00:00'
        );

        [
            $suscripcion,
            $sindicato,
        ] = $this->crearSuscripcion();

        $pago = app(
            MotrixSubscriptionBillingService::class
        )->asegurarRenovacion(
            $suscripcion
        );

        $secretario =
            User::factory()->create([
                'role' => 'secretario',
                'sindicato_id' =>
                    $sindicato->id,
            ]);

        Sanctum::actingAs(
            $secretario
        );

        $this->postJson(
            '/api/pagos-suscripcion-motrix/'
            . $pago->id
            . '/registrar',
            [
                'monto_pagado' => 15,
                'forma_pago' =>
                    'QR',
                'canal_cobro' =>
                    'sindicato',
                'referencia_pago' =>
                    'PRUEBA-001',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.estado',
                'Pagado'
            );

        $suscripcion->refresh();

        $this->assertSame(
            '2026-10-31',
            $suscripcion
                ->fecha_vencimiento
                ->toDateString()
        );

        $this->assertDatabaseHas(
            'pagos_suscripcion_motrix',
            [
                'id' =>
                    $pago->id,
                'estado' =>
                    'Pagado',
                'canal_cobro' =>
                    'sindicato',
            ]
        );
    }

    public function test_secretario_no_puede_cobrar_suscripcion_de_otro_sindicato(): void
    {
        [
            $suscripcion,
        ] = $this->crearSuscripcion(
            'Sindicato Dueño',
            '910001'
        );

        $pago = app(
            MotrixSubscriptionBillingService::class
        )->asegurarCuotaInicial(
            $suscripcion
        );

        $otroSindicato =
            Sindicato::query()->create([
                'nombre' =>
                    'Sindicato Ajeno',
            ]);

        $secretario =
            User::factory()->create([
                'role' =>
                    'secretario',
                'sindicato_id' =>
                    $otroSindicato->id,
            ]);

        Sanctum::actingAs(
            $secretario
        );

        $this->postJson(
            '/api/pagos-suscripcion-motrix/'
            . $pago->id
            . '/registrar',
            [
                'monto_pagado' => 15,
                'forma_pago' =>
                    'Efectivo',
                'canal_cobro' =>
                    'sindicato',
            ]
        )->assertNotFound();

        $this->assertDatabaseHas(
            'pagos_suscripcion_motrix',
            [
                'id' =>
                    $pago->id,
                'estado' =>
                    'Pendiente',
            ]
        );
    }

    public function test_secretario_no_puede_registrar_pago_como_motrix_directo(): void
    {
        [
            $suscripcion,
            $sindicato,
        ] = $this->crearSuscripcion(
            'Sindicato Canal',
            '910002'
        );

        $pago = app(
            MotrixSubscriptionBillingService::class
        )->asegurarCuotaInicial(
            $suscripcion
        );

        $secretario =
            User::factory()->create([
                'role' =>
                    'secretario',
                'sindicato_id' =>
                    $sindicato->id,
            ]);

        Sanctum::actingAs(
            $secretario
        );

        $this->postJson(
            '/api/pagos-suscripcion-motrix/'
            . $pago->id
            . '/registrar',
            [
                'monto_pagado' => 15,
                'forma_pago' =>
                    'QR',
                'canal_cobro' =>
                    'motrix_directo',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'canal_cobro'
            );

        $this->assertDatabaseHas(
            'pagos_suscripcion_motrix',
            [
                'id' =>
                    $pago->id,
                'estado' =>
                    'Pendiente',
                'monto_pagado' =>
                    0,
            ]
        );
    }

    public function test_admin_general_puede_registrar_pago_motrix_directo(): void
    {
        [
            $suscripcion,
        ] = $this->crearSuscripcion(
            'Sindicato Directo',
            '910003'
        );

        $pago = app(
            MotrixSubscriptionBillingService::class
        )->asegurarCuotaInicial(
            $suscripcion
        );

        Sanctum::actingAs(
            User::factory()->create([
                'role' =>
                    'admin_general',
            ])
        );

        $this->postJson(
            '/api/pagos-suscripcion-motrix/'
            . $pago->id
            . '/registrar',
            [
                'monto_pagado' => 15,
                'forma_pago' =>
                    'Transferencia',
                'canal_cobro' =>
                    'motrix_directo',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.canal_cobro',
                'motrix_directo'
            )
            ->assertJsonPath(
                'data.estado',
                'Pagado'
            );
    }

    public function test_sincronizacion_crea_recordatorio_de_vencimiento(): void
    {
        Carbon::setTestNow(
            '2026-09-28 08:00:00'
        );

        [
            $suscripcion,
            $sindicato,
            $mototaxista,
        ] = $this->crearSuscripcion();

        $usuario =
            User::factory()->create([
                'role' =>
                    'conductor',
                'mototaxista_id' =>
                    $mototaxista->id,
                'persona_id' =>
                    $mototaxista
                        ->id_persona,
                'sindicato_id' =>
                    $sindicato->id,
            ]);

        $resultado = app(
            MotrixSubscriptionBillingService::class
        )->sincronizarSuscripcion(
            $suscripcion
        );

        $this->assertNotNull(
            $resultado[
                'renovacion_id'
            ]
        );

        $alerta =
            AlertaSuscripcionMotrix::query()
                ->where(
                    'id_mototaxista',
                    $mototaxista->id
                )
                ->first();

        $this->assertNotNull(
            $alerta
        );

        $this->assertSame(
            'recordatorio_3d',
            $alerta->tipo
        );

        $this->assertSame(
            $usuario->id,
            $alerta->user_id
        );
    }

    public function test_conductor_puede_consultar_y_marcar_su_alerta(): void
    {
        Carbon::setTestNow(
            '2026-09-28 08:00:00'
        );

        [
            $suscripcion,
            $sindicato,
            $mototaxista,
        ] = $this->crearSuscripcion();

        $usuario =
            User::factory()->create([
                'role' =>
                    'conductor',
                'mototaxista_id' =>
                    $mototaxista->id,
                'persona_id' =>
                    $mototaxista
                        ->id_persona,
                'sindicato_id' =>
                    $sindicato->id,
            ]);

        app(
            MotrixSubscriptionBillingService::class
        )->sincronizarSuscripcion(
            $suscripcion
        );

        Sanctum::actingAs(
            $usuario
        );

        $respuesta =
            $this->getJson(
                '/api/conductor/suscripcion-motrix/alertas'
            )
                ->assertOk();

        $alertaId = (int) (
            $respuesta->json(
                'data.0.id'
            )
        );

        $this->assertGreaterThan(
            0,
            $alertaId
        );

        $this->postJson(
            '/api/conductor/suscripcion-motrix/alertas/'
            . $alertaId
            . '/leida'
        )
            ->assertOk()
            ->assertJsonPath(
                'data.estado',
                'Leida'
            );
    }

    private function crearSuscripcion(
        string $nombreSindicato = 'Sindicato Central',
        string $ci = '910000'
    ): array {
        $sindicato =
            Sindicato::query()->create([
                'nombre' =>
                    $nombreSindicato,
            ]);

        $persona =
            Persona::query()->create([
                'nombre' =>
                    'Conductor',
                'apellidos' =>
                    'Suscripcion',
                'ci' =>
                    $ci,
                'telefono' =>
                    '70000000',
            ]);

        $mototaxista =
            Mototaxista::query()->create([
                'nro_chaleco' =>
                    substr($ci, -4),
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

        return [
            $suscripcion,
            $sindicato,
            $mototaxista,
            $persona,
            $plan,
        ];
    }
}
