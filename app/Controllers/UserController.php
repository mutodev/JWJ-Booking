<?php

namespace App\Controllers;

use App\Services\UserService;
use CodeIgniter\RESTful\ResourceController;

class UserController extends ResourceController
{

    protected $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    /**
     * Obtener usuario por ID
     * @return void
     */
    public function getUserById(string $id)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Usuario obtenido', $this->userService->getUserById($id)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Crear nuevo usuario
     *
     * @return void
     */
    public function create()
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);
            return $this->response
                ->setStatusCode(201)
                ->setJSON(create_response('Usuario creado', $this->userService->create($data)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Actualizar usuario por ID
     *
     * @return void
     */
    public function updateUser(string $id)
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Usuario actualizado', $this->userService->update($id, $data)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Eliminar usuario por ID
     *
     * @return void
     */
    public function deleteUser(string $id)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Usuario eliminado', $this->userService->delete($id)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Cambiar contraseña de usuario
     *
     * @return void
     */
    public function changePassword()
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Contraseña cambiada', $this->userService->changePassword($data)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Obtener usuarios por rol
     * @return void
     */
    public function getUserByRole(string $role)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Lista de usuarios', $this->userService->getByRole($role)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }
}
