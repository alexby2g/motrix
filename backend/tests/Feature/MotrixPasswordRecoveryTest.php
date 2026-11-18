<?php

namespace Tests\Feature;

use App\Models\Pasajero;
use App\Models\PasswordResetOtp;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MotrixPasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private function habilitarSmsDePruebas(): void
    {
        config([
            'services.motrix_sms.twilio_account_sid' => 'AC' . str_repeat('1', 32),
            'services.motrix_sms.twilio_auth_token' => 'token-solo-pruebas',
            'services.motrix_sms.twilio_from' => '+15005550006',
            'services.motrix_sms.twilio_messaging_service_sid' => null,
            'services.motrix_sms.webhook_url' => null,
            'services.motrix_sms.token' => null,
        ]);

        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM-motrix-test'], 201),
        ]);
    }

    public function test_no_asegura_envio_si_el_proveedor_sms_no_esta_configurado(): void
    {
        config([
            'services.motrix_sms.twilio_account_sid' => null,
            'services.motrix_sms.twilio_auth_token' => null,
            'services.motrix_sms.twilio_from' => null,
            'services.motrix_sms.twilio_messaging_service_sid' => null,
            'services.motrix_sms.webhook_url' => null,
        ]);

        [, $usuario] = $this->crearPasajero('sin-sms@example.com', '70000001', '200001');
        $this->postJson('/api/auth/recuperacion/solicitar', [
            'login' => '70000001',
        ])->assertStatus(503);

        $this->assertDatabaseMissing('password_reset_otps', ['user_id' => $usuario->id]);
    }

    public function test_enviar_sms_solo_al_numero_asociado_a_la_cuenta(): void
    {
        $this->habilitarSmsDePruebas();
        [, $usuario] = $this->crearPasajero('persona@example.com', '70000002', '200002');

        $this->postJson('/api/auth/recuperacion/solicitar', [
            'login' => '+591 7000-0002',
            'email' => 'intruso@example.com',
            'to' => '79999999',
        ])->assertOk()->assertJsonStructure(['message']);

        $otp = PasswordResetOtp::query()->where('user_id', $usuario->id)->firstOrFail();
        $this->assertSame('sms', $otp->canal);
        $this->assertSame('****0002', $otp->destino_enmascarado);
        $this->assertSame(hash('sha256', '70000002'), $otp->identifier_hash);
        $this->assertNull($otp->reset_token_hash);
        $this->assertNull($otp->verified_at);

        Http::assertSent(function (HttpRequest $request) {
            return $request->url() ===
                'https://api.twilio.com/2010-04-01/Accounts/AC' . str_repeat('1', 32) . '/Messages.json'
                && $request['To'] === '+59170000002'
                && $request['From'] === '+15005550006'
                && ! str_contains((string) $request['Body'], 'intruso@example.com');
        });
        Http::assertSentCount(1);
    }

    public function test_numero_inexistente_no_revela_cuentas_ni_envia_sms(): void
    {
        $this->habilitarSmsDePruebas();
        $this->crearPasajero('existente@example.com', '70000003', '200003');

        $desconocido = $this->postJson('/api/auth/recuperacion/solicitar', [
            'login' => '79999999',
        ])->assertOk();

        $real = $this->postJson('/api/auth/recuperacion/solicitar', [
            'login' => '70000003',
        ])->assertOk();

        $this->assertSame($desconocido->json('message'), $real->json('message'));
        Http::assertSentCount(1);
    }

    public function test_no_se_crea_otp_si_el_sms_falla(): void
    {
        $this->habilitarSmsDePruebas();
        Http::fake(['api.twilio.com/*' => Http::response(['error' => 'rejected'], 403)]);
        [, $usuario] = $this->crearPasajero('rechazo@example.com', '70000004', '200004');

        $this->postJson('/api/auth/recuperacion/solicitar', [
            'login' => '70000004',
        ])->assertOk();

        $this->assertDatabaseMissing('password_reset_otps', ['user_id' => $usuario->id]);
    }

    public function test_no_reenvia_multiples_sms_en_el_mismo_minuto(): void
    {
        $this->habilitarSmsDePruebas();
        $this->crearPasajero('limite@example.com', '70000005', '200005');

        $this->postJson('/api/auth/recuperacion/solicitar', ['login' => '70000005'])->assertOk();
        $this->postJson('/api/auth/recuperacion/solicitar', ['login' => '+59170000005'])->assertOk();

        Http::assertSentCount(1);
    }

    public function test_rechaza_celular_ambiguo_y_no_envia_sms(): void
    {
        $this->habilitarSmsDePruebas();
        $this->crearPasajero('primero@example.com', '70000006', '200006');

        $persona = Persona::query()->create([
            'nombre' => 'Segundo',
            'apellidos' => 'Historico',
            'telefono' => '+591 7000-0006',
            'ci' => '200016',
        ]);
        $pasajero = Pasajero::query()->create([
            'email' => 'segundo@example.com',
            'password' => null,
            'id_persona' => $persona->id,
        ]);
        User::factory()->create([
            'name' => 'Segundo',
            'nickname' => 'historico-segundo',
            'email' => 'segundo@example.com',
            'role' => 'pasajero',
            'pasajero_id' => $pasajero->id,
            'persona_id' => $persona->id,
            'mototaxista_id' => null,
            'federacion_id' => null,
            'sindicato_id' => null,
        ]);

        $this->postJson('/api/auth/recuperacion/solicitar', ['login' => '70000006'])->assertOk();
        $this->assertDatabaseCount('password_reset_otps', 0);
        Http::assertNothingSent();
    }

    public function test_cinco_intentos_fallidos_bloquean_el_codigo_correcto(): void
    {
        [, $usuario] = $this->crearPasajero('intentos@example.com', '70000007', '200007');
        $this->crearOtpManual($usuario, '70000007', '123456');

        for ($intento = 1; $intento <= 5; $intento++) {
            $this->postJson('/api/auth/recuperacion/verificar', [
                'login' => '70000007',
                'codigo' => '000000',
            ])->assertStatus(422);
        }

        $this->assertSame(5, PasswordResetOtp::query()->where('user_id', $usuario->id)->value('attempts'));
        $this->postJson('/api/auth/recuperacion/verificar', [
            'login' => '70000007',
            'codigo' => '123456',
        ])->assertStatus(422);
    }

    public function test_codigo_correcto_genera_token_de_cambio_y_se_usa_una_vez(): void
    {
        [$pasajero, $usuario] = $this->crearPasajero('restablecer@example.com', '70000008', '200008');
        $this->crearOtpManual($usuario, '70000008', '123456');

        $respuesta = $this->postJson('/api/auth/recuperacion/verificar', [
            'login' => '+59170000008',
            'codigo' => '123456',
        ])->assertOk()->assertJsonStructure(['reset_token']);

        $token = $respuesta->json('reset_token');
        $this->assertSame(64, strlen($token));

        $payload = [
            'login' => '70000008',
            'reset_token' => $token,
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
        ];

        $this->postJson('/api/auth/recuperacion/restablecer', $payload)->assertOk();
        $usuario->refresh();
        $pasajero->refresh();

        $this->assertTrue(Hash::check('NuevaClave123!', $usuario->password));
        $this->assertTrue(Hash::check('NuevaClave123!', $pasajero->password));

        $otp = PasswordResetOtp::query()->where('user_id', $usuario->id)->firstOrFail();
        $this->assertNotNull($otp->consumed_at);
        $this->assertNull($otp->reset_token_hash);

        $this->postJson('/api/auth/recuperacion/restablecer', $payload)->assertStatus(422);
    }

    private function crearOtpManual(User $usuario, string $numero, string $codigo): void
    {
        PasswordResetOtp::query()->create([
            'user_id' => $usuario->id,
            'identifier_hash' => hash('sha256', $numero),
            'code_hash' => Hash::make($codigo),
            'reset_token_hash' => null,
            'canal' => 'sms',
            'destino_enmascarado' => '****' . substr($numero, -4),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'verified_at' => null,
            'consumed_at' => null,
        ]);
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
