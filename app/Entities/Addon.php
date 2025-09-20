<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Addon extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id' => 'string',
        'base_price' => 'float',
        'is_active' => 'boolean',
        'estimated_duration_minutes' => 'integer',
        'price_type' => 'string'
    ];
}
