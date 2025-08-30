<?php

namespace App\Services;

use App\Repositories\UserRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;
use App\Services\BrevoEmailService as ServicesBrevoEmailService;

class UserService
{
    protected $userRepository;
    protected $emailService;

    function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->emailService = new ServicesBrevoEmailService();
    }

    /**
     * Consulta de usuario por id
     * @param string $id
     * @return User
     */
    public function getUserById(string $id)
    {
        $user = $this->userRepository->getUserById($id);
        if (!$user)
            throw new HTTPException(lang('User.userNotFound'), Response::HTTP_NOT_FOUND);

        return $user;
    }

    /**
     * Crear nuevo usuario
     *
     * @param array $data
     * @return User
     */
    public function create(array $data)
    {

        $validateUser = $this->userRepository->getUserByEmail($data['email']);
        if ($validateUser)    
            throw new HTTPException(lang('User.emailInUse'), Response::HTTP_CONFLICT);

        $password = $this->generateRandomPassword(12);
        $data['password'] = password_hash($password, PASSWORD_BCRYPT);

        $user = $this->userRepository->createUser($data);
        if (!$user)
            throw new HTTPException(lang('User.userCreationFailed'), Response::HTTP_BAD_REQUEST);

        $email = $this->emailService->sendEmail(
            $data['email'],
            'Welcome to JamWithJamie',
            view('emails/welcome', ['password' => $password]),
        );

        if (!$email)
            throw new HTTPException(lang('User.emailSendFailed'), Response::HTTP_BAD_REQUEST);

        return $this->userRepository->getUserById($user);
    }

    /**
     * Actualizar usuario
     *
     * @param string $id
     * @param array $data
     * @return User
     */
    public function update(string $id, array $data)
    {
        $user = $this->userRepository->updateUser($id, $data);
        if (!$user)
            throw new HTTPException(lang('User.userUpdateFailed'), Response::HTTP_BAD_REQUEST);

        return $this->userRepository->getUserById($id);
    }

    /**
     * Cambiar contraseña de usuario
     *
     * @param string $data
     * @return void
     */
    public function changePassword(array $data)
    {
        $user = $this->userRepository->getUserById($data['id']);
        if (!$user)
            throw new HTTPException(lang('User.userNotFound'), Response::HTTP_NOT_FOUND);

        if (password_hash($data['password'], PASSWORD_BCRYPT) == $user->password)
            throw new HTTPException(lang('User.invalidCurrentPassword'), Response::HTTP_UNAUTHORIZED);

        $updated = $this->userRepository->updateUser($data['id'], ['password' => password_hash($data['password'], PASSWORD_BCRYPT)]);
        if (!$updated)
            throw new HTTPException(lang('User.passwordChangeFailed'), Response::HTTP_BAD_REQUEST);

        return true;
    }

    /**
     * Eliminar usuario
     *
     * @param string $id
     * @return void
     */
    public function delete(string $id)
    {
        $user = $this->userRepository->getUserById($id);
        if (!$user)
            throw new HTTPException(lang('User.userNotFound'), Response::HTTP_NOT_FOUND);

        $deleted = $this->userRepository->deleteUser($id);
        if (!$deleted)
            throw new HTTPException(lang('User.userDeletionFailed'), Response::HTTP_BAD_REQUEST);

        return true;
    }

    /**
     * Restaurar usuario eliminado lógicamente
     *
     * @param string $id
     * @return void
     */
    public function restore(string $id)
    {
        $user = $this->userRepository->getUserById($id, true);
        if (!$user)
            throw new HTTPException(lang('User.userNotFound'), Response::HTTP_NOT_FOUND);

        $restored = $this->userRepository->restoreUser($id);
        if (!$restored)
            throw new HTTPException(lang('User.userRestoreFailed'), Response::HTTP_BAD_REQUEST);

        return true;
    }

    /**
     * Obtener usuarios por rol
     *
     * @param string $role
     * @return array
     */
    public function getByRole(string $role)
    {
        return $this->userRepository->getByRole($role);
    }

    /**
     * Generar una contraseña aleatoria
     *
     * @param int $length
     * @return string
     */
    private function generateRandomPassword($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+-=';
        $charactersLength = strlen($characters);
        $randomPassword = '';
        for ($i = 0; $i < $length; $i++) {
            $randomPassword .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomPassword;
    }
}
