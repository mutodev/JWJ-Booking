<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class CustomService extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id' => 'string',
        'name' => 'string',
        'detail' => '?string',
        'price' => 'float',
        'is_active' => 'boolean',
        'created_by' => '?string',
        'updated_by' => '?string',
    ];
}
