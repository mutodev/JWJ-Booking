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
                        'Lista de servicios',
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
     * Obtener un servicio por ID
     */
    public function getById($id)
    {
        try {
            return $this->response
                ->setStatusCode(Response::HTTP_OK)
                ->setJSON(
                    create_response(
                        "Detalle del servicio {$id}",
                        $this->service->getById((int) $id)
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
                        'Servicio creado correctamente',
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
    public function updateData($id)
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);

            return $this->response
                ->setStatusCode(Response::HTTP_OK)
                ->setJSON(
                    create_response(
                        "Servicio actualizado correctamente",
                        $this->service->update((int) $id, $data)
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
                        "Servicio {$id} eliminado correctamente",
                        $this->service->delete((int) $id)
                    )
                );
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }
}
