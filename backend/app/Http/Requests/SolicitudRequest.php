<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'origen' => 'required|string|max:150',
            'latitud_origen' => 'nullable|numeric|between:-90,90',
            'longitud_origen' => 'nullable|numeric|between:-180,180',

            'destino' => 'required|string|max:150',
            'latitud_destino' => 'nullable|numeric|between:-90,90',
            'longitud_destino' => 'nullable|numeric|between:-180,180',

            'fecha' => 'required|date',
            'estado' => 'required|string|in:Pendiente,Aceptado,En Curso,Finalizado,Cancelado,Expirado',
            'id_pasajero' => 'required|exists:pasajeros,id',

            'precio' => 'nullable|numeric|min:0',
            'distancia_km' => 'nullable|numeric|min:0',
        ];
    }
}
