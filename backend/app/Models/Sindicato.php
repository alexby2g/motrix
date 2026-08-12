<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sindicato extends Model
{
    protected $table = 'sindicatos';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'federacion',
        'id_federacion',
        'logo',
        'fecha_creacion',
        'direccion',
    ];

    protected $casts = [
        'id_federacion' => 'integer',
        'fecha_creacion' => 'date:Y-m-d',
    ];

    public function federacionRelacion()
    {
        return $this->belongsTo(
            Federacion::class,
            'id_federacion'
        );
    }

    /**
     * Alias "federacionEntidad" para usarlo cuando el campo
     * de texto "federacion" ya existe en la tabla.
     */
    public function federacionEntidad()
    {
        return $this->federacionRelacion();
    }

    public function mototaxistas()
    {
        return $this->hasMany(
            Mototaxista::class,
            'id_sindicato'
        );
    }
}
