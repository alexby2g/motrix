<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViajeCompartidoToken extends Model
{
    protected $table = 'viaje_compartido_tokens';

    protected $fillable = [
        'solicitud_id',
        'token_hash',
        'created_by_user_id',
        'expires_at',
        'revoked_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'solicitud_id' => 'integer',
        'created_by_user_id' => 'integer',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
