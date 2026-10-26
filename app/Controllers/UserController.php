<?php

namespace App\Controllers;

use App\Services\UserService;
use App\Services\MfaService;
use App\Services\AuthorizationPolicy;
use App\Repositories\UserRepository;
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

            $actor = service('auth')->user();
            if ($actor && (string) $actor->id === $id) {
                $permissions = (new UserRepository())->getUserPermissions((string) $actor->id);
                $canAdministerUsers = (new AuthorizationPolicy())->allows($permissions, [
                    'menu_uri' => '/admin/config/users',
                    'permission' => 'can_update',
                ]);

                // Preserve self-service profile editing without allowing a
                // user to promote or activate their own account through JSON.
                if (!$canAdministerUsers) {
                    $data = array_intersect_key($data ?: [], array_flip([
                        'first_name', 'last_name', 'password',
                    ]));
                }
            }

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

    public function resetMfa(string $id)
    {
        try {
            $actor = service('auth')->user();
            if (!$actor) {
                throw new \CodeIgniter\HTTP\Exceptions\HTTPException('Unauthorized', 401);
            }

            (new MfaService())->resetForUser($id, $actor);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Multi-factor authentication reset successfully', true));
        } catch (\Throwable $th) {
            $statusCode = $th->getCode();
            if ($statusCode < 400 || $statusCode > 599) {
                $statusCode = 500;
            }
            return $this->response->setStatusCode($statusCode)->setJSON(['message' => $th->getMessage()]);
        }
    }
}
