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
                ->setJSON(create_response(lang('App.user_obtained'), $this->userService->getUserById($id)));
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
                ->setJSON(create_response(lang('App.user_created'), $this->userService->create($data)));
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
                ->setJSON(create_response(lang('App.user_updated'), $this->userService->update($id, $data)));
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
                ->setJSON(create_response(lang('App.user_deleted'), $this->userService->delete($id)));
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

            // Obtener ID del usuario desde el token JWT
            $authHeader = $this->request->getHeaderLine('Authorization');
            if (!$authHeader) {
                throw new \Exception('Token not provided', 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $key = env('encryption.key');
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($key, 'HS256'));
            $data['id'] = $decoded->id;

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.password_changed'), $this->userService->changePassword($data)));
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
                ->setJSON(create_response(lang('App.user_list'), $this->userService->getByRole($role)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }
}
