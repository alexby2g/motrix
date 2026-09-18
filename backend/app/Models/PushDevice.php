<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushDevice extends Model
{
    protected $table = 'push_devices';

    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'device_name',
        'active',
        'last_seen_at',
    ];

    protected $hidden = [
        'token',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
