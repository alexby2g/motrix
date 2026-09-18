<?php

namespace Tests\Feature;

use App\Models\Mototaxista;
use App\Models\PagoSuscripcionMotrix;
use App\Models\Persona;
use App\Models\PlanSuscripcionMotrix;
use App\Models\Sindicato;
use App\Models\SuscripcionMotrix;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MotrixSubscriptionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretario_solo_previsualiza_reportes_de_su_sindicato(): void
    {
        [$sindicatoA, $motoA, $suscripcionA] = $this->crearEscenario('Sindicato A', '920001');
        [$sindicatoB, $motoB, $suscripcionB] = $this->crearEscenario('Sindicato B', '920002');

        $this->crearPago($suscripcionA, $motoA, $sindicatoA, 'sindicato', 15);
        $this->crearPago($suscripcionB, $motoB, $sindicatoB, 'sindicato', 15);

        $secretario = User::factory()->create([
            'role' => 'secretario',
            'sindicato_id' => $sindicatoA->id,
        ]);

        Sanctum::actingAs($secretario);

        $respuesta = $this->getJson(
            '/api/reportes-suscripcion-motrix/preview?tipo=suscripciones&periodo=2026-09&id_sindicato=' . $sindicatoB->id
        )->assertOk();

        $filas = $respuesta->json('data.filas');
        $this->assertCount(1, $filas);
        $this->assertSame('Sindicato A', $filas[0]['sindicato']);
    }

    public function test_admin_general_separa_cobro_sindicato_y_motrix_directo(): void
    {
        [$sindicato, $motoA, $suscripcionA] = $this->crearEscenario('Sindicato Central', '920010');
        [, $motoB, $suscripcionB] = $this->crearEscenario('Sindicato Central', '920011', $sindicato);

        $this->crearPago($suscripcionA, $motoA, $sindicato, 'sindicato', 15);
        $this->crearPago($suscripcionB, $motoB, $sindicato, 'motrix_directo', 15);

        $admin = User::factory()->create(['role' => 'admin_general']);
        Sanctum::actingAs($admin);

        $this->getJson(
            '/api/reportes-suscripcion-motrix/preview?tipo=recaudacion&periodo=2026-09&id_sindicato=' . $sindicato->id
        )
            ->assertOk()
            ->assertJsonPath('data.filas.0.cobrado_sindicato', '15.00')
            ->assertJsonPath('data.filas.0.motrix_directo', '15.00')
            ->assertJsonPath('data.filas.0.recaudado_total', '30.00');
    }

    public function test_admin_general_puede_exportar_reporte_pdf(): void
    {
        [$sindicato, $mototaxista, $suscripcion] = $this->crearEscenario(
            'Sindicato PDF',
            '920015'
        );

        $this->crearPago(
            $suscripcion,
            $mototaxista,
            $sindicato,
            'sindicato',
            15
        );

        $admin = User::factory()->create([
            'role' => 'admin_general',
        ]);
        Sanctum::actingAs($admin);

        $respuesta = $this->get(
            '/api/reportes-suscripcion-motrix/suscripciones/pdf?periodo=2026-09&id_sindicato='
            . $sindicato->id
        );

        $respuesta->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $respuesta->headers->get('content-type')
        );
    }

    public function test_admin_general_puede_exportar_reporte_excel(): void
    {
        [$sindicato, $mototaxista, $suscripcion] = $this->crearEscenario(
            'Sindicato Excel',
            '920016'
        );

        $this->crearPago(
            $suscripcion,
            $mototaxista,
            $sindicato,
            'motrix_directo',
            15
        );

        $admin = User::factory()->create([
            'role' => 'admin_general',
        ]);
        Sanctum::actingAs($admin);

        $respuesta = $this->get(
            '/api/reportes-suscripcion-motrix/recaudacion/excel?periodo=2026-09&id_sindicato='
            . $sindicato->id
        );

        $respuesta->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $respuesta->headers->get('content-type')
        );
    }

    public function test_secretario_no_puede_generar_resumen_consolidado(): void
    {
        [$sindicato] = $this->crearEscenario('Sindicato Central', '920020');

        $secretario = User::factory()->create([
            'role' => 'secretario',
            'sindicato_id' => $sindicato->id,
        ]);
        Sanctum::actingAs($secretario);

        $this->getJson(
            '/api/reportes-suscripcion-motrix/preview?tipo=consolidado&periodo=2026-09'
        )->assertForbidden();
    }

    public function test_conductor_solo_previsualiza_su_historial(): void
    {
        [$sindicato, $mototaxista, $suscripcion] = $this->crearEscenario('Sindicato Central', '920030');
        $this->crearPago($suscripcion, $mototaxista, $sindicato, 'sindicato', 15);

        $conductor = User::factory()->create([
            'role' => 'conductor',
            'mototaxista_id' => $mototaxista->id,
            'persona_id' => $mototaxista->id_persona,
            'sindicato_id' => $sindicato->id,
        ]);
        Sanctum::actingAs($conductor);

        $this->getJson('/api/conductor/suscripcion-motrix/reporte')
            ->assertOk()
            ->assertJsonPath('data.filas.0.periodo', '2026-09')
            ->assertJsonPath('data.filas.0.pagado', '15.00');
    }

    private function crearEscenario(
        string $nombreSindicato,
        string $ci,
        ?Sindicato $sindicato = null
    ): array {
        $sindicato ??= Sindicato::query()->create(['nombre' => $nombreSindicato]);

        $persona = Persona::query()->create([
            'nombre' => 'Conductor',
            'apellidos' => $ci,
            'ci' => $ci,
            'telefono' => '70000000',
        ]);

        $mototaxista = Mototaxista::query()->create([
            'nro_chaleco' => substr($ci, -4),
            'telefono' => '70000000',
            'estado' => 'Activo',
            'id_persona' => $persona->id,
            'id_sindicato' => $sindicato->id,
        ]);

        $plan = PlanSuscripcionMotrix::query()->create([
            'nombre' => 'MOTRIX Conductor',
            'descripcion' => 'Suscripción mensual',
            'monto' => 15,
            'duracion_meses' => 1,
            'dias_gracia' => 3,
            'aviso_dias_antes' => 7,
            'activo' => true,
        ]);

        $suscripcion = SuscripcionMotrix::query()->create([
            'id_mototaxista' => $mototaxista->id,
            'id_sindicato' => $sindicato->id,
            'plan_id' => $plan->id,
            'fecha_inicio' => '2026-09-01',
            'fecha_vencimiento' => '2026-09-30',
            'estado' => 'Activa',
        ]);

        return [$sindicato, $mototaxista, $suscripcion];
    }

    private function crearPago(
        SuscripcionMotrix $suscripcion,
        Mototaxista $mototaxista,
        Sindicato $sindicato,
        string $canal,
        float $monto
    ): PagoSuscripcionMotrix {
        return PagoSuscripcionMotrix::query()->create([
            'suscripcion_id' => $suscripcion->id,
            'id_mototaxista' => $mototaxista->id,
            'id_sindicato' => $sindicato->id,
            'periodo' => '2026-09',
            'monto_esperado' => 15,
            'monto_pagado' => $monto,
            'fecha_vencimiento' => '2026-09-30',
            'fecha_pago' => '2026-09-15 10:00:00',
            'estado' => 'Pagado',
            'forma_pago' => 'QR',
            'canal_cobro' => $canal,
        ]);
    }
}
