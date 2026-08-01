<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\RefreshToken;
use App\Models\User;
use App\Models\AuthSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserAuthService
{
    public function __construct(
        protected JwtService $jwt,
    ) {}

    /**
     * Register user.
     */
    public function register(array $data): array
    {
        if (User::where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Email sudah terdaftar.'],
            ]);
        }

        return DB::transaction(function () use ($data) {

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            return $this->createAuthentication(
                $user,
                $data['device'] ?? null,
            );
        });
    }

    /**
     * Login menggunakan email/password.
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        return DB::transaction(function () use ($user, $credentials) {

            return $this->createAuthentication(
                $user,
                $credentials['device'] ?? null,
            );
        });
    }

    /**
     * Login dari OAuth.
     */
    public function oauthLogin(
        User $user,
        ?string $device = null,
    ): array {
        return DB::transaction(function () use ($user, $device) {

            return $this->createAuthentication(
                $user,
                $device,
            );
        });
    }

    /**
     * Refresh access token.
     */
    public function refresh(
        string $refreshToken,
        ?string $device = null,
    ): array {

        $record = $this->validateRefreshToken($refreshToken);

        return DB::transaction(function () use ($record, $device) {
            $session = $record->session;
            $user = $session->user;
            $this->revokeRefreshToken($record);
            return $this->createAuthentication(
                $user,
                $device ?? $session->device_name,
            );
        });
    }

    /**
     * Logout satu session.
     */
    public function logout(string $refreshToken): void
    {
        $record = $this->validateRefreshToken($refreshToken);
        $record->session->update([
            'revoked_at' => now(),
        ]);
        $record->session
            ->refreshTokens()
            ->update([
                'revoked_at' => now(),
            ]);

        // $this->revokeRefreshToken($record);
    }

    /**
     * Logout semua session.
     */
    public function logoutAll(User $user): int
    {
        return $user
            ->authSessions()
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
            ]);
    }

    /**
     * Resolve user dari JWT.
     */
    public function resolveUser(string $token): User
    {
        try {

            $payload = $this->jwt->verify($token);
        } catch (\Throwable) {

            throw ValidationException::withMessages([
                'token' => ['Token tidak valid atau sudah kadaluarsa.'],
            ]);
        }

        if (
            ! isset($payload->sub)
            || ! isset($payload->type)
            || $payload->type !== 'user'
        ) {
            throw ValidationException::withMessages([
                'token' => ['Token bukan token user.'],
            ]);
        }

        $user = User::find($payload->sub);

        if (! $user) {
            throw ValidationException::withMessages([
                'token' => ['User tidak ditemukan.'],
            ]);
        }

        return $user;
    }

    /**
     * Generate seluruh authentication.
     */
    protected function createAuthentication(
        User $user,
        ?string $device,
    ): array {

        $session = AuthSession::create([
            'user_id' => $user->id,
            'client_id' => request('client_id', 'web'),
            'device_name' => $device,
            'platform' => request()->header('Sec-CH-UA-Platform'),
            'browser' => request()->userAgent(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'last_activity_at' => now(),
            'expires_at' => now()->addMinutes(
                $this->jwt->refreshTtl()
            ),
        ]);

        $refreshToken = $this->issueRefreshToken($session);

        $accessToken = $this->issueAccessToken(
            $user,
            $session->id,
        );

        return [
            'user' => UserResource::make($user),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'session_id' => $session->id,
            'token_type' => 'Bearer',
            'expires_in' => $this->jwt->ttl(),
        ];
    }

    /**
     * Generate access token.
     */
    protected function issueAccessToken(
        User $user,
        string $sessionId,
    ): string {

        return $this->jwt->generate([

            'sub' => (string) $user->id,
            'type' => 'user',
            'session' => $sessionId,
            'email' => $user->email,
            'name' => $user->name,
            'avatar_key' => $user->avatar_key,

        ]);
    }

    /**
     * Generate refresh token.
     */
    protected function issueRefreshToken(
        AuthSession $session,
    ): string {

        $raw = Str::random(80);

        $session->refreshTokens()->create([
            'token' => hash('sha256', $raw),
            'expires_at' => now()->addMinutes(
                $this->jwt->refreshTtl()
            ),
        ]);

        return $raw;
    }

    /**
     * Validate refresh token.
     */
    protected function validateRefreshToken(
        string $refreshToken,
    ): RefreshToken {

        $record = RefreshToken::query()
            ->where('token', hash('sha256', $refreshToken))
            ->whereNull('revoked_at')
            ->first();

        if (! $record) {

            throw ValidationException::withMessages([
                'refresh_token' => ['Refresh token tidak valid.'],
            ]);
        }

        if (! $record->isValid()) {

            throw ValidationException::withMessages([
                'refresh_token' => ['Refresh token sudah kadaluarsa.'],
            ]);
        }

        return $record;
    }

    /**
     * Revoke refresh token.
     */
    protected function revokeRefreshToken(
        RefreshToken $token,
    ): void {

        $token->update([
            'revoked_at' => now(),
        ]);
    }

    /**
     * Revoke semua refresh token.
     */
    public function revokeAllRefreshTokens(
        User $user,
    ): int {
        return $user->authSessions()
            ->each(function ($session) {
                $session->refreshTokens()
                    ->whereNull('revoked_at')
                    ->update([
                        'revoked_at' => now(),
                    ]);

                $session->update([
                    'revoked_at' => now(),
                ]);
            });
    }
}
