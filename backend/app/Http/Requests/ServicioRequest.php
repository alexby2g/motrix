<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServicioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $servicioId = $this->route('id');

        return [
            'hora_inicio' => [
                'required',
                'date_format:H:i',
            ],
            'hora_fin' => [
                'nullable',
                'date_format:H:i',
            ],
            'estado' => [
                'required',
                'string',
                Rule::in([
                    'Activo',
                    'En Curso',
                    'Finalizado',
                    'Cancelado',
                ]),
            ],
            'id_solicitud' => [
                'required',
                'integer',
                'exists:solicitudes,id',
                Rule::unique(
                    'servicios',
                    'id_solicitud'
                )->ignore($servicioId),
            ],
            'id_mototaxista' => [
                'required',
                'integer',
                'exists:mototaxistas,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_solicitud.unique' =>
                'La solicitud seleccionada ya tiene un servicio asociado.',
            'hora_inicio.date_format' =>
                'La hora de inicio debe tener un formato válido.',
            'hora_fin.date_format' =>
                'La hora de finalización debe tener un formato válido.',
        ];
    }
}
