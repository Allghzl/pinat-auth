<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Service extends Authenticatable implements JWTSubject
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

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'type' => 'service',
            'service' => $this->name,
            'bucket' => $this->def_bucket ?? null,
            'scopes' => $this->allowed_scopes,
        ];
    }
}
