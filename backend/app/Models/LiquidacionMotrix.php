<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiquidacionMotrix extends Model
{
    protected $table = 'liquidaciones_motrix';

    protected $fillable = [
        'id_sindicato',
        'periodo',
        'monto_declarado',
        'monto_transferido',
        'estado',
        'forma_pago',
        'referencia',
        'comprobante_url',
        'fecha_transferencia',
        'fecha_validacion',
        'observacion',
        'registrado_por',
        'validado_por',
    ];

    protected $casts = [
        'id_sindicato' => 'integer',
        'monto_declarado' => 'decimal:2',
        'monto_transferido' => 'decimal:2',
        'fecha_transferencia' => 'datetime',
        'fecha_validacion' => 'datetime',
        'registrado_por' => 'integer',
        'validado_por' => 'integer',
    ];

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

    public function detalles(): HasMany
    {
        return $this->hasMany(
            LiquidacionPagoMotrix::class,
            'liquidacion_id'
        );
    }

    public function saldoPendiente(): string
    {
        $declarado = (float) $this->monto_declarado;
        $transferido = (float) $this->monto_transferido;

        return number_format(
            max(0, $declarado - $transferido),
            2,
            '.',
            ''
        );
    }
}
