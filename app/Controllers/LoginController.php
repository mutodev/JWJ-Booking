<?php

namespace App\Controllers;

use App\Services\LoginService;
use CodeIgniter\RESTful\ResourceController;

class LoginController extends ResourceController
{

    protected $loginService;

    function __construct()
    {
        $this->loginService = new LoginService();
    }

    /**
     * Login de usuario
     *
     * @return void
     */
    public function login()
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Inicio de sesión', $this->loginService->login($data['email'], $data['password'])));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Restaurar contraseña de usuario
     * @return void
     */
    public function resetPassword()
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Contraseña restaurada', $this->loginService->resetPassword($data)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }
}
