<?php

namespace App\Controllers;

use App\Services\ChildrenAgeRangeService;
use CodeIgniter\RESTful\ResourceController;

class ChildrenAgeRangeController extends ResourceController
{
    protected $service;

    public function __construct()
    {
        $this->service = new ChildrenAgeRangeService();
    }

    /**
     * Obtener todos los rangos de edad
     */
    public function getAll()
    {
        try {
            $ranges = $this->service->getAllRanges(true);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.age_range_list'), $ranges));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Obtener rango de edad por ID
     */
    public function getById($id = null)
    {
        try {
            $range = $this->service->getRangeById($id);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.age_range_found'), $range));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Obtener rangos por service_price_id
     */
    public function getByServicePriceId($servicePriceId)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.service_age_ranges'), $this->service->getRangesByServicePrice($servicePriceId, true)));
        } catch (\Throwable $th) {
            print_r($th);
            die();
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Crear nuevo rango de edad
     */
    public function create()
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.age_range_created'), $this->service->createRange($data)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Actualizar rango de edad
     */
    public function update($id = null)
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.age_range_updated'), $this->service->updateRange($id, $data)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Eliminar rango de edad
     */
    public function delete($id = null)
    {
        try {
            $result = $this->service->deleteRange($id);

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
     * Activar rango de edad
     */
    public function activate($id = null)
    {
        try {
            $result = $this->service->activateRange($id);

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
     * Desactivar rango de edad
     */
    public function deactivate($id = null)
    {
        try {
            $result = $this->service->deactivateRange($id);

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
     * Desactivar todos los rangos de un service_price_id
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
