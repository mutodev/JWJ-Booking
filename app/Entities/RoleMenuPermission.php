<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class RoleMenuPermission extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id' => 'string',
        'role_id' => 'string',
        'menu_id' => 'string',
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_update' => 'boolean',
        'can_delete' => 'boolean'
    ];
}
