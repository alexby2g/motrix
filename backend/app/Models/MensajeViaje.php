<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MensajeViaje extends Model
{
    use HasFactory;

    protected $table = 'mensajes_viaje';

    public $timestamps = false;

    protected $fillable = [
        'solicitud_id',
        'remitente_usuario_id',
        'remitente_tipo',
        'remitente_nombre',
        'mensaje',
        'leido_pasajero_en',
        'leido_conductor_en',
        'creado_en',
    ];

    protected $casts = [
        'solicitud_id' => 'integer',
        'remitente_usuario_id' => 'integer',
        'leido_pasajero_en' => 'datetime',
        'leido_conductor_en' => 'datetime',
        'creado_en' => 'datetime',
    ];

    /**
     * Mantiene las horas del chat en el formato local guardado por MOTRIX.
     * Evita que Laravel convierta el valor a UTC al generar JSON y que el
     * navegador lo muestre cuatro horas antes en Bolivia.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function solicitud()
    {
        return $this->belongsTo(
            Solicitud::class,
            'solicitud_id'
        );
    }
}
