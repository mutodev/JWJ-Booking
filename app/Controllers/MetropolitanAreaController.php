<?php

namespace App\Controllers;

use App\Services\MetropolitanAreaService;
use CodeIgniter\RESTful\ResourceController;

class MetropolitanAreaController extends ResourceController
{
    protected $service;

    public function __construct()
    {
        $this->service = new MetropolitanAreaService();
    }

    public function getAll()
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.metropolitan_area_list'), $this->service->getAll()));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function getAllActive()
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.metropolitan_area_list_active'), $this->service->getAllActive()));
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
                ->setJSON(create_response(lang('App.metropolitan_area_found'), $this->service->getById($id)));
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
                ->setJSON(create_response(lang('App.metropolitan_area_created'), $this->service->create($data)));
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
                ->setJSON(create_response(lang('App.metropolitan_area_updated'), $this->service->update($id, $data)));
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
                ->setJSON(create_response(lang('App.metropolitan_area_deleted'), $this->service->delete($id)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }
}
