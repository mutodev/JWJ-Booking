<?php

namespace App\Repositories;

use App\Entities\User;
use App\Models\UserModel;

class UserRepository
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Consulta de usuario por correo
     * 
     * @param string $email
     * @return User
     */
    public function getUserByEmail(string $email): User
    {
        return $this->userModel
            ->where('email', $email)
            ->first();
    }

    /**
     * Consulta de usuario por correo y contrseña
     *
     * @param string $email
     * @param string $password
     * @return User
     */
    public function getUserByEmailAndPassword(string $email, string $password): ?array
    {
        $data = $this->userModel
            ->select('
                users.id,
                users.first_name,
                users.last_name,
                users.email,
                users.image,
                users.password,
                roles.name AS role_name
             ')
            ->join('roles', 'roles.id = users.role_id')
            ->where('email', $email)
            ->where('state', true)
            ->first();

        if (!$data || !password_verify($password, $data->password))
            return null;

        $data = $data->toArray();
        unset($data['password']);
        return $data;
    }
}
