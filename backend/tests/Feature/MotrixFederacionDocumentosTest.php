<?php
namespace Tests\Feature;
use App\Models\Federacion;
use App\Models\Sindicato;
use App\Models\Persona;
use App\Models\Mototaxista;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
class MotrixFederacionDocumentosTest extends TestCase
{
    use RefreshDatabase;
    private function escenario(string $nombre, string $ci): array
    {
        $f = Federacion::create(['nombre' => $nombre]);
        $s = Sindicato::create(['nombre' => 'Sindicato '.$nombre, 'id_federacion' => $f->id]);
        $p = Persona::create(['nombre' => $nombre, 'apellidos' => 'Prueba', 'ci' => $ci, 'sindicato_registro_id' => $s->id]);
        $m = Mototaxista::create(['id_persona' => $p->id, 'id_sindicato' => $s->id, 'nro_chaleco' => $ci, 'estado' => 'Activo', 'disponible' => false]);
        return [$f, $s, $p, $m];
    }
    public function test_representante_ve_solo_su_federacion(): void
    {
        [$f, $s, , $m] = $this->escenario('Propia', '989001');
        [, $otra, , $otro] = $this->escenario('Ajena', '989002');
        Sanctum::actingAs(User::factory()->create(['role' => 'representante_federacion', 'federacion_id' => $f->id]));
        $this->getJson('/api/mototaxistas/'.$m->id)->assertOk();
        $this->getJson('/api/mototaxistas/'.$otro->id)->assertNotFound();
        $this->getJson('/api/sindicatos')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $s->id);
        $this->getJson('/api/sindicatos/'.$otra->id)->assertNotFound();
        $this->getJson('/api/personas')->assertOk()->assertJsonCount(1);
    }
    public function test_representante_no_modifica_ni_afilia_fuera(): void
    {
        [$f, , , $m] = $this->escenario('Propia', '989010');
        [, $otro] = $this->escenario('Ajena', '989011');
        $persona = Persona::create(['nombre' => 'Nueva', 'ci' => '989012']);
        Sanctum::actingAs(User::factory()->create(['role' => 'representante_federacion', 'federacion_id' => $f->id]));
        $this->deleteJson('/api/mototaxistas/'.$m->id)->assertForbidden();
        $this->putJson('/api/mototaxistas/'.$m->id, [])->assertForbidden();
        $this->postJson('/api/mototaxistas', ['id_persona' => $persona->id, 'id_sindicato' => $otro->id, 'nro_chaleco' => '77', 'estado' => 'Activo'])->assertUnprocessable()->assertJsonValidationErrors('id_sindicato');
    }
    public function test_exportacion_filtra_federacion_y_genera_tres_formatos(): void
    {
        [$f] = $this->escenario('VisibleDocumento', '989020');
        $this->escenario('OcultaDocumento', '989021');
        Sanctum::actingAs(User::factory()->create(['role' => 'representante_federacion', 'federacion_id' => $f->id]));
        $r = $this->get('/api/documentos/afiliados/word')->assertOk();
        $this->assertStringContainsString('VisibleDocumento', $r->getContent());
        $this->assertStringNotContainsString('OcultaDocumento', $r->getContent());
        $this->getJson('/api/documentos/viajes/word')->assertForbidden();
        $this->get('/api/documentos/afiliados/pdf')->assertOk()->assertHeader('content-type', 'application/pdf');
        $excel = $this->get('/api/documentos/afiliados/excel')->assertOk();
        $this->assertStringStartsWith('PK', $excel->streamedContent());
    }
    public function test_cuenta_sin_federacion_falla_cerrada(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'representante_federacion', 'federacion_id' => null]));
        $this->getJson('/api/sindicatos')->assertForbidden();
        $this->getJson('/api/documentos/afiliados/word')->assertForbidden();
    }
    public function test_conductor_no_accede_al_modulo_documentos(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\EnsureMotrixLegalAccepted::class);
        [, , , $m] = $this->escenario('ConductorPropio', '989030');
        Sanctum::actingAs(User::factory()->create(['role' => 'conductor', 'mototaxista_id' => $m->id]));
        $this->getJson('/api/documentos')->assertForbidden();
        $this->get('/api/documentos/mi_registro/word')->assertForbidden();
    }
    public function test_representante_registra_afiliado_en_su_sindicato(): void
    {
        [$f, $s] = $this->escenario('PropiaRegistro', '989040');
        $persona = Persona::create(['nombre' => 'Nueva', 'ci' => '989041']);
        Sanctum::actingAs(User::factory()->create(['role' => 'representante_federacion', 'federacion_id' => $f->id]));
        $this->getJson('/api/personas/opciones-mototaxista?q=Nueva')->assertOk()->assertJsonCount(1);
        $this->postJson('/api/mototaxistas', ['id_persona' => $persona->id, 'id_sindicato' => $s->id, 'nro_chaleco' => '78', 'estado' => 'Activo'])->assertCreated();
        $this->assertDatabaseHas('mototaxistas', ['id_persona' => $persona->id, 'id_sindicato' => $s->id]);
    }
    public function test_pasajero_no_accede_al_modulo_documentos(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\EnsureMotrixLegalAccepted::class);
        $p = Persona::create(['nombre' => 'Pasajero', 'ci' => '989050']);
        $pasajero = \App\Models\Pasajero::create(['id_persona' => $p->id]);
        Sanctum::actingAs(User::factory()->create(['role' => 'pasajero', 'pasajero_id' => $pasajero->id]));
        $this->getJson('/api/documentos')->assertForbidden();
        $this->get('/api/documentos/mis_viajes/word?desde=2026-10-01&hasta=2026-10-01')->assertForbidden();
    }
    public function test_secretario_exporta_solo_su_sindicato(): void
    {
        [, $s] = $this->escenario('SindicatoPropio', '989060');
        $this->escenario('SindicatoAjeno', '989061');
        Sanctum::actingAs(User::factory()->create(['role' => 'secretario', 'sindicato_id' => $s->id]));
        $r = $this->get('/api/documentos/afiliados/word')->assertOk();
        $this->assertStringContainsString('SindicatoPropio', $r->getContent());
        $this->assertStringNotContainsString('SindicatoAjeno', $r->getContent());
    }

    public function test_persona_puede_registrarse_sin_ci_y_mototaxista_lo_exige(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\EnsureMotrixLegalAccepted::class);
        $f = Federacion::create(['nombre' => 'Federacion CI']);
        $sindicato = Sindicato::create(['nombre' => 'Sindicato CI', 'id_federacion' => $f->id]);
        Sanctum::actingAs(User::factory()->create(['role' => 'admin_registro']));

        $respuesta = $this->postJson('/api/personas', [
            'nombre' => 'Persona Sin CI',
            'apellidos' => 'Prueba',
            'telefono' => '70000001',
        ])->assertCreated();

        $personaId = (int) $respuesta->json('data.id');
        $this->assertDatabaseHas('personas', ['id' => $personaId, 'ci' => null]);

        $this->postJson('/api/mototaxistas', [
            'id_persona' => $personaId,
            'id_sindicato' => $sindicato->id,
            'nro_chaleco' => 'SINCI-1',
            'estado' => 'Activo',
        ])->assertUnprocessable()->assertJsonValidationErrors('id_persona');
    }

    public function test_mototaxista_afiliado_no_puede_quedarse_sin_ci(): void
    {
        [, , $persona] = $this->escenario('MototaxistaConCi', '989045');
        Sanctum::actingAs(User::factory()->create(['role' => 'admin_registro']));

        $this->putJson('/api/personas/'.$persona->id, [
            'nombre' => $persona->nombre,
            'apellidos' => $persona->apellidos,
            'ci' => '',
        ])->assertUnprocessable()->assertJsonValidationErrors('ci');

        $this->assertDatabaseHas('personas', [
            'id' => $persona->id,
            'ci' => '989045',
        ]);
    }

    public function test_pasajero_publico_puede_registrarse_sin_ci(): void
    {
        $respuesta = $this->postJson('/api/auth/registro-pasajero', [
            'nombre' => 'Pasajero',
            'apellidos' => 'Sin CI',
            'telefono' => '70000002',
            'email' => 'pasajero.sin.ci@motrix.test',
            'password' => 'Motrix123*',
            'password_confirmation' => 'Motrix123*',
            'device_name' => 'Prueba',
        ])->assertCreated();

        $personaId = (int) $respuesta->json('user.persona_id');
        $this->assertDatabaseHas('personas', ['id' => $personaId, 'ci' => null]);
    }

    public function test_actividad_institucional_no_expone_rutas_individuales(): void
    {
        [$f, , , $m] = $this->escenario('ActividadPropia', '989070');
        [, , , $otro] = $this->escenario('ActividadAjena', '989071');
        foreach ([[$m, 'OrigenPrivadoPropio'], [$otro, 'OrigenPrivadoAjeno']] as [$conductor, $origen]) {
            \App\Models\Solicitud::create(['mototaxista_id' => $conductor->id, 'origen' => $origen, 'destino' => 'Destino', 'fecha' => '2026-10-01', 'estado' => 'Finalizado']);
        }
        Sanctum::actingAs(User::factory()->create(['role' => 'representante_federacion', 'federacion_id' => $f->id]));
        $r = $this->get('/api/documentos/actividad/word?desde=2026-10-01&hasta=2026-10-01')->assertOk();
        $this->assertStringContainsString('Cantidad de viajes', $r->getContent());
        $this->assertStringNotContainsString('OrigenPrivado', $r->getContent());
    }

}
