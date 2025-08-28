<?php

use Firebase\JWT\JWT;

/**
 * Generación de token
 *
 * @param array $data
 * @return string
 */
function generate_token(array $data): string
{
    $payload = array_merge(
        $data,
        [
            'iat' => time(),
            'exp' => time() + 6000
        ]
    );
    return JWT::encode($payload, env('encryption.key'), 'HS256');
}
