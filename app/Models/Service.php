<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Service extends Authenticatable
{
    use HasUuids;

    protected $table = 'services';

    protected $fillable = [
        'name',
        'slug',
        'default_bucket',
        'client_id',
        'client_secret_hash',
        'status',
        'allowed_scopes',
        'last_used_at'
    ];

    protected $casts = [
        'allowed_scopes' => 'array',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'client_secret_hash',
    ];
}
