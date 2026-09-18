<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuscripcionMotrix extends Model
{
    protected $table = 'suscripciones_motrix';

    protected $fillable = [
        'id_mototaxista',
        'id_sindicato',
        'plan_id',
        'fecha_inicio',
        'fecha_vencimiento',
        'estado',
        'renovacion_automatica',
        'suspendida_en',
        'motivo_suspension',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'id_mototaxista' => 'integer',
        'id_sindicato' => 'integer',
        'plan_id' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_vencimiento' => 'date',
        'renovacion_automatica' => 'boolean',
        'suspendida_en' => 'datetime',
        'creado_por' => 'integer',
        'actualizado_por' => 'integer',
    ];

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

    public function plan(): BelongsTo
    {
        return $this->belongsTo(
            PlanSuscripcionMotrix::class,
            'plan_id'
        );
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(
            PagoSuscripcionMotrix::class,
            'suscripcion_id'
        );
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(
            AlertaSuscripcionMotrix::class,
            'suscripcion_id'
        );
    }

    public function estaSuspendida(): bool
    {
        return strtolower(trim((string) $this->estado)) === 'suspendida';
    }

    public function diasRestantes(): int
    {
        if (! $this->fecha_vencimiento instanceof CarbonInterface) {
            return 0;
        }

        return now()
            ->startOfDay()
            ->diffInDays(
                $this->fecha_vencimiento->copy()->startOfDay(),
                false
            );
    }
}
