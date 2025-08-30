<?php

namespace App\Services;

use App\Repositories\UserRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;
use App\Services\BrevoEmailService as ServicesBrevoEmailService;

use function PHPUnit\Framework\isNull;

class LoginService
{
    protected $userRepository;
    protected $emailService;


    function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->emailService = new ServicesBrevoEmailService();
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

        if (!$data['is_active'])
            throw new HTTPException(lang('Auth.userInactive'), Response::HTTP_UNAUTHORIZED);


        $data['access'] = $this->getUserMenuTree($data['id']);

        unset($data['id']);
        return generate_token((array) $data);
    }

    /**
     * Obtener menú completo con estructura jerárquica y permisos
     * @param string $userId
     * @return array
     */
    private function getUserMenuTree(string $userId): array
    {
        $permissions = $this->userRepository->getUserPermissions($userId);

        // Organizar en estructura jerárquica
        $menuTree = [];
        foreach ($permissions as $permission) {
            if ($permission->menu_parent_id === null) {
                // Es menú principal
                $menuTree[$permission->menu_id] = [
                    'id' => $permission->menu_id,
                    'name' => $permission->menu_name,
                    'uri' => $permission->menu_uri,
                    'icon' => $permission->menu_icon,
                    'order' => $permission->menu_order,
                    'permissions' => [
                        'view' => (bool)$permission->can_view,
                        'create' => (bool)$permission->can_create,
                        'update' => (bool)$permission->can_update,
                        'delete' => (bool)$permission->can_delete
                    ],
                    'children' => []
                ];
            }
        }

        // Agregar submenús
        foreach ($permissions as $permission) {
            if ($permission->menu_parent_id !== null && isset($menuTree[$permission->menu_parent_id])) {
                $menuTree[$permission->menu_parent_id]['children'][] = [
                    'id' => $permission->menu_id,
                    'name' => $permission->menu_name,
                    'uri' => $permission->menu_uri,
                    'icon' => $permission->menu_icon,
                    'order' => $permission->menu_order,
                    'permissions' => [
                        'view' => (bool)$permission->can_view,
                        'create' => (bool)$permission->can_create,
                        'update' => (bool)$permission->can_update,
                        'delete' => (bool)$permission->can_delete
                    ]
                ];
            }
        }

        // Ordenar por order y convertir a array indexado
        usort($menuTree, function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });

        return array_values($menuTree);
    }


    /**
     * Restaurar contraseña de usuario
     *
     * @return void
     */
    public function resetPassword(array $data)
    {
        $user = $this->userRepository->getUserByEmail($data['email']);
        if (isNull($user))
            return true;

        $newPassword = generate_password(12);
        $updated = $this->userRepository->updateUser($user->id, ['password' => password_hash($newPassword, PASSWORD_BCRYPT)]);
        if (!$updated)
            return true;

        $emailSent = $this->emailService->sendEmail(
            $data['email'],
            'Password Reset - JamWithJamie',
            view('emails/reset_password', ['password' => $newPassword]),
        );

        if (!$emailSent)
            throw new HTTPException(lang('User.emailSendFailed'), Response::HTTP_BAD_REQUEST);

        return true;
    }
}
