<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EjecucionAutomatizacionMotrix extends Model
{
    protected $table = 'ejecuciones_automatizacion_motrix';

    protected $fillable = [
        'tipo',
        'origen',
        'ejecutado_por',
        'fecha_referencia',
        'estado',
        'procesadas',
        'alertas_creadas',
        'alertas_enviadas',
        'alertas_sin_usuario',
        'errores',
        'detalle',
        'iniciada_en',
        'finalizada_en',
    ];

    protected $casts = [
        'ejecutado_por' => 'integer',
        'fecha_referencia' => 'date',
        'procesadas' => 'integer',
        'alertas_creadas' => 'integer',
        'alertas_enviadas' => 'integer',
        'alertas_sin_usuario' => 'integer',
        'errores' => 'integer',
        'detalle' => 'array',
        'iniciada_en' => 'datetime',
        'finalizada_en' => 'datetime',
    ];

    public function ejecutor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'ejecutado_por'
        );
    }
}
