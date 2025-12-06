<?php

namespace App\Controllers;

use App\Services\TypeAddonService;
use CodeIgniter\RESTful\ResourceController;

class TypeAddonController extends ResourceController
{
    protected $service;

    public function __construct()
    {
        $this->service = new TypeAddonService();
    }

    public function getAll()
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Type addons list', $this->service->getAll()));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function getAllActive()
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Active type addons list', $this->service->getAllActive()));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function getById($id)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Type addon found', $this->service->getById($id)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function create()
    {
        try {
            $result = $this->service->createWithImage($this->request);

            return $this->response
                ->setStatusCode(201)
                ->setJSON(create_response('Type addon created successfully', $result));
        } catch (\Throwable $th) {
            log_message('error', 'TypeAddon creation error: ' . $th->getMessage());
            return $this->response
                ->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function updateData($id)
    {
        try {
            $result = $this->service->updateWithImage($id, $this->request);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Type addon updated successfully', $result));
        } catch (\Throwable $th) {
            log_message('error', 'TypeAddon update error: ' . $th->getMessage());
            return $this->response
                ->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function deleteData($id)
    {
        try {
            $result = $this->service->delete($id);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Type addon deleted successfully', $result));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }
}
