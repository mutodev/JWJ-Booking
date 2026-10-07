<?php

namespace App\Controllers;

use App\Services\ReservationCustomServiceService;
use CodeIgniter\RESTful\ResourceController;

class ReservationCustomServiceController extends ResourceController
{
    protected $service;

    public function __construct()
    {
        $this->service = new ReservationCustomServiceService();
    }

    /**
     * GET /reservation-custom-services/by-reservation/{reservationId}
     */
    public function getByReservation($reservationId)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Reservation custom services', $this->service->getByReservation((string) $reservationId)));
        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    /**
     * POST /reservation-custom-services
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true) ?? [];

            return $this->response
                ->setStatusCode(201)
                ->setJSON(create_response('Custom service added', $this->service->create($data, $this->currentUserLabel())));
        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    /**
     * PUT /reservation-custom-services/{id}
     */
    public function updateData($id)
    {
        try {
            $data = $this->request->getJSON(true) ?? [];

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Custom service updated', $this->service->update((string) $id, $data, $this->currentUserLabel())));
        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    /**
     * DELETE /reservation-custom-services/{id}
     */
    public function deleteData($id)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Custom service removed', $this->service->delete((string) $id)));
        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    private function errorResponse(\Throwable $th)
    {
        $code = (int) $th->getCode();
        if ($code < 400 || $code > 599) {
            log_message('error', 'ReservationCustomService error: ' . $th->getMessage());
            $code = 500;
        }

        return $this->response
            ->setStatusCode($code)
            ->setJSON(['message' => $th->getMessage()]);
    }

    /**
     * Etiqueta del admin autenticado para las columnas de auditoría.
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
