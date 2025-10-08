<?php

namespace App\Controllers;

use App\Services\ReservationService;
use CodeIgniter\RESTful\ResourceController;

class ReservationController extends ResourceController
{
    protected $service;

    public function __construct()
    {
        $this->service = new ReservationService();
    }

    public function getAll()
    {
        try {
            return $this->response->setStatusCode(200)
                ->setJSON(create_response(lang('Reservation.list'), $this->service->getAll()));
        } catch (\Throwable $th) {
            print_r($th);
            die();
            return $this->response->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function getById($id)
    {
        try {
            return $this->response->setStatusCode(200)
                ->setJSON(create_response(lang('Reservation.found'), $this->service->getById($id)));
        } catch (\Throwable $th) {
            return $this->response->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function create()
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);

            return $this->response->setStatusCode(201)
                ->setJSON(create_response(lang('Reservation.created'), $this->service->create($data)));
        } catch (\Throwable $th) {
            return $this->response->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Crear reserva desde el formulario del frontend
     * Recibe todos los datos de los steps y los mapea correctamente
     */
    public function createFromForm()
    {
        try {
            $json = $this->request->getBody();
            $formData = json_decode($json, true);

            return $this->response->setStatusCode(201)
                ->setJSON(create_response(lang('Reservation.created'), $this->service->createFromForm($formData)));
        } catch (\Throwable $th) {
            return $this->response->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function updateData($id)
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);

            return $this->response->setStatusCode(200)
                ->setJSON(create_response(lang('Reservation.updated'), $this->service->update($id, $data)));
        } catch (\Throwable $th) {
            return $this->response->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function deleteData($id)
    {
        try {
            return $this->response->setStatusCode(200)
                ->setJSON(create_response(lang('Reservation.deleted'), $this->service->delete($id)));
        } catch (\Throwable $th) {
            return $this->response->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function sendPaymentEmail()
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);

            $this->service->sendPaymentEmail($data['reservationId'], $data['paymentUrl']);

            return $this->response->setStatusCode(200)
                ->setJSON(create_response('Payment email sent successfully', null));
        } catch (\Throwable $th) {
            return $this->response->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }
}
