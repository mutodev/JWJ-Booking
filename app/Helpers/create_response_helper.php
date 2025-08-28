<?php

/**
 * Estructura de respuesta JSON
 *
 * @param string $message
 * @param mixed $data
 * @return array
 */
function create_response(string $message, mixed $data): array
{
    return [
        'message' => $message,
        'data' => $data
    ];
}
