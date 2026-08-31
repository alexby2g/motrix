<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanSuscripcionMotrix extends Model
{
    protected $table = 'planes_suscripcion_motrix';

    protected $fillable = [
        'nombre',
        'descripcion',
        'monto',
        'duracion_meses',
        'dias_gracia',
        'aviso_dias_antes',
        'activo',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'duracion_meses' => 'integer',
        'dias_gracia' => 'integer',
        'aviso_dias_antes' => 'integer',
        'activo' => 'boolean',
        'creado_por' => 'integer',
        'actualizado_por' => 'integer',
    ];

    public function suscripciones(): HasMany
    {
        return $this->hasMany(
            SuscripcionMotrix::class,
            'plan_id'
        );
    }
}
