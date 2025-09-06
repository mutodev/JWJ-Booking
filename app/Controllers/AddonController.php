<?php

namespace App\Controllers;

use App\Services\AddonService;
use CodeIgniter\RESTful\ResourceController;

class AddonController extends ResourceController
{
    protected AddonService $service;

    public function __construct()
    {
        $this->service = new AddonService();
    }

    /**
     * Obtener todos los addons
     */
    public function getAll()
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Lista de addons', $this->service->getAll()));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Obtener todos los addons activos
     */
    public function getAllActive()
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Lista de addons activos', $this->service->getAllActive()));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Buscar addons por nombre
     */
    public function search($name)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response("Resultados de búsqueda para '{$name}'", $this->service->search($name)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Obtener addon por ID
     */
    public function getById($id)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Addon encontrado', $this->service->getById($id)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Crear un nuevo addon
     */
    public function create()
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);

            return $this->response
                ->setStatusCode(201)
                ->setJSON(create_response('Addon creado', $this->service->create($data)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Actualizar un addon
     */
    public function updateData($id)
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Addon actualizado', $this->service->update($id, $data)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Eliminar un addon
     */
    public function deleteData($id)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Addon eliminado', $this->service->delete($id)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }
}
