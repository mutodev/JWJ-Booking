<?php

namespace App\Services;

use App\Repositories\UserRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;

class LoginService
{
    protected $userRepository;

    function __construct()
    {
        $this->userRepository = new UserRepository();
    }


    /**
     * Verificación de datos Login
     *
     * @param string $email
     * @param string $password
     * @return void
     */
    public function login(string $email, string $password)
    {
        $data = $this->userRepository->getUserByEmailAndPassword($email, $password);
        if (!$data)
            throw new HTTPException(lang('Auth.invalidLogin'), Response::HTTP_UNAUTHORIZED);

        unset($data['id']);
        return generate_token((array) $data);
    }
}
