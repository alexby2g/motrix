<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MotocicletaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'placa' => 'required|string|max:20',
            'modelo' => 'required|string|max:100',
            'color' => 'required|string|max:50',
            'id_mototaxista' => 'required|exists:mototaxistas,id'
        ];
    }
}