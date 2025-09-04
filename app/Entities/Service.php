<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Service extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id' => 'string',
        'name' => 'string',
        'description' => 'string',
        'is_active' => 'boolean'
    ];
}
