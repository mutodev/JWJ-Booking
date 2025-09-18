<?php

namespace App\Controllers;

use App\Services\ZipCodeService;
use CodeIgniter\RESTful\ResourceController;

class ZipCodeController extends ResourceController
{
    protected $service;

    public function __construct()
    {
        $this->service = new ZipCodeService();
    }

    public function getAll()
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Lista de códigos postales', $this->service->getAll()));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function getAllAndCity()
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Lista de códigos postales con ciudad', $this->service->getAllAndCity()));
        } catch (\Throwable $th) {
            print_r($th);
            die();
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
                ->setJSON(create_response('Código postal encontrado', $this->service->getById($id)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function getByCityAndCode($cityId, $code)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Código postal encontrado', $this->service->getByCityAndCode($cityId, $code)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function getByCity($id)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Código postal por ciudad', $this->service->getByCity($id)));
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
                ->setJSON(create_response('Código postal creado', $this->service->create($data)));
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
                ->setJSON(create_response('Código postal actualizado', $this->service->update($id, $data)));
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
                ->setJSON(create_response('Código postal eliminado', $this->service->delete($id)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }
}
