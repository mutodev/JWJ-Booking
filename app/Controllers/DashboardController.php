<?php

namespace App\Controllers;

use App\Services\DashboardService;
use CodeIgniter\RESTful\ResourceController;

class DashboardController extends ResourceController
{
    protected $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
    }

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
            $startDate = $this->request->getGet('start_date');
            $endDate = $this->request->getGet('end_date');

            $data = $this->dashboardService->getReservationsStatusEvolution($startDate, $endDate);

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
            $limit = $this->request->getGet('limit') ?? 10;
            $data = $this->dashboardService->getMostPopularJamTypes($limit);

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
            $limit = $this->request->getGet('limit') ?? 10;
            $data = $this->dashboardService->getCitiesWithMostEvents($limit);

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