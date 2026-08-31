<?php

namespace Tests\Feature;

use App\Models\Mototaxista;
use App\Models\Persona;
use App\Models\PlanSuscripcionMotrix;
use App\Models\Sindicato;
use App\Models\SuscripcionMotrix;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MotrixSubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pasajero_no_puede_entrar_a_suscripcion_de_conductor(): void
    {
        $user = User::factory()->create([
            'role' => 'pasajero',
        ]);

        Sanctum::actingAs($user);

        $this->getJson(
            '/api/conductor/suscripcion-motrix'
        )->assertStatus(403);
    }

    public function test_conductor_sin_suscripcion_recibe_estado_no_configurado(): void
    {
        [$mototaxista, $user] =
            $this->crearConductor(
                'Sindicato Uno',
                '900001'
            );

        Sanctum::actingAs($user);

        $this->getJson(
            '/api/conductor/suscripcion-motrix'
        )
            ->assertOk()
            ->assertJson([
                'configurada' => false,
                'data' => null,
            ]);

        $this->assertNotNull(
            $mototaxista->id
        );
    }

    public function test_secretario_no_puede_configurar_suscripcion_de_otro_sindicato(): void
    {
        $sindicatoUno =
            Sindicato::query()->create([
                'nombre' => 'Sindicato Uno',
            ]);

        $sindicatoDos =
            Sindicato::query()->create([
                'nombre' => 'Sindicato Dos',
            ]);

        $persona =
            Persona::query()->create([
                'nombre' => 'Conductor',
                'apellidos' => 'Dos',
                'ci' => '900002',
            ]);

        $mototaxista =
            Mototaxista::query()->create([
                'nro_chaleco' => '22',
                'estado' => 'Activo',
                'id_persona' => $persona->id,
                'id_sindicato' => $sindicatoDos->id,
            ]);

        $secretario =
            User::factory()->create([
                'role' => 'secretario',
                'sindicato_id' => $sindicatoUno->id,
            ]);

        $plan =
            PlanSuscripcionMotrix::query()
                ->create([
                    'nombre' => 'MOTRIX Conductor',
                    'monto' => 15,
                    'duracion_meses' => 1,
                    'dias_gracia' => 3,
                    'aviso_dias_antes' => 7,
                    'activo' => true,
                ]);

        Sanctum::actingAs($secretario);

        $this->postJson(
            '/api/suscripciones-motrix/configurar',
            [
                'id_mototaxista' =>
                    $mototaxista->id,
                'plan_id' =>
                    $plan->id,
                'fecha_inicio' =>
                    '2026-09-01',
            ]
        )->assertStatus(403);
    }

    public function test_admin_general_puede_crear_plan_y_configurar_suscripcion(): void
    {
        [$mototaxista] =
            $this->crearConductor(
                'Sindicato Central',
                '900003'
            );

        $admin =
            User::factory()->create([
                'role' => 'admin_general',
            ]);

        Sanctum::actingAs($admin);

        $respuestaPlan =
            $this->postJson(
                '/api/planes-suscripcion-motrix',
                [
                    'nombre' =>
                        'MOTRIX Conductor',
                    'descripcion' =>
                        'Suscripción mensual del conductor.',
                    'monto' => 15,
                    'duracion_meses' => 1,
                    'dias_gracia' => 3,
                    'aviso_dias_antes' => 7,
                    'activo' => true,
                ]
            )
                ->assertCreated()
                ->assertJsonPath(
                    'data.monto',
                    '15.00'
                );

        $planId =
            (int) $respuestaPlan->json(
                'data.id'
            );

        $this->postJson(
            '/api/suscripciones-motrix/configurar',
            [
                'id_mototaxista' =>
                    $mototaxista->id,
                'plan_id' =>
                    $planId,
                'fecha_inicio' =>
                    '2026-09-01',
            ]
        )
            ->assertCreated()
            ->assertJsonPath(
                'data.id_mototaxista',
                $mototaxista->id
            )
            ->assertJsonPath(
                'data.fecha_vencimiento',
                '2026-09-30'
            );

        $this->assertDatabaseHas(
            'suscripciones_motrix',
            [
                'id_mototaxista' =>
                    $mototaxista->id,
                'plan_id' =>
                    $planId,
            ]
        );

        $suscripcion =
            SuscripcionMotrix::query()
                ->where(
                    'id_mototaxista',
                    $mototaxista->id
                )
                ->firstOrFail();

        $this->assertSame(
            '2026-09-01',
            $suscripcion->fecha_inicio
                ->toDateString()
        );

        $this->assertSame(
            '2026-09-30',
            $suscripcion->fecha_vencimiento
                ->toDateString()
        );
    }

    private function crearConductor(
        string $nombreSindicato,
        string $ci
    ): array {
        $sindicato =
            Sindicato::query()->create([
                'nombre' =>
                    $nombreSindicato,
            ]);

        $persona =
            Persona::query()->create([
                'nombre' => 'Conductor',
                'apellidos' => 'Prueba',
                'telefono' => '70000000',
                'ci' => $ci,
            ]);

        $mototaxista =
            Mototaxista::query()->create([
                'nro_chaleco' =>
                    substr($ci, -4),
                'telefono' => '70000000',
                'estado' => 'Activo',
                'id_persona' =>
                    $persona->id,
                'id_sindicato' =>
                    $sindicato->id,
            ]);

        $usuario =
            User::factory()->create([
                'name' =>
                    'Conductor Prueba',
                'role' =>
                    'conductor',
                'mototaxista_id' =>
                    $mototaxista->id,
                'persona_id' =>
                    $persona->id,
                'sindicato_id' =>
                    $sindicato->id,
            ]);

        return [
            $mototaxista,
            $usuario,
            $sindicato,
            $persona,
        ];
    }
}
