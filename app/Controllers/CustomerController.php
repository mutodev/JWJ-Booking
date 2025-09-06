<?php

namespace App\Controllers;

use App\Services\CustomerService;
use CodeIgniter\RESTful\ResourceController;

class CustomerController extends ResourceController
{
    protected $service;

    public function __construct()
    {
        $this->service = new CustomerService();
    }

    public function getAll()
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Lista de clientes', $this->service->getAll()));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function getById($id)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Cliente encontrado', $this->service->getById($id)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function searchByName($name)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response("Resultados de búsqueda", $this->service->searchByName($name)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function create()
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);

            return $this->response
                ->setStatusCode(201)
                ->setJSON(create_response('Cliente creado', $this->service->create($data)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function updateData($id)
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Cliente actualizado', $this->service->update($id, $data)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function deleteData($id)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Cliente eliminado', $this->service->delete($id)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }
}
