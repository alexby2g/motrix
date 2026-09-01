<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferenciaLiquidacionMotrix extends Model
{
    protected $table =
        'transferencias_liquidacion_motrix';

    protected $fillable = [
        'liquidacion_id',
        'monto',
        'forma_pago',
        'referencia',
        'comprobante_url',
        'fecha_transferencia',
        'estado',
        'observacion',
        'registrado_por',
        'validado_por',
        'validado_en',
    ];

    protected $casts = [
        'liquidacion_id' => 'integer',
        'monto' => 'decimal:2',
        'fecha_transferencia' => 'datetime',
        'registrado_por' => 'integer',
        'validado_por' => 'integer',
        'validado_en' => 'datetime',
    ];

    public function liquidacion(): BelongsTo
    {
        return $this->belongsTo(
            LiquidacionMotrix::class,
            'liquidacion_id'
        );
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'registrado_por'
        );
    }

    public function validadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'validado_por'
        );
    }
}
