<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\ValidationException;

class ServiceAuthService
{
    /**
     * Login service.
     */
    public function login(
        string $clientId,
        string $clientSecret
    ): array {

        $service = Service::where(
            'client_id',
            $clientId
        )->first();

        if (! $service) {
            throw ValidationException::withMessages([
                'client_id' => ['Invalid client credentials.'],
            ]);
        }

        if ($service->status !== 'active') {
            throw ValidationException::withMessages([
                'service' => ['Service is inactive.'],
            ]);
        }

        if (! Hash::check(
            $clientSecret,
            $service->client_secret_hash
        )) {
            throw ValidationException::withMessages([
                'client_id' => ['Invalid client credentials.'],
            ]);
        }

        $service->update([
            'last_used_at' => now(),
        ]);

        $token = JWTAuth::fromUser($service);

        return [
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => config('jwt.ttl') * 60,
            'service'      => $service,
        ];
    }

    /**
     * Register new service.
     *
     * Secret hanya ditampilkan SEKALI.
     */
    public function register(array $data): array
    {
        $clientSecret = $this->generateSecret();

        $service = Service::create([
            'name'               => $data['name'],
            'slug'               => $data['slug'],
            'client_id'          => (string) Str::uuid(),
            'client_secret_hash' => Hash::make($clientSecret),
            'status'             => 'active',
            'default_bucket'     => $data['default_bucket'],
            'allowed_scopes'     => $data['allowed_scopes'] ?? [],
        ]);

        return [
            'service' => $service,
            'credentials' => [
                'client_id' => $service->client_id,
                'client_secret' => $clientSecret,
            ],
        ];
    }

    /**
     * Generate secure client secret.
     */
    public function generateSecret(): string
    {
        return 'psk_' . Str::random(64);
    }

    /**
     * Rotate secret.
     */
    public function rotateSecret(Service $service): array
    {
        $secret = $this->generateSecret();

        $service->update([
            'client_secret_hash' => Hash::make($secret),
        ]);

        return [
            'client_id' => $service->client_id,
            'client_secret' => $secret,
        ];
    }

    /**
     * Disable service.
     */
    public function disable(Service $service): Service
    {
        $service->update([
            'status' => 'inactive',
        ]);

        return $service->refresh();
    }

    /**
     * Enable service.
     */
    public function enable(Service $service): Service
    {
        $service->update([
            'status' => 'active',
        ]);

        return $service->refresh();
    }

    /**
     * Check scope.
     */
    public function hasScope(
        Service $service,
        string $scope
    ): bool {

        $scopes = $service->allowed_scopes ?? [];

        if (in_array('*', $scopes, true)) {
            return true;
        }

        if (in_array($scope, $scopes, true)) {
            return true;
        }

        foreach ($scopes as $allowed) {

            if (
                str_ends_with($allowed, '.*')
                && str_starts_with(
                    $scope,
                    substr($allowed, 0, -1)
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find service by client_id.
     */
    public function findByClientId(
        string $clientId
    ): ?Service {

        return Service::where(
            'client_id',
            $clientId
        )->first();
    }
}
