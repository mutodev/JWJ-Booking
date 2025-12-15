<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ServicePrice extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id' => 'string',
        'service_id' => 'string',
        'county_id' => 'string',
        'performers_count' => 'integer',
        'amount' => 'float',
        'travel_fee' => 'float',
        'is_available' => 'boolean',
        'extra_child_fee' => 'float',
        'range_age' => 'string'
    ];
}
