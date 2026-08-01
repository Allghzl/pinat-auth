<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\JWK;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class JwtService
{
    public function __construct(
        protected KeyPairService $keys,
    ) {}

    /**
     * Generate JWT.
     */
    public function generate(array $claims): string
    {
        $privateKey = $this->keys->getPrivateKey();
        $ttl = $this->ttl();
        $now = time();

        $payload = array_merge([
            'iss' => config('pinat-auth.jwt.issuer'),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
        ], $claims);

        return JWT::encode(
            $payload,
            $privateKey,
            'RS256',
            $this->keys->kid(),
        );
    }

    /**
     * Verify token.
     */
    public function verify(string $token): object
    {
        return JWT::decode(
            $token,
            new Key(
                $this->keys->getPublicKey(),
                'RS256'
            )
        );
    }

    /**
     * Decode payload tanpa validasi.
     */
    public function payload(string $token): array
    {
        $parts = explode('.', $token);

        return json_decode(
            base64_decode(strtr($parts[1], '-_', '+/')),
            true
        );
    }

    public function ttl(): int
    {
        return config('pinat-auth.jwt.ttl') * 60;
    }

    public function refreshTtl(): int
    {
        return (int) config('pinat-auth.jwt.refresh_ttl') * 60;
    }
}
