<?php

namespace Tests\Feature;

use App\Models\Pasajero;
use App\Models\Persona;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MotrixSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_pasajero_no_puede_entrar_a_usuarios_administrativos(): void
    {
        $user = User::factory()->create([
            'role' => 'pasajero',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/usuarios')
            ->assertStatus(403);
    }

    public function test_admin_servicios_no_puede_crear_personas_del_padron_maestro(): void
    {
        $user = User::factory()->create([
            'role' => 'admin_servicios',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/personas', [
            'nombre' => 'Persona',
            'apellidos' => 'Prueba',
            'ci' => '99112233',
        ])->assertStatus(403);
    }

    public function test_un_pasajero_no_puede_consultar_el_viaje_de_otro_pasajero(): void
    {
        [$pasajeroUno] = $this->crearPasajero('uno@example.com', '100001');
        [$pasajeroDos, $usuarioDos] = $this->crearPasajero('dos@example.com', '100002');

        $solicitud = Solicitud::query()->create([
            'origen' => 'Origen de prueba',
            'destino' => 'Destino de prueba',
            'estado' => 'Finalizado',
            'id_pasajero' => $pasajeroUno->id,
        ]);

        Sanctum::actingAs($usuarioDos);

        $this->getJson('/api/pasajero/solicitudes/' . $solicitud->id)
            ->assertStatus(404);
    }

    public function test_eliminacion_de_cuenta_bloquea_si_hay_viaje_activo(): void
    {
        [$pasajero, $usuario] = $this->crearPasajero('activo@example.com', '100003');

        Solicitud::query()->create([
            'origen' => 'Origen activo',
            'destino' => 'Destino activo',
            'estado' => 'En Curso',
            'id_pasajero' => $pasajero->id,
        ]);

        Sanctum::actingAs($usuario);

        $this->deleteJson('/api/pasajero/cuenta', [
            'confirmacion' => 'ELIMINAR',
            'email_confirmacion' => 'activo@example.com',
        ])->assertStatus(409);

        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
        ]);
    }

    public function test_pasajero_puede_eliminar_su_cuenta_y_conservar_historial_anonimizado(): void
    {
        [$pasajero, $usuario, $persona] = $this->crearPasajero('borrar@example.com', '100004');

        $solicitud = Solicitud::query()->create([
            'origen' => 'Origen histórico',
            'destino' => 'Destino histórico',
            'estado' => 'Finalizado',
            'id_pasajero' => $pasajero->id,
        ]);

        Sanctum::actingAs($usuario);

        $this->deleteJson('/api/pasajero/cuenta', [
            'confirmacion' => 'ELIMINAR',
            'email_confirmacion' => 'borrar@example.com',
        ])->assertOk();

        $this->assertDatabaseMissing('users', [
            'id' => $usuario->id,
        ]);

        $this->assertDatabaseHas('pasajeros', [
            'id' => $pasajero->id,
            'email' => null,
        ]);

        $this->assertDatabaseHas('personas', [
            'id' => $persona->id,
            'nombre' => 'Cuenta eliminada',
            'ci' => null,
        ]);

        $this->assertDatabaseHas('solicitudes', [
            'id' => $solicitud->id,
            'id_pasajero' => $pasajero->id,
        ]);
    }

    private function crearPasajero(
        string $email,
        string $ci
    ): array {
        $persona = Persona::query()->create([
            'nombre' => 'Pasajero',
            'apellidos' => 'Prueba',
            'telefono' => '70000000',
            'ci' => $ci,
        ]);

        $pasajero = Pasajero::query()->create([
            'email' => $email,
            'password' => null,
            'id_persona' => $persona->id,
        ]);

        $usuario = User::factory()->create([
            'name' => 'Pasajero Prueba',
            'email' => $email,
            'role' => 'pasajero',
            'pasajero_id' => $pasajero->id,
            'persona_id' => $persona->id,
            'mototaxista_id' => null,
            'federacion_id' => null,
            'sindicato_id' => null,
        ]);

        return [
            $pasajero,
            $usuario,
            $persona,
        ];
    }
}
