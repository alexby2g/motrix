<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Imagen extends Model
{
    protected $table = 'imagenes';
    protected $fillable = [
        'ruta',
        'tipo',
        'id_persona'
    ];

    public $timestamps = false;

    /**
     * Relación inversa: Una imagen pertenece a una Persona.
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }
}