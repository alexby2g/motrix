<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SindicatoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:100',
            'direccion' => 'required|string|max:150',
            'fecha_creacion' => 'required|date'
        ];
    }
}