<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MotrixLegalAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pasajero_debe_aceptar_version_legal_vigente(): void
    {
        $user = User::factory()->create([
            'role' => 'pasajero',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/legal/status')
            ->assertOk()
            ->assertJsonPath('data.required', true)
            ->assertJsonPath('data.accepted', false)
            ->assertJsonPath('data.terms_version', '1.0')
            ->assertJsonPath('data.privacy_version', '1.0');
    }

    public function test_pasajero_puede_aceptar_y_la_aceptacion_queda_auditada(): void
    {
        $user = User::factory()->create([
            'role' => 'pasajero',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/legal/accept', [
            'accepted_terms' => true,
            'accepted_privacy' => true,
            'terms_version' => '1.0',
            'privacy_version' => '1.0',
            'channel' => 'web',
        ])
            ->assertOk()
            ->assertJsonPath('data.accepted', true);

        $this->assertDatabaseHas('motrix_legal_acceptances', [
            'user_id' => $user->id,
            'role_at_acceptance' => 'pasajero',
            'terms_version' => '1.0',
            'privacy_version' => '1.0',
            'channel' => 'web',
        ]);

        $this->getJson('/api/legal/status')
            ->assertOk()
            ->assertJsonPath('data.accepted', true);
    }

    public function test_conductor_tambien_debe_aceptar(): void
    {
        $user = User::factory()->create([
            'role' => 'conductor',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/legal/status')
            ->assertOk()
            ->assertJsonPath('data.required', true)
            ->assertJsonPath('data.accepted', false);
    }

    public function test_administrador_no_necesita_aceptacion_operativa(): void
    {
        $user = User::factory()->create([
            'role' => 'admin_general',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/legal/status')
            ->assertOk()
            ->assertJsonPath('data.required', false)
            ->assertJsonPath('data.accepted', true);
    }
}
