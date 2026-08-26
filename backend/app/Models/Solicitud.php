<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    use HasFactory;

    protected $table = 'solicitudes';

    public $timestamps = false;

    protected $fillable = [
        'origen',
        'latitud_origen',
        'longitud_origen',

        'destino',
        'latitud_destino',
        'longitud_destino',

        'fecha',
        'estado',
        'id_pasajero',

        'precio',
        'distancia_km',

        'mototaxista_id',
        'metodo_pago',
        'expira_en',
        'motivo_cancelacion',

        'calificacion',
        'comentario_calificacion',
        'calificado_en',
        'creado_en',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
        'latitud_origen' => 'float',
        'longitud_origen' => 'float',
        'latitud_destino' => 'float',
        'longitud_destino' => 'float',
        'precio' => 'float',
        'distancia_km' => 'float',
        'expira_en' => 'datetime',
        'calificacion' => 'integer',
        'calificado_en' => 'datetime',
        'creado_en' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Solicitud $solicitud) {
            $solicitud->mensajes()->delete();
            $solicitud->incidencias()->delete();
        });
    }

    public function pasajero()
    {
        return $this->belongsTo(
            Pasajero::class,
            'id_pasajero'
        );
    }

    public function mototaxista()
    {
        return $this->belongsTo(
            Mototaxista::class,
            'mototaxista_id'
        );
    }

    public function mensajes()
    {
        return $this->hasMany(
            MensajeViaje::class,
            'solicitud_id'
        )->orderBy('id');
    }

    public function incidencias()
    {
        return $this->hasMany(
            IncidenciaViaje::class,
            'solicitud_id'
        )->orderBy('id');
    }
}
