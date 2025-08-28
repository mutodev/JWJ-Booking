<?php

namespace App\Services;

use App\Repositories\RoleRepository;

class RoleService
{

    protected $roleRepository;

    function __construct()
    {
        $this->roleRepository = new RoleRepository();
    }

    /**
     * Obtener todos los roles
     *
     * @return array
     */
    public function getAllRoles()
    {
        return $this->roleRepository->getAllRoles();
    }
}
