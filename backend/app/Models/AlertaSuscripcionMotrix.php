<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertaSuscripcionMotrix extends Model
{
    protected $table = 'alertas_suscripcion_motrix';

    protected $fillable = [
        'suscripcion_id',
        'pago_suscripcion_id',
        'id_mototaxista',
        'id_sindicato',
        'user_id',
        'tipo',
        'titulo',
        'mensaje',
        'canal',
        'estado',
        'programada_para',
        'enviada_en',
        'leida_en',
    ];

    protected $casts = [
        'suscripcion_id' => 'integer',
        'pago_suscripcion_id' => 'integer',
        'id_mototaxista' => 'integer',
        'id_sindicato' => 'integer',
        'user_id' => 'integer',
        'programada_para' => 'datetime',
        'enviada_en' => 'datetime',
        'leida_en' => 'datetime',
    ];

    public function suscripcion(): BelongsTo
    {
        return $this->belongsTo(
            SuscripcionMotrix::class,
            'suscripcion_id'
        );
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(
            PagoSuscripcionMotrix::class,
            'pago_suscripcion_id'
        );
    }

    public function mototaxista(): BelongsTo
    {
        return $this->belongsTo(
            Mototaxista::class,
            'id_mototaxista'
        );
    }

    public function sindicato(): BelongsTo
    {
        return $this->belongsTo(
            Sindicato::class,
            'id_sindicato'
        );
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}
