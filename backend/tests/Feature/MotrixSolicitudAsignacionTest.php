<?php

namespace Tests\Feature;

use App\Models\Mototaxista;
use App\Models\Pasajero;
use App\Models\Persona;
use App\Models\Solicitud;
use App\Models\User;
use App\Services\AsignacionConductorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MotrixSolicitudAsignacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $pdo = DB::connection()->getPdo();

        if (method_exists($pdo, 'sqliteCreateFunction')) {
            $pdo->sqliteCreateFunction(
                'LEAST',
                static fn (...$values) => min($values),
                -1
            );

            $pdo->sqliteCreateFunction(
                'GREATEST',
                static fn (...$values) => max($values),
                -1
            );

            $pdo->sqliteCreateFunction(
                'RADIANS',
                static fn ($value) => deg2rad((float) $value),
                1
            );

            $pdo->sqliteCreateFunction(
                'COS',
                static fn ($value) => cos((float) $value),
                1
            );

            $pdo->sqliteCreateFunction(
                'SIN',
                static fn ($value) => sin((float) $value),
                1
            );

            $pdo->sqliteCreateFunction(
                'ACOS',
                static fn ($value) => acos((float) $value),
                1
            );
        }
    }

    public function test_un_segundo_conductor_no_puede_aceptar_una_solicitud_reservada_a_otro(): void
    {
        $pasajero = $this->crearPasajero();

        [$paul] = $this->crearConductor(
            'Paul',
            'Prueba',
            'PAUL-001',
            '70001011'
        );

        [$manuel, $usuarioManuel] = $this->crearConductor(
            'Manuel',
            'Prueba',
            'MANUEL-001',
            '70001012'
        );

        $this->aceptarLegal($usuarioManuel);

        Event::fake();

        $solicitud = $this->crearSolicitud(
            $pasajero,
            $paul
        );

        Sanctum::actingAs($usuarioManuel);

        $this->postJson(
            "/api/conductor/solicitudes/{$solicitud->id}/aceptar"
        )->assertForbidden();

        $this->assertDatabaseHas('solicitudes', [
            'id' => $solicitud->id,
            'estado' => 'Buscando conductor',
            'mototaxista_id' => $paul->id,
        ]);

        $this->assertDatabaseMissing('solicitudes', [
            'id' => $solicitud->id,
            'mototaxista_id' => $manuel->id,
        ]);

        $this->assertDatabaseCount('servicios', 0);
    }

    public function test_despues_de_aceptar_el_conductor_asignado_otro_conductor_no_puede_tomar_el_viaje(): void
    {
        $pasajero = $this->crearPasajero();

        [$paul, $usuarioPaul] = $this->crearConductor(
            'Paul',
            'Prueba',
            'PAUL-002',
            '70001021'
        );

        [$manuel, $usuarioManuel] = $this->crearConductor(
            'Manuel',
            'Prueba',
            'MANUEL-002',
            '70001022'
        );

        $this->aceptarLegal($usuarioPaul);
        $this->aceptarLegal($usuarioManuel);

        Event::fake();

        $solicitud = $this->crearSolicitud(
            $pasajero,
            $paul
        );

        Sanctum::actingAs($usuarioPaul);

        $this->postJson(
            "/api/conductor/solicitudes/{$solicitud->id}/aceptar"
        )->assertOk();

        $this->assertDatabaseHas('solicitudes', [
            'id' => $solicitud->id,
            'estado' => 'Aceptado',
            'mototaxista_id' => $paul->id,
        ]);

        $this->assertDatabaseHas('servicios', [
            'id_solicitud' => $solicitud->id,
            'id_mototaxista' => $paul->id,
        ]);

        Sanctum::actingAs($usuarioManuel);

        $this->postJson(
            "/api/conductor/solicitudes/{$solicitud->id}/aceptar"
        )->assertForbidden();

        $this->assertDatabaseHas('solicitudes', [
            'id' => $solicitud->id,
            'estado' => 'Aceptado',
            'mototaxista_id' => $paul->id,
        ]);

        $this->assertDatabaseMissing('solicitudes', [
            'id' => $solicitud->id,
            'mototaxista_id' => $manuel->id,
        ]);

        $this->assertSame(
            1,
            (int) DB::table('servicios')
                ->where('id_solicitud', $solicitud->id)
                ->count()
        );
    }

    public function test_si_el_conductor_no_responde_en_30_segundos_se_reasigna_al_siguiente(): void
    {
        $inicio = Carbon::create(
            2026,
            9,
            11,
            19,
            0,
            0,
            'UTC'
        );

        Carbon::setTestNow($inicio);

        try {
            $pasajero = $this->crearPasajero();

            [$paul] = $this->crearConductor(
                'Paul',
                'Timeout',
                'PAUL-003',
                '70001031'
            );

            [$manuel] = $this->crearConductor(
                'Manuel',
                'Timeout',
                'MANUEL-003',
                '70001032'
            );

            $solicitud = $this->crearSolicitud(
                $pasajero,
                $paul
            );

            /** @var AsignacionConductorService $servicio */
            $servicio = app(AsignacionConductorService::class);

            $servicio->registrarSeguimientoAsignacion(
                $solicitud->id,
                $paul->id
            );

            Carbon::setTestNow(
                $inicio->copy()->addSeconds(29)
            );

            $antesDelTimeout = $servicio
                ->revisarAsignacionPendiente($solicitud->id);

            $this->assertSame(
                'esperando_respuesta',
                $antesDelTimeout['motivo']
            );
            $this->assertFalse($antesDelTimeout['cambio']);

            $this->assertDatabaseHas('solicitudes', [
                'id' => $solicitud->id,
                'estado' => 'Buscando conductor',
                'mototaxista_id' => $paul->id,
            ]);

            Carbon::setTestNow(
                $inicio->copy()->addSeconds(31)
            );

            $despuesDelTimeout = $servicio
                ->revisarAsignacionPendiente($solicitud->id);

            $this->assertTrue($despuesDelTimeout['cambio']);
            $this->assertSame(
                'timeout_reasignado',
                $despuesDelTimeout['motivo']
            );
            $this->assertNotNull(
                $despuesDelTimeout['conductor']
            );
            $this->assertSame(
                $manuel->id,
                (int) $despuesDelTimeout['conductor']->id
            );

            $this->assertDatabaseHas('solicitudes', [
                'id' => $solicitud->id,
                'estado' => 'Buscando conductor',
                'mototaxista_id' => $manuel->id,
            ]);

            $this->assertDatabaseMissing('solicitudes', [
                'id' => $solicitud->id,
                'mototaxista_id' => $paul->id,
            ]);

            $this->assertDatabaseCount('servicios', 0);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_panel_conductor_recibe_contador_de_30_segundos_y_reasigna_al_vencer(): void
    {
        $inicio = Carbon::create(
            2026,
            9,
            11,
            20,
            0,
            0,
            'UTC'
        );

        Carbon::setTestNow($inicio);

        try {
            $pasajero = $this->crearPasajero();

            [$paul, $usuarioPaul] = $this->crearConductor(
                'Paul',
                'Panel',
                'PAUL-004',
                '70001041'
            );

            [$manuel] = $this->crearConductor(
                'Manuel',
                'Panel',
                'MANUEL-004',
                '70001042'
            );

            $this->aceptarLegal($usuarioPaul);
            Event::fake();

            $solicitud = $this->crearSolicitud(
                $pasajero,
                $paul
            );

            /** @var AsignacionConductorService $servicio */
            $servicio = app(AsignacionConductorService::class);

            $servicio->registrarSeguimientoAsignacion(
                $solicitud->id,
                $paul->id
            );

            Carbon::setTestNow(
                $inicio->copy()->addSeconds(5)
            );

            Sanctum::actingAs($usuarioPaul);

            $respuesta = $this->getJson(
                '/api/conductor/solicitudes-disponibles'
            )->assertOk();

            $respuesta
                ->assertJsonPath('0.id', $solicitud->id)
                ->assertJsonPath('0.segundos_respuesta_total', 30)
                ->assertJsonPath('0.segundos_respuesta_restantes', 25);

            $this->assertNotEmpty(
                $respuesta->json('0.respuesta_expira_en')
            );

            Carbon::setTestNow(
                $inicio->copy()->addSeconds(31)
            );

            $this->getJson(
                '/api/conductor/solicitudes-disponibles'
            )
                ->assertOk()
                ->assertExactJson([]);

            $this->assertDatabaseHas('solicitudes', [
                'id' => $solicitud->id,
                'estado' => 'Buscando conductor',
                'mototaxista_id' => $manuel->id,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function aceptarLegal(User $usuario): void
    {
        Sanctum::actingAs($usuario);

        $this->postJson('/api/legal/accept', [
            'accepted_terms' => true,
            'accepted_privacy' => true,
            'terms_version' => '1.0',
            'privacy_version' => '1.0',
            'channel' => 'web',
        ])->assertOk();
    }

    private function crearPasajero(): Pasajero
    {
        $persona = Persona::query()->create([
            'nombre' => 'Pasajero',
            'apellidos' => 'Prueba',
            'telefono' => '70001001',
            'ci' => 'PAS-001',
            'direccion' => 'Trinidad',
        ]);

        return Pasajero::query()->create([
            'email' => 'pasajero.prueba@motrix.test',
            'password' => null,
            'id_persona' => $persona->id,
        ]);
    }

    private function crearConductor(
        string $nombre,
        string $apellido,
        string $ci,
        string $telefono
    ): array {
        $persona = Persona::query()->create([
            'nombre' => $nombre,
            'apellidos' => $apellido,
            'telefono' => $telefono,
            'ci' => $ci,
            'direccion' => 'Trinidad',
        ]);

        $mototaxista = Mototaxista::query()->create([
            'nro_chaleco' => null,
            'codigo_qr' => null,
            'telefono' => $telefono,
            'estado' => 'Activo',
            'documentacion_en_regla' => true,
            'aportes_al_dia' => true,
            'estado_sindical' => 'Habilitado',
            'disponible' => true,
            'latitud' => -14.8333000,
            'longitud' => -64.9000000,
            'ultima_conexion' => Carbon::now('UTC')
                ->format('Y-m-d H:i:s'),
            'id_persona' => $persona->id,
            'id_sindicato' => null,
        ]);

        $usuario = User::factory()->create([
            'name' => "{$nombre} {$apellido}",
            'nickname' => $telefono,
            'email' => strtolower($nombre)
                . '.'
                . strtolower($ci)
                . '@motrix.test',
            'role' => 'conductor',
            'mototaxista_id' => $mototaxista->id,
            'pasajero_id' => null,
            'persona_id' => $persona->id,
            'federacion_id' => null,
            'sindicato_id' => null,
        ]);

        return [$mototaxista, $usuario];
    }

    private function crearSolicitud(
        Pasajero $pasajero,
        Mototaxista $mototaxista
    ): Solicitud {
        return Solicitud::query()->create([
            'origen' => 'Punto de origen de prueba',
            'latitud_origen' => -14.8333000,
            'longitud_origen' => -64.9000000,
            'destino' => 'Punto de destino de prueba',
            'latitud_destino' => -14.8300000,
            'longitud_destino' => -64.8950000,
            'fecha' => Carbon::now('America/La_Paz')
                ->toDateString(),
            'estado' => 'Buscando conductor',
            'id_pasajero' => $pasajero->id,
            'precio' => 6.00,
            'distancia_km' => 1.00,
            'mototaxista_id' => $mototaxista->id,
            'metodo_pago' => 'Efectivo',
            'expira_en' => Carbon::now('UTC')
                ->addMinutes(3)
                ->format('Y-m-d H:i:s'),
        ]);
    }
}
