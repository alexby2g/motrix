<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidenciaViaje extends Model
{
    use HasFactory;

    protected $table = 'incidencias_viaje';

    protected $fillable = [
        'codigo',
        'solicitud_id',
        'reportado_por_usuario_id',
        'reportado_por_rol',
        'reportado_por_nombre',
        'tipo',
        'prioridad',
        'descripcion',
        'latitud',
        'longitud',
        'precision_metros',
        'estado',
        'nota_administrador',
        'atendido_por_usuario_id',
        'atendido_por_nombre',
        'fecha_reportada',
        'recibido_en',
        'atencion_en',
        'resuelto_en',
    ];

    protected $casts = [
        'solicitud_id' => 'integer',
        'reportado_por_usuario_id' => 'integer',
        'atendido_por_usuario_id' => 'integer',
        'latitud' => 'float',
        'longitud' => 'float',
        'precision_metros' => 'float',
        'fecha_reportada' => 'datetime',
        'recibido_en' => 'datetime',
        'atencion_en' => 'datetime',
        'resuelto_en' => 'datetime',
    ];

    public function solicitud()
    {
        return $this->belongsTo(
            Solicitud::class,
            'solicitud_id'
        );
    }

    public function scopeActivas($query)
    {
        return $query->whereIn('estado', [
            'Reportado',
            'Recibido',
            'En atención',
        ]);
    }
}
