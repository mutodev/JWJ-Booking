<?php

namespace App\Controllers;

use App\Services\PromoCodeService;
use CodeIgniter\RESTful\ResourceController;

/**
 * Controlador REST para gestión de códigos promocionales
 * Maneja endpoints para validar y aplicar promo codes
 */
class PromoCodeController extends ResourceController
{
    protected $service;

    public function __construct()
    {
        $this->service = new PromoCodeService();
    }

    /**
     * Validar un código promocional por su código
     * GET /home/promo-codes/validate/{code}
     */
    public function validateCode($code = null)
    {
        try {
            if (!$code) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON(
                        create_response(
                            false,
                            null,
                            'Promo code is required'
                        )
                    );
            }

            $result = $this->service->validatePromoCode($code);

            if ($result['is_valid']) {
                return $this->response
                    ->setStatusCode(200)
                    ->setJSON(
                        create_response(
                            true,
                            $result,
                            'Promo code is valid'
                        )
                    );
            } else {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON(
                        create_response(
                            false,
                            null,
                            $result['message'] ?? 'Invalid or expired promo code'
                        )
                    );
            }
        } catch (\Exception $e) {
            log_message('error', 'Error validating promo code: ' . $e->getMessage());
            return $this->response
                ->setStatusCode(500)
                ->setJSON(
                    create_response(
                        false,
                        null,
                        'An error occurred while validating the promo code'
                    )
                );
        }
    }
}
