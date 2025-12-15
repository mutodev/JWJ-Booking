<?php

namespace App\Controllers;

use App\Services\ServiceService;
use CodeIgniter\HTTP\Response;
use CodeIgniter\RESTful\ResourceController;

class ServiceController extends ResourceController
{
    protected $service;

    public function __construct()
    {
        $this->service = new ServiceService();
    }

    /**
     * Listar todos los servicios
     */
    public function getAll()
    {
        try {
            return $this->response
                ->setStatusCode(Response::HTTP_OK)
                ->setJSON(
                    create_response(
                        lang('App.service_list'),
                        $this->service->getAll()
                    )
                );
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Listar todos los servicios activos
     */
    public function getAllActive()
    {
        try {
            return $this->response
                ->setStatusCode(Response::HTTP_OK)
                ->setJSON(
                    create_response(
                        lang('App.service_list'),
                        $this->service->getAllActive()
                    )
                );
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Obtener un servicio por ID
     */
    public function getById($id)
    {
        try {
            return $this->response
                ->setStatusCode(Response::HTTP_OK)
                ->setJSON(
                    create_response(
                        lang('App.service_detail'),
                        $this->service->getById( $id)
                    )
                );
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Crear un nuevo servicio
     */
    public function create()
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);

            return $this->response
                ->setStatusCode(Response::HTTP_CREATED)
                ->setJSON(
                    create_response(
                        lang('App.service_created_successfully'),
                        $this->service->create($data)
                    )
                );
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Actualizar un servicio existente
     */
    public function updateData(string $id)
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);
            return $this->response
                ->setStatusCode(Response::HTTP_OK)
                ->setJSON(
                    create_response(
                        lang('App.service_updated_successfully'),
                        $this->service->update($id, $data)
                    )
                );
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Actualizar un servicio con imagen
     */
    public function updateWithImage(string $id)
    {
        try {
            return $this->response
                ->setStatusCode(Response::HTTP_OK)
                ->setJSON(
                    create_response(
                        lang('App.service_updated_successfully'),
                        $this->service->updateWithImage($id, $this->request)
                    )
                );
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Eliminar un servicio (soft delete)
     */
    public function deleteData($id)
    {
        try {
            return $this->response
                ->setStatusCode(Response::HTTP_OK)
                ->setJSON(
                    create_response(
                        lang('App.service_deleted_successfully'),
                        $this->service->delete($id)
                    )
                );
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }
}
