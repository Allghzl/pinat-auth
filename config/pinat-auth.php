<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Configuration
    |--------------------------------------------------------------------------
    |
    | PinatAuth menggunakan Firebase JWT dengan RSA-256 untuk signing.
    | Keys disimpan di environment variables untuk security.
    |
    */

    'jwt' => [
        'keys' => [
            'public' => env('JWT_PUBLIC_KEY'),
            'private' => env('JWT_PRIVATE_KEY'),
            'passphrase' => env('JWT_PASSPHRASE'),
        ],

        'algo' => env('JWT_ALGO', 'RS256'),

        // Access token TTL (in minutes)
        'ttl' => env('JWT_TTL', 60),

        // Refresh token TTL (in minutes)
        'refresh_ttl' => env('JWT_REFRESH_TTL', 20160), // 2 weeks default

        // Leeway time for clock skew (in seconds)
        'leeway' => env('JWT_LEEWAY', 0),

        // Issuer claim (iss)
        'issuer' => env('APP_URL'),

        // Required claims untuk validasi
        'required_claims' => [
            'iss',
            'iat',
            'exp',
            'nbf',
            'sub',
            'type',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Management
    |--------------------------------------------------------------------------
    |
    | Configuration untuk refresh token dan session management.
    |
    */

    'session' => [
        // Auto cleanup expired refresh tokens (in days)
        'cleanup_after' => 30,

        // Maximum concurrent sessions per user (0 = unlimited)
        'max_sessions' => 0,

        // Refresh token rotation enabled
        'rotation_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth Providers
    |--------------------------------------------------------------------------
    |
    | Supported OAuth providers untuk social login.
    |
    */

    'oauth' => [
        'supported_providers' => ['google', 'github'],

        // Default redirect after OAuth callback
        'default_redirect' => env('OAUTH_DEFAULT_REDIRECT', '/auth/callback'),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Roles & Permissions
    |--------------------------------------------------------------------------
    |
    | Default roles untuk user baru dan role hierarchy.
    |
    */

    'roles' => [
        'default' => 'user',

        'available' => [
            'user',
            'admin',
            'super_admin',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Authentication
    |--------------------------------------------------------------------------
    |
    | Configuration khusus untuk service-to-service auth.
    |
    */

    'service' => [
        // Service token TTL (in minutes)
        'ttl' => env('SERVICE_JWT_TTL', 1440), // 24 hours

        // Service token required claims
        'required_claims' => [
            'iss',
            'iat',
            'exp',
            'nbf',
            'sub',
            'type',
            'client_id',
        ],
    ],
];
