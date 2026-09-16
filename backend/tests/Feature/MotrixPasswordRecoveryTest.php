<?php

namespace Tests\Feature;

use App\Models\Pasajero;
use App\Models\PasswordResetOtp;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MotrixPasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_recuperacion_por_celular_usa_el_correo_guardado_en_la_cuenta(): void
    {
        config([
            'mail.default' => 'array',
            'services.motrix_sms.webhook_url' => null,
            'services.motrix_sms.token' => null,
        ]);

        [, $usuario] = $this->crearPasajero(
            'recuperacion@example.com',
            '70000001',
            '200001'
        );

        $this->postJson('/api/auth/recuperacion/solicitar', [
            'login' => '70000001',
            'email' => 'intruso@example.com',
        ])
            ->assertOk()
            ->assertJsonStructure(['message'])
            ->assertJsonMissing(['reset_token']);

        $otp = PasswordResetOtp::query()
            ->where('user_id', $usuario->id)
            ->firstOrFail();

        $this->assertSame('email', $otp->canal);
        $this->assertNotNull($otp->destino_enmascarado);
        $this->assertStringEndsWith('@example.com', (string) $otp->destino_enmascarado);
        $this->assertStringNotContainsString('intruso', (string) $otp->destino_enmascarado);
    }

    public function test_cuenta_con_correo_interno_y_sin_sms_queda_sin_canal_configurado(): void
    {
        config([
            'mail.default' => 'array',
            'services.motrix_sms.webhook_url' => null,
            'services.motrix_sms.token' => null,
        ]);

        [, $usuario] = $this->crearPasajero(
            'pasajero.70000002@motrix.invalid',
            '70000002',
            '200002'
        );

        $this->postJson('/api/auth/recuperacion/solicitar', [
            'login' => '70000002',
        ])->assertOk();

        $otp = PasswordResetOtp::query()
            ->where('user_id', $usuario->id)
            ->firstOrFail();

        $this->assertSame('sin_configurar', $otp->canal);
        $this->assertNull($otp->destino_enmascarado);
    }

    public function test_correo_motrix_test_no_se_trata_como_destino_real(): void
    {
        config([
            'mail.default' => 'array',
            'services.motrix_sms.webhook_url' => null,
            'services.motrix_sms.token' => null,
        ]);

        [, $usuario] = $this->crearPasajero(
            'pasajero.70000006@motrix.test',
            '70000006',
            '200006'
        );

        $this->postJson('/api/auth/recuperacion/solicitar', [
            'login' => '70000006',
        ])->assertOk();

        $otp = PasswordResetOtp::query()
            ->where('user_id', $usuario->id)
            ->firstOrFail();

        $this->assertSame('sin_configurar', $otp->canal);
        $this->assertNull($otp->destino_enmascarado);
    }

    public function test_recuperacion_bloquea_celular_historico_asociado_a_mas_de_una_cuenta(): void
    {
        config([
            'mail.default' => 'array',
            'services.motrix_sms.webhook_url' => null,
            'services.motrix_sms.token' => null,
        ]);

        $this->crearPasajero(
            'primero@example.com',
            '70000007',
            '200007'
        );

        $persona = Persona::query()->create([
            'nombre' => 'Segundo',
            'apellidos' => 'Historico',
            'telefono' => '+591 7000-0007',
            'ci' => '200008',
        ]);

        $pasajero = Pasajero::query()->create([
            'email' => 'segundo@example.com',
            'password' => null,
            'id_persona' => $persona->id,
        ]);

        User::factory()->create([
            'name' => 'Segundo Historico',
            'nickname' => 'segundo-historico',
            'email' => 'segundo@example.com',
            'role' => 'pasajero',
            'pasajero_id' => $pasajero->id,
            'persona_id' => $persona->id,
            'mototaxista_id' => null,
            'federacion_id' => null,
            'sindicato_id' => null,
        ]);

        $this->postJson('/api/auth/recuperacion/solicitar', [
            'login' => '70000007',
        ])->assertOk()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseCount(
            'password_reset_otps',
            0
        );
    }

    public function test_recuperacion_reconoce_celular_historico_con_formato_bolivia(): void
    {
        config([
            'mail.default' => 'array',
            'services.motrix_sms.webhook_url' => null,
            'services.motrix_sms.token' => null,
        ]);

        $persona = Persona::query()->create([
            'nombre' => 'Formato',
            'apellidos' => 'Historico',
            'telefono' => '+591 7000-0008',
            'ci' => '200009',
        ]);

        $pasajero = Pasajero::query()->create([
            'email' => 'formato@example.com',
            'password' => null,
            'id_persona' => $persona->id,
        ]);

        $usuario = User::factory()->create([
            'name' => 'Formato Historico',
            'nickname' => 'formato-historico',
            'email' => 'formato@example.com',
            'role' => 'pasajero',
            'pasajero_id' => $pasajero->id,
            'persona_id' => $persona->id,
            'mototaxista_id' => null,
            'federacion_id' => null,
            'sindicato_id' => null,
        ]);

        $this->postJson('/api/auth/recuperacion/solicitar', [
            'login' => '70000008',
        ])->assertOk();

        $this->assertDatabaseHas(
            'password_reset_otps',
            [
                'user_id' => $usuario->id,
                'canal' => 'email',
            ]
        );
    }

    public function test_respuesta_no_revela_si_la_cuenta_existe(): void
    {
        config([
            'mail.default' => 'array',
            'services.motrix_sms.webhook_url' => null,
            'services.motrix_sms.token' => null,
        ]);

        $this->crearPasajero(
            'existente@example.com',
            '70000003',
            '200003'
        );

        $respuestaExistente = $this->postJson('/api/auth/recuperacion/solicitar', [
            'login' => '70000003',
        ])->assertOk();

        $respuestaInexistente = $this->postJson('/api/auth/recuperacion/solicitar', [
            'login' => '79999999',
        ])->assertOk();

        $this->assertSame(
            $respuestaExistente->json('message'),
            $respuestaInexistente->json('message')
        );
    }

    public function test_otp_se_bloquea_al_alcanzar_cinco_intentos_fallidos(): void
    {
        [, $usuario] = $this->crearPasajero(
            'intentos@example.com',
            '70000004',
            '200004'
        );

        PasswordResetOtp::query()->create([
            'user_id' => $usuario->id,
            'identifier_hash' => hash('sha256', '70000004'),
            'code_hash' => Hash::make('123456'),
            'reset_token_hash' => null,
            'canal' => 'email',
            'destino_enmascarado' => 'in******@example.com',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'verified_at' => null,
            'consumed_at' => null,
        ]);

        for ($intento = 1; $intento <= 5; $intento++) {
            $this->postJson('/api/auth/recuperacion/verificar', [
                'login' => '70000004',
                'codigo' => '000000',
            ])->assertStatus(422);
        }

        $this->assertSame(
            5,
            PasswordResetOtp::query()
                ->where('user_id', $usuario->id)
                ->value('attempts')
        );

        $this->postJson('/api/auth/recuperacion/verificar', [
            'login' => '70000004',
            'codigo' => '123456',
        ])->assertStatus(422);
    }

    public function test_token_verificado_permite_cambiar_password_una_sola_vez(): void
    {
        [$pasajero, $usuario] = $this->crearPasajero(
            'cambio@example.com',
            '70000005',
            '200005'
        );

        $token = str_repeat('a', 64);

        PasswordResetOtp::query()->create([
            'user_id' => $usuario->id,
            'identifier_hash' => hash('sha256', '70000005'),
            'code_hash' => Hash::make('123456'),
            'reset_token_hash' => hash('sha256', $token),
            'canal' => 'email',
            'destino_enmascarado' => 'ca****@example.com',
            'attempts' => 1,
            'expires_at' => now()->addMinutes(10),
            'verified_at' => now(),
            'consumed_at' => null,
        ]);

        $payload = [
            'login' => '70000005',
            'reset_token' => $token,
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
        ];

        $this->postJson('/api/auth/recuperacion/restablecer', $payload)
            ->assertOk();

        $usuario->refresh();
        $pasajero->refresh();

        $this->assertTrue(Hash::check('NuevaClave123!', $usuario->password));
        $this->assertTrue(Hash::check('NuevaClave123!', $pasajero->password));

        $otp = PasswordResetOtp::query()
            ->where('user_id', $usuario->id)
            ->firstOrFail();

        $this->assertNotNull($otp->consumed_at);
        $this->assertNull($otp->reset_token_hash);

        $this->postJson('/api/auth/recuperacion/restablecer', $payload)
            ->assertStatus(422);
    }

    private function crearPasajero(
        string $email,
        string $telefono,
        string $ci
    ): array {
        $persona = Persona::query()->create([
            'nombre' => 'Pasajero',
            'apellidos' => 'Recuperacion',
            'telefono' => $telefono,
            'ci' => $ci,
        ]);

        $pasajero = Pasajero::query()->create([
            'email' => $email,
            'password' => null,
            'id_persona' => $persona->id,
        ]);

        $usuario = User::factory()->create([
            'name' => 'Pasajero Recuperacion',
            'nickname' => $telefono,
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
