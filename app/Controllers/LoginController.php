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
            $email = strtolower(trim((string) ($data['email'] ?? '')));
            $password = (string) ($data['password'] ?? '');
            $this->guardAttempts('login|' . hash('sha256', $email));

            if ($email === '' || $password === '') {
                throw new \CodeIgniter\HTTP\Exceptions\HTTPException('Email and password are required', 422);
            }
            return $this->response
                ->setHeader('Cache-Control', 'no-store')
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.login_session'), $this->loginService->login($email, $password)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function verifyMfa()
    {
        try {
            $data = json_decode($this->request->getBody(), true) ?: [];
            $challengeToken = (string) ($data['challenge_token'] ?? '');
            $code = (string) ($data['code'] ?? '');
            $this->guardAttempts('mfa|' . hash('sha256', $challengeToken));

            if ($challengeToken === '' || $code === '') {
                throw new \CodeIgniter\HTTP\Exceptions\HTTPException('Challenge and authentication code are required', 422);
            }

            return $this->response
                ->setHeader('Cache-Control', 'no-store')
                ->setStatusCode(200)
                ->setJSON(create_response('Authentication verified', $this->loginService->verifyMfa($challengeToken, $code)));
        } catch (\Throwable $th) {
            $statusCode = $th->getCode();
            if ($statusCode < 400 || $statusCode > 599) {
                $statusCode = 500;
            }
            return $this->response->setStatusCode($statusCode)->setJSON(['message' => $th->getMessage()]);
        }
    }

    private function guardAttempts(string $bucket): void
    {
        // Cache backends reject characters such as |, :, / and @ in keys.
        $key = 'auth_rate_' . hash('sha256', $bucket . '|' . $this->request->getIPAddress());
        if (!service('throttler')->check($key, 8, 300)) {
            throw new \CodeIgniter\HTTP\Exceptions\HTTPException('Too many attempts. Please try again later.', 429);
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
                ->setJSON(create_response(lang('App.password_restored'), $this->loginService->resetPassword($data)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }
}
