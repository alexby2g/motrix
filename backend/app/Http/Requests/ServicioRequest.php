<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServicioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hora_inicio' => 'required',
            'hora_fin' => 'required',
            'estado' => 'required|string',
            'id_solicitud' => 'required|exists:solicitudes,id',
            'id_mototaxista' => 'required|exists:mototaxistas,id'
        ];
    }
}