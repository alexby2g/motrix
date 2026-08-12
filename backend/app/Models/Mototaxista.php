<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mototaxista extends Model
{
    protected $table = 'mototaxistas';

    public $timestamps = false;

    protected $fillable = [
        'nro_chaleco',
        'codigo_qr',
        'telefono',
        'estado',
        'disponible',
        'latitud',
        'longitud',
        'ultima_conexion',
        'id_persona',
        'id_sindicato',
    ];

    protected $casts = [
        'disponible' => 'boolean',
        'latitud' => 'float',
        'longitud' => 'float',
        'ultima_conexion' => 'datetime',
        'id_persona' => 'integer',
        'id_sindicato' => 'integer',
    ];

    public function persona()
    {
        return $this->belongsTo(
            Persona::class,
            'id_persona'
        );
    }

    public function sindicato()
    {
        return $this->belongsTo(
            Sindicato::class,
            'id_sindicato'
        );
    }

    public function motocicletas()
    {
        return $this->hasMany(
            Motocicleta::class,
            'id_mototaxista'
        );
    }

    public function solicitudes()
    {
        return $this->hasMany(
            Solicitud::class,
            'mototaxista_id'
        );
    }

    public function servicios()
    {
        return $this->hasMany(
            Servicio::class,
            'id_mototaxista'
        );
    }

    public function usuarioConductor()
    {
        return $this->hasOne(
            User::class,
            'mototaxista_id'
        )->where('role', 'conductor');
    }
}
