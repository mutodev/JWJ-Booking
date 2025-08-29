<?php

namespace App\Controllers;

use App\Services\RoleService;
use CodeIgniter\RESTful\ResourceController;

class RoleController extends ResourceController
{

    protected $roleService;

    public function __construct()
    {
        $this->roleService = new RoleService();
    }

    /**
     * Obtener todos los roles
     *
     * @return void
     */
    public function getAllRoles()
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Lista de roles', $this->roleService->getAllRoles()));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Crear un nuevo rol
     *
     * @return void
     */
    public function createRole()
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);
            return $this->response
                ->setStatusCode(201)
                ->setJSON(create_response('Rol creado', $this->roleService->createRole($data)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Actualizar un rol existente
     *
     * @param int $id
     * @return void
     */
    public function updateRole($id)
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Rol actualizado', $this->roleService->updateRole($id, $data)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }
}
