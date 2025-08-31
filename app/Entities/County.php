<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class County extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id' => 'string',
        'metropolitan_area_id' => 'string',
        'name' => 'string',
        'is_active' => 'boolean'
    ];
}
