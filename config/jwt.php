<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Private Key
    |--------------------------------------------------------------------------
    |
    | The RSA private key used to sign JWT offline tokens.
    | This key must be kept secret and never committed to version control.
    | Algorithm: RS256 (RSA with SHA-256)
    |
    */
    'private_key' => env('JWT_PRIVATE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | JWT Public Key Path
    |--------------------------------------------------------------------------
    |
    | Path to the RSA public key used by SDK clients to verify JWT signatures.
    | This key can be safely distributed to clients.
    |
    */
    'public_key_path' => env('JWT_PUBLIC_KEY_PATH', 'storage/jwt_public.pem'),

    /*
    |--------------------------------------------------------------------------
    | JWT Issuer
    |--------------------------------------------------------------------------
    |
    | The issuer claim (iss) for all JWT tokens.
    | This value is hardcoded as per technical decision T6.
    |
    */
    'issuer' => 'license-platform',

    /*
    |--------------------------------------------------------------------------
    | JWT Algorithm
    |--------------------------------------------------------------------------
    |
    | The signing algorithm for JWT tokens.
    | This is hardcoded to RS256 and must not be changed.
    | SDK clients must verify with alg=RS256 hardcoded (not from header).
    |
    */
    'algorithm' => 'RS256',

    /*
    |--------------------------------------------------------------------------
    | Default Offline Token TTL (hours)
    |--------------------------------------------------------------------------
    |
    | Default time-to-live for offline tokens in hours.
    | This can be overridden per product (min: 1h, max: 168h = 7 days).
    |
    */
    'default_ttl_hours' => 24,

    /*
    |--------------------------------------------------------------------------
    | Minimum Offline Token TTL (hours)
    |--------------------------------------------------------------------------
    |
    | Minimum allowed TTL for offline tokens.
    |
    */
    'min_ttl_hours' => 1,

    /*
    |--------------------------------------------------------------------------
    | Maximum Offline Token TTL (hours)
    |--------------------------------------------------------------------------
    |
    | Maximum allowed TTL for offline tokens (7 days).
    |
    */
    'max_ttl_hours' => 168,

    /*
    |--------------------------------------------------------------------------
    | NBF Clock Skew Tolerance (seconds)
    |--------------------------------------------------------------------------
    |
    | Maximum allowed difference between nbf and iat claims.
    | Tokens with nbf > iat + tolerance will be rejected.
    | This prevents token manipulation attacks.
    |
    */
    'nbf_tolerance_seconds' => 300, // 5 minutes
];
