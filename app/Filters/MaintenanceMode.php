<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MaintenanceMode implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!$this->isEnabled()) {
            return null;
        }

        return service('response')
            ->setStatusCode(503)
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->setHeader('X-Robots-Tag', 'noindex, nofollow')
            ->setContentType('text/html')
            ->setBody(view('maintenance'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    private function isEnabled(): bool
    {
        return filter_var(
            getenv('app.maintenanceEnabled') ?: 'false',
            FILTER_VALIDATE_BOOLEAN
        );
    }
}
