<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto' => 'required|numeric',
            'metodo' => 'required|string|max:50',
            'estado' => 'required|string|max:50',
            'id_servicio' => 'required|exists:servicios,id'
        ];
    }
}