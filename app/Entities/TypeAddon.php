<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class TypeAddon extends Entity
{
    protected $attributes = [
        'id' => null,
        'name' => null,
        'image' => null,
        'description' => null,
        'is_active' => true,
        'created_at' => null,
        'updated_at' => null,
        'deleted_at' => null,
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
