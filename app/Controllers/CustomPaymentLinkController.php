<?php

namespace App\Controllers;

use App\Services\CustomPaymentLinkService;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\RESTful\ResourceController;

/**
 * B5 — Admin REST surface for custom payment links.
 *
 * Every route is registered under the `verifyToken` filter. The amount and
 * description are validated and priced server-side by the service; this
 * controller only parses HTTP and maps HTTPException codes.
 */
class CustomPaymentLinkController extends ResourceController
{
    protected $service;

    public function __construct()
    {
        $this->service = new CustomPaymentLinkService();
    }

    /** GET /api/payment-links */
    public function index()
    {
        try {
            $links = $this->service->listLinks();

            return $this->response->setStatusCode(200)
                ->setJSON(create_response('Payment links retrieved successfully', $links));
        } catch (\Throwable $th) {
            return $this->fail($th);
        }
    }

    /** POST /api/payment-links */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true) ?? [];

            $link = $this->service->createLink($data, $this->currentUserLabel());

            return $this->response->setStatusCode(201)
                ->setJSON(create_response('Payment link created successfully', $link));
        } catch (\Throwable $th) {
            return $this->fail($th);
        }
    }

    /** GET /api/payment-links/(:segment) */
    public function show($id = null)
    {
        try {
            $link = $this->service->getLink((string) $id);

            return $this->response->setStatusCode(200)
                ->setJSON(create_response('Payment link retrieved successfully', $link));
        } catch (\Throwable $th) {
            return $this->fail($th);
        }
    }

    /** POST /api/payment-links/(:segment)/send-email */
    public function sendEmail($id = null)
    {
        try {
            $link = $this->service->sendLinkEmail((string) $id);

            return $this->response->setStatusCode(200)
                ->setJSON(create_response('Payment link email sent', $link));
        } catch (\Throwable $th) {
            return $this->fail($th);
        }
    }

    /** POST /api/payment-links/(:segment)/cancel */
    public function cancel($id = null)
    {
        try {
            $link = $this->service->cancelLink((string) $id);

            return $this->response->setStatusCode(200)
                ->setJSON(create_response('Payment link cancelled', $link));
        } catch (\Throwable $th) {
            return $this->fail($th);
        }
    }

    /** DELETE /api/payment-links/(:segment) */
    public function delete($id = null)
    {
        try {
            $this->service->deleteLink((string) $id);

            return $this->response->setStatusCode(200)
                ->setJSON(create_response('Payment link deleted', null));
        } catch (\Throwable $th) {
            return $this->fail($th);
        }
    }

    /**
     * Map an exception to a JSON error response, honoring HTTPException codes.
     */
    private function fail(\Throwable $th)
    {
        $statusCode = 500;
        if ($th instanceof HTTPException && $th->getCode() >= 400 && $th->getCode() < 600) {
            $statusCode = $th->getCode();
        }

        if ($statusCode >= 500) {
            log_message('error', 'CustomPaymentLinkController error: ' . $th->getMessage());
        }

        return $this->response->setStatusCode($statusCode)
            ->setJSON(['message' => $th->getMessage()]);
    }

    /**
     * Best-effort human label of the authenticated admin for the audit column.
     */
    private function currentUserLabel(): string
    {
        try {
            $user = service('auth')->user();
            if (!$user) {
                return 'System';
            }

            $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            if ($name !== '') {
                return $name;
            }

            return (string) ($user->email ?? 'System');
        } catch (\Throwable $e) {
            return 'System';
        }
    }
}
