<?php

namespace App\Services;

class JwksService
{
    public function __construct(
        protected KeyPairService $keys,
    ) {}
    public function generate(): array
    {
        $resource = openssl_pkey_get_public(
            $this->keys->getPublicKey()
        );

        if (! $resource) {
            throw new \RuntimeException('Invalid public key.');
        }

        $details = openssl_pkey_get_details($resource);

        $rsa = $details['rsa'];

        return [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'use' => 'sig',
                    'alg' => 'RS256',
                    'kid' => $this->keys->kid(),
                    'n' => $this->base64UrlEncode($rsa['n']),
                    'e' => $this->base64UrlEncode($rsa['e']),
                ],
            ],
        ];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(
            strtr(
                base64_encode($data),
                '+/',
                '-_'
            ),
            '='
        );
    }
}
