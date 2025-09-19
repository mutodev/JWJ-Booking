<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Duration extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [
        'id' => 'string',
        'service_price_id' => 'string',
        'minutes' => 'integer',
        'is_active' => 'boolean'
    ];
}
