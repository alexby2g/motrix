<?php

namespace Tests\Feature;

use App\Http\Requests\PersonaRequest;
use Illuminate\Validation\Rule;
use Tests\TestCase;

class PersonaRequestTest extends TestCase
{
    public function test_regla_ci_no_ignora_id_vacio_al_crear(): void
    {
        $request = PersonaRequest::create('/api/personas', 'POST');

        $rules = $request->rules();

        $this->assertArrayHasKey('ci', $rules);
        $this->assertInstanceOf(Rule::class, $rules['ci'][3]);
    }
}
