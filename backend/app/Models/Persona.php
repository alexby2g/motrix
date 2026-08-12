<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $table = 'personas';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'apellidos',
        'telefono',
        'ci',
        'direccion',
        'sindicato_registro_id',
    ];

    protected $casts = [
        'sindicato_registro_id' => 'integer',
    ];

    public function imagenes()
    {
        return $this->hasMany(
            ImagenPersona::class,
            'id_persona'
        )->orderBy('id');
    }

    public function mototaxista()
    {
        return $this->hasOne(
            Mototaxista::class,
            'id_persona'
        );
    }

    public function pasajero()
    {
        return $this->hasOne(
            Pasajero::class,
            'id_persona'
        );
    }

    public function usuarios()
    {
        return $this->hasMany(
            User::class,
            'persona_id'
        );
    }

    public function sindicatoRegistro()
    {
        return $this->belongsTo(
            Sindicato::class,
            'sindicato_registro_id'
        );
    }
}
