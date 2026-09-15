<?php

namespace App\Controllers;

use App\Services\PaymentAccessService;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\RESTful\ResourceController;

/**
 * Public, unauthenticated redemption endpoint for the `/pay/{token}` links
 * sent by email (reservations and custom payment links alike). Never returns
 * 401/403/419 — those trip the frontend's axios interceptor and would bounce
 * an anonymous customer to /login.
 */
class PaymentGatewayController extends ResourceController
{
    protected $service;

    public function __construct()
    {
        $this->service = new PaymentAccessService();
    }

    /** GET /api/pay/(:segment) */
    public function redeem($token = null)
    {
        try {
            $redirectUrl = $this->service->redeem((string) $token);

            return $this->response->setStatusCode(200)
                ->setJSON(create_response('Redirect ready', ['redirect_url' => $redirectUrl]));
        } catch (\Throwable $th) {
            return $this->failFromException($th);
        }
    }

    private function failFromException(\Throwable $th)
    {
        $statusCode = 500;
        if ($th instanceof HTTPException && $th->getCode() >= 400 && $th->getCode() < 600) {
            $statusCode = $th->getCode();
        }

        if ($statusCode >= 500) {
            log_message('error', 'PaymentGatewayController error: ' . $th->getMessage());
        }

        return $this->response->setStatusCode($statusCode)
            ->setJSON([
                'status'  => 'error',
                'reason'  => $this->reasonFor($statusCode, $th->getMessage()),
                'message' => $th->getMessage(),
            ]);
    }

    /**
     * Both flows reuse existing HTTPException call sites (regeneratePaymentSession,
     * regenerateSession) that don't share status codes 1:1 for "paid" vs
     * "cancelled" — e.g. a paid reservation and a cancelled one both throw 400.
     * Sniff the message instead of trusting the status code alone.
     */
    private function reasonFor(int $statusCode, string $message): string
    {
        $message = strtolower($message);

        if (str_contains($message, 'paid')) {
            return 'paid';
        }

        if (str_contains($message, 'cancel')) {
            return 'cancelled';
        }

        return match ($statusCode) {
            404, 410 => 'expired',
            default  => 'error',
        };
    }
}
