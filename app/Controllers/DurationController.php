<?php

namespace App\Controllers;

use App\Services\DurationService;
use CodeIgniter\RESTful\ResourceController;

class DurationController extends ResourceController
{
    protected $service;

    public function __construct()
    {
        $this->service = new DurationService();
    }

    /**
     * Obtener todas las duraciones
     */
    public function getAll()
    {
        try {
            $durations = $this->service->getAllDurations(true);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.duration_list'), $durations));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Obtener duración por ID
     */
    public function getById($id = null)
    {
        try {
            $duration = $this->service->getDurationById($id);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.duration_found'), $duration));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Obtener duraciones por service_price_id
     */
    public function getByServicePriceId($servicePriceId)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.duration_list'), $this->service->getDurationsByServicePrice($servicePriceId, true)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Crear nueva duración
     */
    public function create()
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.duration_created'), $this->service->createDuration($data)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Actualizar duración
     */
    public function update($id = null)
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.duration_updated'), $this->service->updateDuration($id, $data)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Eliminar duración
     */
    public function delete($id = null)
    {
        try {
            $result = $this->service->deleteDuration($id);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response($result['message'], $result['data']));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Activar duración
     */
    public function activate($id = null)
    {
        try {
            $result = $this->service->activateDuration($id);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response($result['message'], $result['data']));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Desactivar duración
     */
    public function deactivate($id = null)
    {
        try {
            $result = $this->service->deactivateDuration($id);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response($result['message'], $result['data']));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Desactivar todas las duraciones de un service_price_id
     */
    public function deactivateAllByServicePrice($servicePriceId = null)
    {
        try {
            $result = $this->service->deactivateAllForServicePrice($servicePriceId);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response($result['message'], $result['data']));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }
}