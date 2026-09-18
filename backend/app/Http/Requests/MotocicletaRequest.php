<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MotocicletaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tiene_placa' => filter_var(
                $this->input('tiene_placa', true),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            ) ?? false,
            'tiene_soat' => filter_var(
                $this->input('tiene_soat', false),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            ) ?? false,
        ]);
    }

    public function rules(): array
    {
        $id = (int) ($this->route('id') ?? 0);

        return [
            'tiene_placa' => [
                'required',
                'boolean',
            ],
            'placa' => [
                'nullable',
                Rule::requiredIf(
                    fn () => $this->boolean('tiene_placa')
                ),
                'string',
                'max:20',
                Rule::unique('motocicletas', 'placa')
                    ->ignore($id ?: null),
            ],
            'chasis' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('motocicletas', 'chasis')
                    ->ignore($id ?: null),
            ],
            'modelo' => [
                'required',
                'string',
                'max:100',
            ],
            'color' => [
                'required',
                'string',
                'max:50',
            ],
            'tiene_soat' => [
                'required',
                'boolean',
            ],
            'id_mototaxista' => [
                'required',
                'exists:mototaxistas,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'placa.required' =>
                'La placa es obligatoria cuando indicas que la motocicleta tiene placa.',
            'placa.unique' =>
                'La placa ya se encuentra registrada en otra motocicleta.',
            'chasis.unique' =>
                'El número de chasis ya se encuentra registrado.',
            'modelo.required' =>
                'El modelo de la motocicleta es obligatorio.',
            'color.required' =>
                'El color de la motocicleta es obligatorio.',
            'id_mototaxista.required' =>
                'Debes seleccionar al mototaxista responsable.',
        ];
    }
}
