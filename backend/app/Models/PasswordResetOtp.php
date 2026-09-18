<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetOtp extends Model
{
    protected $table = 'password_reset_otps';

    protected $fillable = [
        'user_id',
        'identifier_hash',
        'code_hash',
        'reset_token_hash',
        'canal',
        'destino_enmascarado',
        'attempts',
        'expires_at',
        'verified_at',
        'consumed_at',
    ];

    protected $hidden = [
        'code_hash',
        'reset_token_hash',
        'identifier_hash',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'attempts' => 'integer',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
