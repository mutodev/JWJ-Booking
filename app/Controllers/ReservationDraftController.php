<?php

namespace App\Controllers;

use App\Services\ReservationDraftService;
use CodeIgniter\RESTful\ResourceController;

/**
 * Controlador REST para gestión de drafts de reservas
 * Maneja endpoints para visualizar carritos abandonados
 */
class ReservationDraftController extends ResourceController
{
    protected $service;

    public function __construct()
    {
        $this->service = new ReservationDraftService();
    }

    /**
     * Listar todos los drafts (Admin/Coordinador)
     * GET /reservation-drafts
     */
    public function index()
    {
        try {
            $drafts = $this->service->getAllDrafts();

            return $this->response
                ->setStatusCode(200)
                ->setJSON(
                    create_response(
                        true,
                        $drafts,
                        'Reservation drafts retrieved successfully'
                    )
                );
        } catch (\Exception $e) {
            log_message('error', 'Error getting reservation drafts: ' . $e->getMessage());
            return $this->response
                ->setStatusCode(500)
                ->setJSON(
                    create_response(
                        false,
                        null,
                        'An error occurred while retrieving reservation drafts'
                    )
                );
        }
    }

    /**
     * Obtener estadísticas de funnel
     * GET /reservation-drafts/stats
     */
    public function stats()
    {
        try {
            $stats = $this->service->getFunnelStats();

            return $this->response
                ->setStatusCode(200)
                ->setJSON(
                    create_response(
                        true,
                        $stats,
                        'Stats retrieved successfully'
                    )
                );
        } catch (\Exception $e) {
            log_message('error', 'Error getting draft stats: ' . $e->getMessage());
            return $this->response
                ->setStatusCode(500)
                ->setJSON(
                    create_response(
                        false,
                        null,
                        'An error occurred while retrieving stats'
                    )
                );
        }
    }

    /**
     * Obtener drafts abandonados (no completados)
     * GET /reservation-drafts/abandoned
     */
    public function abandoned()
    {
        try {
            $hours = $this->request->getGet('hours') ?? 24;
            $abandoned = $this->service->getAbandoned((int)$hours);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(
                    create_response(
                        true,
                        $abandoned,
                        'Abandoned carts retrieved successfully'
                    )
                );
        } catch (\Exception $e) {
            log_message('error', 'Error getting abandoned carts: ' . $e->getMessage());
            return $this->response
                ->setStatusCode(500)
                ->setJSON(
                    create_response(
                        false,
                        null,
                        'An error occurred while retrieving abandoned carts'
                    )
                );
        }
    }

    /**
     * Obtener un draft específico por ID
     * GET /reservation-drafts/{id}
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON(
                        create_response(
                            false,
                            null,
                            'Draft ID is required'
                        )
                    );
            }

            $draft = $this->service->getDraftById($id);

            if (!$draft) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON(
                        create_response(
                            false,
                            null,
                            'Draft not found'
                        )
                    );
            }

            return $this->response
                ->setStatusCode(200)
                ->setJSON(
                    create_response(
                        true,
                        $draft,
                        'Draft retrieved successfully'
                    )
                );
        } catch (\Exception $e) {
            log_message('error', 'Error getting draft: ' . $e->getMessage());
            return $this->response
                ->setStatusCode(500)
                ->setJSON(
                    create_response(
                        false,
                        null,
                        'An error occurred while retrieving the draft'
                    )
                );
        }
    }
}
