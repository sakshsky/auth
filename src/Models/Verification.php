<?php

namespace Sakshsky\Auth\Models;

use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    protected $fillable = [
        'email', 'code', 'expiry', 'socket_id', 'hashed_fingerprint', 'salt',
    ];

    protected $casts = [
        'expiry' => 'datetime',
    ];
}