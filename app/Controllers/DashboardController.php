<?php

/**
 * DashboardController
 *
 * Controlador para métricas y análisis del dashboard administrativo.
 * Proporciona datos estadísticos sobre reservas, pagos, addons populares
 * y evolución temporal del negocio.
 *
 * ENDPOINTS DISPONIBLES:
 * - GET /api/dashboard/reservations-by-status      # Distribución de estados
 * - GET /api/dashboard/reservations-evolution      # Evolución temporal
 * - GET /api/dashboard/payment-status              # Estado de pagos
 * - GET /api/dashboard/invoice-status              # Estado de facturas
 * - GET /api/dashboard/popular-jam-types           # Servicios populares
 * - GET /api/dashboard/cities-with-most-events     # Ciudades top
 * - GET /api/dashboard/most-popular-addons         # Addons más vendidos
 *
 * @package App\Controllers
 * @author  JamWithJamie Team
 * @version 2.0.0
 * @since   1.0.0
 */

namespace App\Controllers;

use App\Services\DashboardService;
use CodeIgniter\RESTful\ResourceController;

/**
 * Controlador de métricas del dashboard
 */
class DashboardController extends ResourceController
{
    /**
     * Servicio de dashboard para lógica de negocio
     * @var DashboardService
     */
    protected $dashboardService;

    /**
     * Constructor del controlador
     * Inicializa el servicio de dashboard
     */
    public function __construct()
    {
        $this->dashboardService = new DashboardService();
    }

    /**
     * Obtiene distribución de reservas por estado
     *
     * Retorna estadísticas de reservas agrupadas por estado con conteos,
     * porcentajes y colores para gráficos. Estados: new, under_review,
     * confirmed, cancelled.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface JSON con estructura:
     *   {
     *     success: true,
     *     data: {
     *       data: [
     *         {status: string, label: string, count: int, percentage: float, color: string}
     *       ],
     *       total: int
     *     }
     *   }
     *
     * @example Response:
     * ```json
     * {
     *   "success": true,
     *   "data": {
     *     "data": [
     *       {"status": "new", "label": "Nueva", "count": 45, "percentage": 60.0, "color": "#17a2b8"},
     *       {"status": "confirmed", "label": "Confirmada", "count": 30, "percentage": 40.0, "color": "#28a745"}
     *     ],
     *     "total": 75
     *   }
     * }
     * ```
     */
    public function getReservationsByStatus()
    {
        try {
            $data = $this->dashboardService->getReservationsByStatus();

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'data' => $data
                ]);
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON([
                    'success' => false,
                    'message' => $th->getMessage()
                ]);
        }
    }

    public function getReservationsStatusEvolution()
    {
        try {
            $data = $this->dashboardService->getReservationsStatusEvolution();

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'data' => $data
                ]);
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON([
                    'success' => false,
                    'message' => $th->getMessage()
                ]);
        }
    }

    public function getPaymentStatus()
    {
        try {
            $data = $this->dashboardService->getPaymentStatus();

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'data' => $data
                ]);
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON([
                    'success' => false,
                    'message' => $th->getMessage()
                ]);
        }
    }

    public function getInvoiceStatus()
    {
        try {
            $data = $this->dashboardService->getInvoiceStatus();

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'data' => $data
                ]);
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON([
                    'success' => false,
                    'message' => $th->getMessage()
                ]);
        }
    }

    public function getMostPopularJamTypes()
    {
        try {
            $data = $this->dashboardService->getMostPopularJamTypes(10);

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'data' => $data
                ]);
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON([
                    'success' => false,
                    'message' => $th->getMessage()
                ]);
        }
    }

    public function getCitiesWithMostEvents()
    {
        try {
            $data = $this->dashboardService->getCitiesWithMostEvents(8);

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'data' => $data
                ]);
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON([
                    'success' => false,
                    'message' => $th->getMessage()
                ]);
        }
    }

    public function getMostPopularAddons()
    {
        try {
            $data = $this->dashboardService->getMostPopularAddons(10);

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'data' => $data
                ]);
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON([
                    'success' => false,
                    'message' => $th->getMessage()
                ]);
        }
    }
}