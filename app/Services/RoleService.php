<?php

namespace App\Services;

use App\Repositories\RoleRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;

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

    /**
     * Crear un nuevo rol
     *
     * @param array $data
     * @return array
     */
    public function createRole(array $data)
    {
        // Validar datos (puedes agregar más validaciones según tus necesidades)
        if (empty($data['name'])) 
            throw new HTTPException(lang('Role.nameRequired'), Response::HTTP_BAD_REQUEST);

        // Crear el rol
        return $this->roleRepository->createRole($data);
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
        // Validar datos (puedes agregar más validaciones según tus necesidades)
        if (empty($data['name'])) 
            throw new HTTPException(lang('Role.nameRequired'), Response::HTTP_BAD_REQUEST);

        // Verificar si el rol existe
        $existingRole = $this->roleRepository->getRoleById($id);
        if (!$existingRole) 
            throw new HTTPException(lang('Role.notFound'), Response::HTTP_NOT_FOUND);

        // Actualizar el rol
        return $this->roleRepository->updateRole($id, $data);
    }
}
