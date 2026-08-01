<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string|null $username
 * @property string|null $avatar_key
 * @property string|null $avatar
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'username', 'avatar_key', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasUuids, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable, HasApiTokens;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $appends = ['avatar'];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function getAvatarAttribute(?string $value): ?string
    {
        if ($this->avatar_key) {
            return app(\App\Services\AvatarStorageService::class)->getUrl($this);
        }
        return $value;
    }

    public function newUniqueId(): string
    {
        return (string) \Illuminate\Support\Str::uuid7();
    }
    public function emailOtp()
    {
        return $this->hasOne(Email_otp::class);
    }

    // OAuth relations
    public function oauthProviders()
    {
        return $this->hasMany(\App\Models\OAuthProvider::class);
    }

    public function refreshTokens()
    {
        return $this->hasMany(\App\Models\RefreshToken::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AuthSession::class);
    }
}
