<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PersonaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        $reglaCiUnico = Rule::unique(
            'personas',
            'ci'
        );

        if ($id !== null && $id !== '') {
            $reglaCiUnico->ignore((int) $id);
        }

        return [
            'ci' => [
                'required',
                'string',
                'max:20',
                $reglaCiUnico,
            ],
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],
            'apellidos' => [
                'nullable',
                'string',
                'max:100',
            ],
            'telefono' => [
                'nullable',
                'string',
                'max:20',
            ],
            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ci.required' => 'El CI es obligatorio.',
            'ci.unique' => 'El CI ya se encuentra registrado.',
            'ci.max' => 'El CI no debe superar los 20 caracteres.',

            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no debe superar los 100 caracteres.',

            'apellidos.max' => 'Los apellidos no deben superar los 100 caracteres.',
            'telefono.max' => 'El teléfono no debe superar los 20 caracteres.',
            'direccion.max' => 'La dirección no debe superar los 255 caracteres.',
        ];
    }
}
