<?php

namespace Tests\Feature;

use App\Models\Pasajero;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MotrixPilotReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_pasajero_puede_registrarse_e_iniciar_sesion_con_celular(): void
    {
        $respuesta = $this->postJson('/api/auth/registro-pasajero-celular', [
            'nombre' => 'Piloto',
            'apellidos' => 'MOTRIX',
            'ci' => 'V570001',
            'telefono' => '7000-1001',
            'direccion' => 'Trinidad',
            'password' => 'Piloto1234',
            'password_confirmation' => 'Piloto1234',
            'device_name' => 'Prueba V5.7',
        ])->assertCreated();

        $respuesta->assertJsonPath('user.nickname', '70001001')
            ->assertJsonPath('user.telefono', '70001001')
            ->assertJsonPath('user.email', null);

        $this->postJson('/api/auth/login', [
            'login' => '+591 70001001',
            'password' => 'Piloto1234',
            'device_name' => 'Login V5.7',
        ])->assertOk()
            ->assertJsonPath('user.role', 'pasajero')
            ->assertJsonPath('user.nickname', '70001001');
    }

    public function test_registro_publico_rechaza_celular_que_ya_existe_en_otra_persona(): void
    {
        Persona::query()->create([
            'nombre' => 'Registro',
            'apellidos' => 'Existente',
            'telefono' => '+591 7000-1003',
            'ci' => 'V570003',
        ]);

        $this->postJson('/api/auth/registro-pasajero-celular', [
            'nombre' => 'Nuevo',
            'apellidos' => 'Pasajero',
            'ci' => 'V570004',
            'telefono' => '70001003',
            'direccion' => 'Trinidad',
            'password' => 'Piloto1234',
            'password_confirmation' => 'Piloto1234',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['telefono']);

        $this->assertDatabaseMissing('personas', [
            'ci' => 'V570004',
        ]);
    }

    public function test_ruta_publica_anterior_tambien_rechaza_celular_duplicado(): void
    {
        Persona::query()->create([
            'nombre' => 'Registro',
            'apellidos' => 'Anterior',
            'telefono' => '0059170001004',
            'ci' => 'V570005',
        ]);

        $this->postJson('/api/auth/registro-pasajero', [
            'nombre' => 'Intento',
            'apellidos' => 'Duplicado',
            'ci' => 'V570006',
            'telefono' => '7000-1004',
            'direccion' => 'Trinidad',
            'email' => 'intento.duplicado@example.com',
            'password' => 'Piloto1234',
            'password_confirmation' => 'Piloto1234',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['telefono']);

        $this->assertDatabaseMissing('personas', [
            'ci' => 'V570006',
        ]);
    }

    public function test_cuenta_de_pasajero_existente_puede_usar_el_celular_de_su_propia_persona(): void
    {
        $persona = Persona::query()->create([
            'nombre' => 'Cuenta',
            'apellidos' => 'Propia',
            'telefono' => '70001005',
            'ci' => 'V570007',
        ]);

        $pasajero = Pasajero::query()->create([
            'email' => 'pasajero.70001005@motrix.invalid',
            'password' => null,
            'id_persona' => $persona->id,
        ]);

        $admin = User::factory()->create([
            'role' => 'admin_general',
        ]);

        Sanctum::actingAs($admin);

        $this->postJson(
            "/api/pasajeros/{$pasajero->id}/cuenta-pasajero-celular",
            [
                'telefono' => '+591 70001005',
                'password' => 'Piloto1234',
            ]
        )->assertCreated()
            ->assertJsonPath(
                'user.nickname',
                '70001005'
            );

        $this->assertDatabaseHas('users', [
            'pasajero_id' => $pasajero->id,
            'nickname' => '70001005',
            'role' => 'pasajero',
        ]);
    }

    public function test_cuenta_de_pasajero_no_toma_el_celular_de_otra_persona_ni_modifica_su_ficha(): void
    {
        $persona = Persona::query()->create([
            'nombre' => 'Cuenta',
            'apellidos' => 'Objetivo',
            'telefono' => '70001006',
            'ci' => 'V570008',
        ]);

        $pasajero = Pasajero::query()->create([
            'email' => 'pasajero.70001006@motrix.invalid',
            'password' => null,
            'id_persona' => $persona->id,
        ]);

        Persona::query()->create([
            'nombre' => 'Otra',
            'apellidos' => 'Persona',
            'telefono' => '+591 7000-1007',
            'ci' => 'V570009',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin_general',
        ]);

        Sanctum::actingAs($admin);

        $this->postJson(
            "/api/pasajeros/{$pasajero->id}/cuenta-pasajero-celular",
            [
                'telefono' => '70001007',
                'password' => 'Piloto1234',
            ]
        )->assertStatus(422)
            ->assertJsonValidationErrors(['telefono']);

        $this->assertSame(
            '70001006',
            (string) $persona->fresh()->telefono
        );

        $this->assertDatabaseMissing('users', [
            'pasajero_id' => $pasajero->id,
        ]);
    }

    public function test_pasajero_puede_cambiar_su_password(): void
    {
        $user = User::factory()->create([
            'role' => 'pasajero',
            'password' => Hash::make('Anterior123'),
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/auth/cambiar-password', [
            'password_actual' => 'Anterior123',
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ])->assertOk();

        $this->assertTrue(
            Hash::check('NuevaClave123', $user->fresh()->password)
        );
    }

    public function test_eliminacion_por_password_conserva_historial_y_no_exige_correo_visible(): void
    {
        $persona = Persona::query()->create([
            'nombre' => 'Eliminar',
            'apellidos' => 'Piloto',
            'telefono' => '70001002',
            'ci' => 'V570002',
        ]);

        $pasajero = Pasajero::query()->create([
            'email' => 'pasajero.70001002@motrix.invalid',
            'password' => null,
            'id_persona' => $persona->id,
        ]);

        $user = User::factory()->create([
            'name' => 'Eliminar Piloto',
            'nickname' => '70001002',
            'email' => 'pasajero.70001002@motrix.invalid',
            'password' => Hash::make('Eliminar123'),
            'role' => 'pasajero',
            'pasajero_id' => $pasajero->id,
            'persona_id' => $persona->id,
            'mototaxista_id' => null,
            'federacion_id' => null,
            'sindicato_id' => null,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/pasajero/cuenta-segura', [
            'confirmacion' => 'ELIMINAR',
            'password_actual' => 'Eliminar123',
        ])->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
