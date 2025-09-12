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
        'min_duration_hours' => 'integer',
        'is_available' => 'boolean',
        'max_children' => 'integer',
        'extra_child_fee' => 'float'
    ];
}
