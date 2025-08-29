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
     * Obtener un rol por su ID
     */
    public function getRoleById(string $id)
    {
        return $this->roleModel->find($id);
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

    /**
     * Crear un nuevo rol
     *
     * @param array $data
     * @return array
     */
    public function createRole(array $data)
    {
        $this->roleModel->insert($data);
        return $this->roleModel->find($this->roleModel->getInsertID());
    }

    /**
     * Actualizar un rol existente
     *
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateRole(string $id, array $data)
    {
        $this->roleModel->update($id, $data);
        return $this->roleModel->find($id);
    }
}
