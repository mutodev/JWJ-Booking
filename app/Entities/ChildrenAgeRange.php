<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ChildrenAgeRange extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [
        'id' => 'string',
        'service_price_id' => 'string',
        'min_age' => 'integer',
        'max_age' => 'integer',
        'is_active' => 'boolean'
    ];
}
