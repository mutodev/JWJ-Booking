<?php

namespace App\Controllers;

use App\Services\RoleService;

class RoleController extends BaseController
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
}
