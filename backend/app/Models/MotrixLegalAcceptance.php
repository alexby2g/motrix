<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotrixLegalAcceptance extends Model
{
    protected $table = 'motrix_legal_acceptances';

    protected $fillable = [
        'user_id',
        'role_at_acceptance',
        'terms_version',
        'privacy_version',
        'accepted_at',
        'channel',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
        ];
    }
}
