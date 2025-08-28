<?php

namespace App\Repositories;

use App\Models\RoleModel;

class RoleRepository
{

    protected $roleModel;

    function __construct()
    {
        $this->roleModel = new RoleModel();
    }

    /**
     * Obtener todos los roles
     *
     * @return array
     */
    public function getAllRoles()
    {
        return $this->roleModel->findAll();
    }
}
