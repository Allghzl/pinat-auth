<?php

namespace App\Services;

use RuntimeException;

class KeyPairService
{
    public function getPrivateKey(): string
    {
        return $this->readKey(
            storage_path('keys/private.pem')
        );
    }

    public function getPublicKey(): string
    {
        return $this->readKey(
            storage_path('keys/public.pem')
        );
    }

    public function kid(): string
    {
        return substr(
            hash(
                'sha256',
                $this->getPublicKey()
            ),
            0,
            16
        );
    }

    protected function readKey(string $path): string
    {
        if (! file_exists($path)) {
            throw new RuntimeException(
                "Key not found: {$path}"
            );
        }

        return trim(
            file_get_contents($path)
        );
    }
}
