<?php

namespace Tests\Feature;

use App\Events\AlertaSuscripcionMotrixPublicada;
use App\Models\AlertaSuscripcionMotrix;
use App\Models\EjecucionAutomatizacionMotrix;
use App\Models\Mototaxista;
use App\Models\Persona;
use App\Models\PlanSuscripcionMotrix;
use App\Models\Sindicato;
use App\Models\SuscripcionMotrix;
use App\Models\User;
use App\Services\MotrixSubscriptionAutomationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MotrixSubscriptionAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_automatizacion_genera_publica_y_audita_alerta(): void
    {
        Carbon::setTestNow(
            '2026-09-28 08:00:00'
        );

        [
            $suscripcion,
            $sindicato,
            $mototaxista,
        ] = $this->crearSuscripcion();

        $usuario = User::factory()->create([
            'role' => 'conductor',
            'mototaxista_id' => $mototaxista->id,
            'persona_id' => $mototaxista->id_persona,
            'sindicato_id' => $sindicato->id,
        ]);

        Event::fake([
            AlertaSuscripcionMotrixPublicada::class,
        ]);

        $resultado = app(
            MotrixSubscriptionAutomationService::class
        )->ejecutar('test');

        $this->assertFalse($resultado['ocupada']);
        $this->assertSame('Completada', $resultado['estado']);
        $this->assertSame(1, $resultado['procesadas']);
        $this->assertSame(1, $resultado['alertas_creadas']);
        $this->assertSame(1, $resultado['alertas_enviadas']);
        $this->assertSame(0, $resultado['errores']);

        $alerta = AlertaSuscripcionMotrix::query()
            ->where(
                'suscripcion_id',
                $suscripcion->id
            )
            ->firstOrFail();

        $this->assertSame(
            'recordatorio_3d',
            $alerta->tipo
        );
        $this->assertSame('Enviada', $alerta->estado);
        $this->assertSame($usuario->id, $alerta->user_id);
        $this->assertNotNull($alerta->enviada_en);

        $this->assertDatabaseHas(
            'ejecuciones_automatizacion_motrix',
            [
                'id' => $resultado['ejecucion_id'],
                'origen' => 'test',
                'estado' => 'Completada',
                'procesadas' => 1,
                'alertas_creadas' => 1,
                'alertas_enviadas' => 1,
                'errores' => 0,
            ]
        );

        Event::assertDispatched(
            AlertaSuscripcionMotrixPublicada::class,
            fn ($evento) =>
                (int) $evento->alerta->id
                    === (int) $alerta->id
        );
    }

    public function test_automatizacion_es_idempotente_para_la_misma_alerta(): void
    {
        Carbon::setTestNow(
            '2026-09-28 08:00:00'
        );

        [
            $suscripcion,
            $sindicato,
            $mototaxista,
        ] = $this->crearSuscripcion();

        User::factory()->create([
            'role' => 'conductor',
            'mototaxista_id' => $mototaxista->id,
            'persona_id' => $mototaxista->id_persona,
            'sindicato_id' => $sindicato->id,
        ]);

        Event::fake([
            AlertaSuscripcionMotrixPublicada::class,
        ]);

        $servicio = app(
            MotrixSubscriptionAutomationService::class
        );

        $primera = $servicio->ejecutar('test');
        $segunda = $servicio->ejecutar('test');

        $this->assertSame(1, $primera['alertas_creadas']);
        $this->assertSame(1, $primera['alertas_enviadas']);
        $this->assertSame(0, $segunda['alertas_creadas']);
        $this->assertSame(0, $segunda['alertas_enviadas']);

        $this->assertSame(
            1,
            AlertaSuscripcionMotrix::query()
                ->where(
                    'suscripcion_id',
                    $suscripcion->id
                )
                ->where(
                    'tipo',
                    'recordatorio_3d'
                )
                ->count()
        );

        Event::assertDispatchedTimes(
            AlertaSuscripcionMotrixPublicada::class,
            1
        );
    }

    public function test_alerta_sin_usuario_se_conserva_y_se_reintenta(): void
    {
        Carbon::setTestNow(
            '2026-09-28 08:00:00'
        );

        [
            $suscripcion,
            $sindicato,
            $mototaxista,
        ] = $this->crearSuscripcion();

        Event::fake([
            AlertaSuscripcionMotrixPublicada::class,
        ]);

        $servicio = app(
            MotrixSubscriptionAutomationService::class
        );

        $primera = $servicio->ejecutar('test');

        $this->assertSame(1, $primera['alertas_sin_usuario']);
        $this->assertSame(0, $primera['alertas_enviadas']);

        $alerta = AlertaSuscripcionMotrix::query()
            ->where(
                'suscripcion_id',
                $suscripcion->id
            )
            ->firstOrFail();

        $this->assertSame('Pendiente', $alerta->estado);
        $this->assertNull($alerta->user_id);

        $usuario = User::factory()->create([
            'role' => 'conductor',
            'mototaxista_id' => $mototaxista->id,
            'persona_id' => $mototaxista->id_persona,
            'sindicato_id' => $sindicato->id,
        ]);

        $reintento =
            $servicio->publicarAlertasPendientes();

        $this->assertSame(1, $reintento['alertas_enviadas']);
        $this->assertSame(0, $reintento['alertas_sin_usuario']);

        $alerta->refresh();

        $this->assertSame('Enviada', $alerta->estado);
        $this->assertSame($usuario->id, $alerta->user_id);
        $this->assertNotNull($alerta->enviada_en);
    }

    public function test_solo_admin_general_puede_consultar_y_ejecutar_automatizacion(): void
    {
        Carbon::setTestNow(
            '2026-09-28 08:00:00'
        );

        [
            $suscripcion,
            $sindicato,
            $mototaxista,
        ] = $this->crearSuscripcion();

        User::factory()->create([
            'role' => 'conductor',
            'mototaxista_id' => $mototaxista->id,
            'persona_id' => $mototaxista->id_persona,
            'sindicato_id' => $sindicato->id,
        ]);

        Event::fake([
            AlertaSuscripcionMotrixPublicada::class,
        ]);

        $secretario = User::factory()->create([
            'role' => 'secretario',
            'sindicato_id' => $sindicato->id,
        ]);

        Sanctum::actingAs($secretario);

        $this->getJson(
            '/api/suscripciones-motrix/automatizacion'
        )->assertForbidden();

        $this->postJson(
            '/api/suscripciones-motrix/sincronizar'
        )->assertForbidden();

        $admin = User::factory()->create([
            'role' => 'admin_general',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson(
            '/api/suscripciones-motrix/automatizacion'
        )
            ->assertOk()
            ->assertJsonPath(
                'data.configurada',
                true
            );

        $respuesta = $this->postJson(
            '/api/suscripciones-motrix/sincronizar'
        )
            ->assertOk()
            ->assertJsonPath(
                'data.estado',
                'Completada'
            );

        $ejecucionId = (int) $respuesta->json(
            'data.ejecucion_id'
        );

        $this->assertGreaterThan(0, $ejecucionId);

        $this->assertDatabaseHas(
            'ejecuciones_automatizacion_motrix',
            [
                'id' => $ejecucionId,
                'origen' => 'manual',
                'ejecutado_por' => $admin->id,
            ]
        );
    }

    private function crearSuscripcion(): array
    {
        $sindicato = Sindicato::query()->create([
            'nombre' => 'Sindicato Automatización',
        ]);

        $persona = Persona::query()->create([
            'nombre' => 'Conductor',
            'apellidos' => 'Automatización',
            'ci' => '930001',
            'telefono' => '70000000',
        ]);

        $mototaxista = Mototaxista::query()->create([
            'nro_chaleco' => '3001',
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

        return [
            $suscripcion,
            $sindicato,
            $mototaxista,
            $persona,
            $plan,
        ];
    }
}
