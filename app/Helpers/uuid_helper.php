<?php

use Ramsey\Uuid\Uuid;

/**
 * Asigna un UUID a $data['data']['id'] si está vacío
 */
function generate_uuid_data(array $data): array
{
    if (empty($data['data']['id'])) {
        $data['data']['id'] = Uuid::uuid4()->toString();
    }
    return $data;
}
