<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImagenPersona extends Model
{
    protected $table = 'imagenes_personas';

    public $timestamps = false;

    protected $fillable = [
        'ruta',
        'tipo',
        'id_persona',
    ];

    public function persona()
    {
        return $this->belongsTo(
            Persona::class,
            'id_persona'
        );
    }
}
