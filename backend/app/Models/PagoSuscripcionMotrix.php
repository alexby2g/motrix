<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PagoSuscripcionMotrix extends Model
{
    protected $table = 'pagos_suscripcion_motrix';

    protected $fillable = [
        'suscripcion_id',
        'id_mototaxista',
        'id_sindicato',
        'periodo',
        'monto_esperado',
        'monto_pagado',
        'fecha_vencimiento',
        'fecha_pago',
        'estado',
        'forma_pago',
        'canal_cobro',
        'referencia_pago',
        'comprobante_url',
        'observacion',
        'registrado_por',
        'validado_por',
        'validado_en',
    ];

    protected $casts = [
        'suscripcion_id' => 'integer',
        'id_mototaxista' => 'integer',
        'id_sindicato' => 'integer',
        'monto_esperado' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'datetime',
        'registrado_por' => 'integer',
        'validado_por' => 'integer',
        'validado_en' => 'datetime',
    ];

    public function suscripcion(): BelongsTo
    {
        return $this->belongsTo(
            SuscripcionMotrix::class,
            'suscripcion_id'
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

    public function detallesLiquidacion(): HasMany
    {
        return $this->hasMany(
            LiquidacionPagoMotrix::class,
            'pago_suscripcion_id'
        );
    }

    public function fueCobradoPorSindicato(): bool
    {
        return strtolower(trim((string) $this->canal_cobro)) === 'sindicato';
    }

    public function estaPagado(): bool
    {
        return strtolower(trim((string) $this->estado)) === 'pagado';
    }
}
