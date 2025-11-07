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
     * Listar todos los códigos promocionales (Admin)
     * GET /promo-codes
     */
    public function index()
    {
        try {
            $promoCodes = $this->service->getAllPromoCodes();

            return $this->response
                ->setStatusCode(200)
                ->setJSON(
                    create_response(
                        true,
                        $promoCodes,
                        'Promo codes retrieved successfully'
                    )
                );
        } catch (\Exception $e) {
            log_message('error', 'Error getting promo codes: ' . $e->getMessage());
            return $this->response
                ->setStatusCode(500)
                ->setJSON(
                    create_response(
                        false,
                        null,
                        'An error occurred while retrieving promo codes'
                    )
                );
        }
    }

    /**
     * Crear un nuevo código promocional (Admin)
     * POST /promo-codes
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            $promoCode = $this->service->createPromoCode($data);

            return $this->response
                ->setStatusCode(201)
                ->setJSON(
                    create_response(
                        true,
                        $promoCode,
                        'Promo code created successfully'
                    )
                );
        } catch (\Exception $e) {
            log_message('error', 'Error creating promo code: ' . $e->getMessage());
            return $this->response
                ->setStatusCode(500)
                ->setJSON(
                    create_response(
                        false,
                        null,
                        $e->getMessage()
                    )
                );
        }
    }

    /**
     * Actualizar un código promocional (Admin)
     * PUT /promo-codes/{id}
     */
    public function update($id = null)
    {
        try {
            if (!$id) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON(
                        create_response(
                            false,
                            null,
                            'Promo code ID is required'
                        )
                    );
            }

            $data = $this->request->getJSON(true);

            $promoCode = $this->service->updatePromoCode($id, $data);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(
                    create_response(
                        true,
                        $promoCode,
                        'Promo code updated successfully'
                    )
                );
        } catch (\Exception $e) {
            log_message('error', 'Error updating promo code: ' . $e->getMessage());
            return $this->response
                ->setStatusCode(500)
                ->setJSON(
                    create_response(
                        false,
                        null,
                        $e->getMessage()
                    )
                );
        }
    }

    /**
     * Eliminar un código promocional (Admin)
     * DELETE /promo-codes/{id}
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON(
                        create_response(
                            false,
                            null,
                            'Promo code ID is required'
                        )
                    );
            }

            $this->service->deletePromoCode($id);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(
                    create_response(
                        true,
                        null,
                        'Promo code deleted successfully'
                    )
                );
        } catch (\Exception $e) {
            log_message('error', 'Error deleting promo code: ' . $e->getMessage());
            return $this->response
                ->setStatusCode(500)
                ->setJSON(
                    create_response(
                        false,
                        null,
                        $e->getMessage()
                    )
                );
        }
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
