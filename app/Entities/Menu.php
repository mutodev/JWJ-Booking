<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Menu extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id' => 'string',
        'name' => 'string',
        'uri' => 'string',
        'icon' => 'string',
        'order' => 'integer',
        'is_active' => 'boolean',
        'parent_id' => 'string'
    ];
}
