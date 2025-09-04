<?php

namespace App\Services;

use App\Repositories\ServiceRepository;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\Exceptions\HTTPException;

class ServiceService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new ServiceRepository();
    }

    /**
     * Obtiene todos los servicios disponibles.
     *
     * @return array
     */
    public function getAll()
    {
        return $this->repo->getAll();
    }

    /**
     * Obtiene un servicio por ID.
     *
     * @param int $id
     * @return array
     * @throws HTTPException
     */
    public function getById(int $id)
    {
        $service = $this->repo->getById($id);
        if (!$service) {
            throw new HTTPException(
                lang('Service.notFound'),
                Response::HTTP_NOT_FOUND
            );
        }
        return $service;
    }

    /**
     * Crea un nuevo servicio.
     * Si existe y está eliminado → se restaura.
     * Si existe y está activo → lanza conflicto.
     *
     * @param array $data
     * @return int|string|bool
     * @throws HTTPException
     */
    public function create(array $data)
    {
        $existing = $this->repo->getByName($data['name'], true);

        // Caso 1: existe y está activo → conflicto
        if ($existing && $existing['deleted_at'] === null) {
            throw new HTTPException(
                lang('Service.alreadyExists', [$data['name']]),
                Response::HTTP_CONFLICT
            );
        }

        // Caso 2: existe pero soft-deleted → restaurar y actualizar datos
        if ($existing && $existing['deleted_at'] !== null) {
            $this->repo->restore($existing['id']);
            return $this->repo->update($existing['id'], $data);
        }

        // Caso 3: no existe → crear nuevo
        return $this->repo->create($data);
    }

    /**
     * Actualiza un servicio existente.
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws HTTPException
     */
    public function update(int $id, array $data)
    {
        $service = $this->repo->getById($id);
        if (!$service) {
            throw new HTTPException(
                lang('Service.notFound'),
                Response::HTTP_NOT_FOUND
            );
        }

        if (isset($data['name'])) {
            $duplicate = $this->repo->getByName($data['name']);
            if ($duplicate && $duplicate['id'] !== $id) {
                throw new HTTPException(
                    lang('Service.alreadyExists', [$data['name']]),
                    Response::HTTP_CONFLICT
                );
            }
        }

        return $this->repo->update($id, $data);
    }

    /**
     * Elimina un servicio usando soft delete.
     *
     * @param int $id
     * @return bool
     * @throws HTTPException
     */
    public function delete(int $id)
    {
        $service = $this->repo->getById($id);
        if (!$service) {
            throw new HTTPException(
                lang('Service.notFound'),
                Response::HTTP_NOT_FOUND
            );
        }
        return $this->repo->softDelete($id);
    }
}
