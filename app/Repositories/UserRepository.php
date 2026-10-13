<?php

namespace App\Repositories;

use App\Entities\User;
use App\Models\UserModel;

class UserRepository
{
    private const PUBLIC_USER_FIELDS = '
        users.id,
        users.first_name,
        users.last_name,
        users.email,
        users.image,
        users.state,
        users.role_id,
        users.is_active,
        users.mfa_enabled,
        users.created_at,
        users.updated_at,
        users.deleted_at
    ';

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
            ->select(self::PUBLIC_USER_FIELDS)
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
                users.state,
                users.role_id,
                users.mfa_enabled,
                users.auth_version,
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
        return $this->userModel
            ->select(self::PUBLIC_USER_FIELDS)
            ->where('role_id', $roleId)
            ->findAll();
    }

    public function getUserForAuthenticationById(string $id): ?User
    {
        return $this->userModel
            ->where('id', $id)
            ->first();
    }

    public function getLoginDataById(string $id): ?array
    {
        $user = $this->userModel
            ->select('
                users.id,
                users.first_name,
                users.last_name,
                users.email,
                users.image,
                users.is_active,
                users.state,
                users.role_id,
                users.auth_version,
                roles.name AS role_name
            ')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.id', $id)
            ->where('users.state', true)
            ->where('roles.is_active', true)
            ->first();

        return $user ? $user->toArray() : null;
    }

    public function updateMfa(string $userId, array $data): bool
    {
        $allowed = [
            'mfa_secret',
            'mfa_enabled',
            'mfa_confirmed_at',
            'mfa_recovery_codes',
            'mfa_last_used_step',
        ];
        $filtered = array_intersect_key($data, array_flip($allowed));
        return $filtered !== [] && $this->userModel->update($userId, $filtered);
    }

    public function claimMfaStep(string $userId, int $step, array $data = []): bool
    {
        $allowed = ['mfa_enabled', 'mfa_confirmed_at', 'mfa_recovery_codes'];
        $filtered = array_intersect_key($data, array_flip($allowed));
        $filtered['mfa_last_used_step'] = $step;

        $builder = db_connect()->table('users');
        $builder
            ->where('id', $userId)
            ->where('deleted_at', null)
            ->groupStart()
                ->where('mfa_last_used_step', null)
                ->orWhere('mfa_last_used_step <', $step)
            ->groupEnd()
            ->update($filtered);

        return db_connect()->affectedRows() === 1;
    }

    public function replaceRecoveryCodes(string $userId, string $expectedJson, string $newJson): bool
    {
        $builder = db_connect()->table('users');
        $builder
            ->where('id', $userId)
            ->where('deleted_at', null)
            ->where('mfa_recovery_codes', $expectedJson)
            ->update(['mfa_recovery_codes' => $newJson]);

        return db_connect()->affectedRows() === 1;
    }

    public function resetMfa(string $userId): bool
    {
        $user = $this->getUserForAuthenticationById($userId);
        if (!$user) {
            return false;
        }

        return $this->userModel->update($userId, [
            'mfa_secret' => null,
            'mfa_enabled' => false,
            'mfa_confirmed_at' => null,
            'mfa_recovery_codes' => null,
            'mfa_last_used_step' => null,
            'auth_version' => ((int) ($user->auth_version ?? 1)) + 1,
        ]);
    }

    public function revokeSessions(string $userId): bool
    {
        $user = $this->getUserForAuthenticationById($userId);
        if (!$user) {
            return false;
        }

        return $this->userModel->update($userId, [
            'auth_version' => ((int) ($user->auth_version ?? 1)) + 1,
        ]);
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
        // Hash de la contraseña si viene en los datos
        if (isset($data['password']) && !empty($data['password'])) {
            // Solo hashear si no está ya hasheada (evitar double hashing)
            if (!password_get_info($data['password'])['algo']) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
        }

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
