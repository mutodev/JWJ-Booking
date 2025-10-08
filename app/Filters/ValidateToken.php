<?php

namespace App\Filters;

use App\Repositories\UserRepository;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ValidateToken implements FilterInterface
{
    protected $tokenResponse;
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        try {
            // Intentar obtener el token de múltiples fuentes
            $token = $request->getHeaderLine('Authorization');

            // Si no se encuentra, intentar en variables de servidor
            if (empty($token)) {
                $token = $request->getServer('HTTP_AUTHORIZATION');
            }

            // También revisar en REDIRECT_HTTP_AUTHORIZATION (para algunos servidores Apache)
            if (empty($token)) {
                $token = $request->getServer('REDIRECT_HTTP_AUTHORIZATION');
            }

            if (empty($token)) {
                return service('response')->setStatusCode(403)->setJSON(['message' => 'Token not provided']);
            }

            // Remover el prefijo "Bearer " si existe
            $token = str_starts_with($token, 'Bearer ') ? substr($token, 7) : $token;
            $this->tokenResponse = verify_token($token, true);

            $userModel = new UserRepository();
            $user = $userModel->getUserByEmail($this->tokenResponse['email']);
            service('auth')->setUser($user);
        } catch (\CodeIgniter\HTTP\Exceptions\HTTPException $ex) {
            // Atrapa las excepciones HTTP lanzadas por verify_token
            return service('response')
                ->setStatusCode($ex->getCode())
                ->setJSON(['message' => $ex->getMessage()]);
        } catch (\Throwable $th) {
            return service('response')
                ->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }


    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $body = $response->getBody();
        $json = json_decode($body, true);

        $json['token'] = generate_token($this->tokenResponse);
        return $response->setJSON($json);
    }
}
