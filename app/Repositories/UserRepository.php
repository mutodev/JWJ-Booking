<?php

namespace App\Repositories;

use App\Entities\User;
use App\Models\UserModel;

class UserRepository
{
    protected UserModel $userModel;

    protected $allowedFields = [
        'first_name',
        'last_name',
        'email',
        'image',
        'state',
        'role_id',
        'is_active',
        'password'
    ];

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Consulta de usuario por id
     * 
     * @param string $id
     * @return User
     */
    public function getUserById(string $id): ?User
    {
        return $this->userModel
            ->where('id', $id)
            ->first();
    }

    /**
     * Consulta de usuario por correo
     * 
     * @param string $email
     * @return User
     */
    public function getUserByEmail(string $email): ?User
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
                users.is_active,
                roles.name AS role_name
             ')
            ->join('roles', 'roles.id = users.role_id')
            ->where('email', $email)
            ->where('state', true)
            ->where('roles.is_active', true)
            ->first();

        if (!$data || !password_verify($password, $data->password))
            return null;

        $data = $data->toArray();
        unset($data['password']);
        return $data;
    }

    /**
     * Obtiene todos los usuarios por rol
     */
    public function getByRole(string $roleId): array
    {
        return $this->userModel->where('role_id', $roleId)
            ->findAll();
    }

    /**
     * Actualiza la contraseña de un usuario
     * @param string $userId
     * @param string $newPassword
     * @return bool
     */
    public function updatePassword(string $userId, string $newPassword): bool
    {
        return $this->userModel->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);
    }

    /**
     * Actualiza los datos generales del usuario
     * @param string $userId
     * @param array $data
     * @return bool
     */
    public function updateUser(string $userId, array $data): bool
    {
        $filtered = array_intersect_key($data, array_flip($this->allowedFields));
        if (empty($filtered)) {
            return false;
        }

        return $this->userModel->update($userId, $filtered);
    }

    /**
     * Crear un usuario nuevo
     * @param array $data
     * @return string|false El ID del usuario creado o false en caso de error
     */
    public function createUser(array $data)
    {
        // Hash de la contraseña antes de guardar
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        // Filtrar datos permitidos
        $filtered = array_intersect_key($data, array_flip($this->allowedFields));

        if (empty($filtered)) {
            return false;
        }

        return $this->userModel->insert($filtered, true);
    }

    /**
     * Elimina un usuario (Soft Delete)
     * @param string $userId
     * @return bool
     */
    public function deleteUser(string $userId): bool
    {
        return $this->userModel->delete($userId);
    }

    /**
     * Restaurar un usuario eliminado lógicamente
     * @param string $userId
     * @return bool
     */
    public function restoreUser(string $userId): bool
    {
        return $this->userModel->update($userId, ['deleted_at' => null]);
    }

    /**
     * Obtener usuarios eliminados lógicamente
     */
    public function getDeletedUsers(): array
    {
        return $this->userModel->onlyDeleted()->findAll();
    }

    /**
     * ***************************************
     * MÉTODOS DE MENÚ Y PERMISOS
     *****************************************
     */

    /**
     * Obtener todos los permisos de menú de un usuario
     * @param string $userId
     * @return array
     */
    public function getUserPermissions(string $userId): array
    {
        return $this->userModel->select('
                menus.id as menu_id,
                menus.name as menu_name,
                menus.uri as menu_uri,
                menus.icon as menu_icon,
                menus.order as menu_order,
                menus.parent_id as menu_parent_id,
                role_menu_permissions.can_view,
                role_menu_permissions.can_create,
                role_menu_permissions.can_update,
                role_menu_permissions.can_delete
            ')
            ->join('roles', 'roles.id = users.role_id')
            ->join('role_menu_permissions', 'role_menu_permissions.role_id = roles.id')
            ->join('menus', 'menus.id = role_menu_permissions.menu_id')
            ->where('users.id', $userId)
            ->where('users.is_active', true)
            ->where('roles.is_active', true)
            ->where('menus.is_active', true)
            ->where('role_menu_permissions.can_view', true)
            ->orderBy('menus.order', 'ASC')
            ->findAll();
    }
}
