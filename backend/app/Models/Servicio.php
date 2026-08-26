<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    protected $table = 'servicios';

    public $timestamps = false;

    protected $fillable = [
        'hora_inicio',
        'hora_fin',
        'estado',
        'id_solicitud',
        'id_mototaxista',
    ];

    protected $casts = [
        'id' => 'integer',
        'id_solicitud' => 'integer',
        'id_mototaxista' => 'integer',
    ];

    /**
     * Solicitud que originó el servicio.
     */
    public function solicitud()
    {
        return $this->belongsTo(
            Solicitud::class,
            'id_solicitud'
        );
    }

    /**
     * Mototaxista que atendió el servicio.
     *
     * PagosPage.vue utiliza:
     * pago.servicio.mototaxista.persona.nombre
     */
    public function mototaxista()
    {
        return $this->belongsTo(
            Mototaxista::class,
            'id_mototaxista'
        );
    }

    /**
     * Pago asociado al servicio.
     */
    public function pago()
    {
        return $this->hasOne(
            Pago::class,
            'id_servicio'
        );
    }
}
