<?php

namespace App\Controllers;

use App\Services\CustomServiceService;
use CodeIgniter\HTTP\Response;
use CodeIgniter\RESTful\ResourceController;

class CustomServiceController extends ResourceController
{
    protected $service;

    public function __construct()
    {
        $this->service = new CustomServiceService();
    }

    /**
     * Listar todos los servicios personalizados
     */
    public function getAll()
    {
        try {
            return $this->response
                ->setStatusCode(Response::HTTP_OK)
                ->setJSON(create_response(lang('App.custom_service_list'), $this->service->getAll()));
        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    /**
     * Listar los servicios personalizados activos
     */
    public function getAllActive()
    {
        try {
            return $this->response
                ->setStatusCode(Response::HTTP_OK)
                ->setJSON(create_response(lang('App.custom_service_list'), $this->service->getAllActive()));
        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    /**
     * Obtener un servicio personalizado por ID
     */
    public function getById($id)
    {
        try {
            return $this->response
                ->setStatusCode(Response::HTTP_OK)
                ->setJSON(create_response(lang('App.custom_service_detail'), $this->service->getById($id)));
        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    /**
     * Crear un servicio personalizado
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true) ?? [];

            return $this->response
                ->setStatusCode(Response::HTTP_CREATED)
                ->setJSON(create_response(
                    lang('App.custom_service_created'),
                    $this->service->create($data, $this->currentUserLabel())
                ));
        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    /**
     * Actualizar un servicio personalizado
     */
    public function updateData(string $id)
    {
        try {
            $data = $this->request->getJSON(true) ?? [];

            return $this->response
                ->setStatusCode(Response::HTTP_OK)
                ->setJSON(create_response(
                    lang('App.custom_service_updated'),
                    $this->service->update($id, $data, $this->currentUserLabel())
                ));
        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    /**
     * Eliminar un servicio personalizado (soft delete)
     */
    public function deleteData($id)
    {
        try {
            return $this->response
                ->setStatusCode(Response::HTTP_OK)
                ->setJSON(create_response(lang('App.custom_service_deleted'), $this->service->delete($id)));
        } catch (\Throwable $th) {
            return $this->errorResponse($th);
        }
    }

    private function errorResponse(\Throwable $th)
    {
        $code = (int) $th->getCode();
        if ($code < 400 || $code > 599) {
            log_message('error', 'CustomService error: ' . $th->getMessage());
            $code = 500;
        }

        return $this->response
            ->setStatusCode($code)
            ->setJSON(['message' => $th->getMessage()]);
    }

    /**
     * Etiqueta del admin autenticado para las columnas de auditoría.
     */
    private function currentUserLabel(): string
    {
        try {
            $user = service('auth')->user();
            if (!$user) {
                return 'System';
            }

            $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            if ($name !== '') {
                return $name;
            }

            return (string) ($user->email ?? 'System');
        } catch (\Throwable $e) {
            return 'System';
        }
    }
}
