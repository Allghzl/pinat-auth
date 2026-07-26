<?php

namespace App\Services;

class KeyPairService
{
    public function getPrivateKey(): string
    {
        return file_get_contents(
            storage_path('keys/private.pem')
        );
    }

    public function getPublicKey(): string
    {
        return file_get_contents(
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
}
