<?php

namespace App\Controllers;

use App\Services\ServicePriceService;
use CodeIgniter\RESTful\ResourceController;

class ServicePriceController extends ResourceController
{
    protected $service;

    public function __construct()
    {
        $this->service = new ServicePriceService();
    }

    /**
     * Obtener todos los precios de servicios.
     */
    public function getAll()
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(
                    create_response(
                        lang('App.service_price_list'),
                        $this->service->getAll()
                    )
                );
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Obtener los precios del servicio por condado 
     */
    public function getAllByCounty($countyId)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(
                    create_response(
                        lang('App.service_price_list'),
                        $this->service->getAllByCounty($countyId)
                    )
                );
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Obtener un precio por ID.
     */
    public function getById($id)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(
                    create_response(
                        lang('App.service_price_detail'),
                        $this->service->getById($id)
                    )
                );
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Obtener un precio por ID.
     */
    public function getByServiceAndCounty($serviceId, $countyId)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(
                    create_response(
                        lang('App.service_price_detail_by_service_county'),
                        $this->service->getByServiceAndCounty($serviceId, $countyId)
                    )
                );
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Crear un nuevo precio de servicio.
     */
    public function createData()
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);
            return $this->response
                ->setStatusCode(201)
                ->setJSON(
                    create_response(
                        lang('App.service_price_created'),
                        $this->service->create($data)
                    )
                );
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Actualizar un precio de servicio por ID.
     */
    public function updateData($id)
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.service_price_updated'), $this->service->update($id, $data)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Eliminar (soft delete) un precio de servicio.
     */
    public function deleteDelete($id)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.service_price_deleted'), $this->service->delete($id)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }
}
