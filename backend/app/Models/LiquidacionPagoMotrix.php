<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidacionPagoMotrix extends Model
{
    protected $table = 'liquidacion_pagos_motrix';

    protected $fillable = [
        'liquidacion_id',
        'pago_suscripcion_id',
        'monto_incluido',
    ];

    protected $casts = [
        'liquidacion_id' => 'integer',
        'pago_suscripcion_id' => 'integer',
        'monto_incluido' => 'decimal:2',
    ];

    public function liquidacion(): BelongsTo
    {
        return $this->belongsTo(
            LiquidacionMotrix::class,
            'liquidacion_id'
        );
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(
            PagoSuscripcionMotrix::class,
            'pago_suscripcion_id'
        );
    }
}
