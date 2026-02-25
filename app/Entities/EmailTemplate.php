<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class EmailTemplate extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [
        'id'                  => 'string',
        'slug'                => 'string',
        'name'                => 'string',
        'subject'             => 'string',
        'body'                => 'string',
        'available_variables' => 'string',
        'is_active'           => 'boolean',
    ];
}
