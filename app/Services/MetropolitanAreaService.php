<?php

namespace App\Services;

use App\Repositories\MetropolitanAreaRepository;
use App\Entities\MetropolitanArea;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;

class MetropolitanAreaService
{
    protected $repository;

    public function __construct()
    {
        $this->repository = new MetropolitanAreaRepository();
    }

    /**
     * Obtener área metropolitana por ID
     */
    public function getById(string $id)
    {
        $area = $this->repository->getById($id);

        if (!$area) {
            throw new HTTPException(lang('MetropolitanArea.notFound'), Response::HTTP_NOT_FOUND);
        }

        return $area;
    }

    /**
     * Obtener todas las áreas metropolitanas
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Obtener todas las áreas metropolitanas activas
     */
    public function getAllActive(): array
    {
        return $this->repository->getAllActive();
    }

    /**
     * Crear nueva área metropolitana
     */
    public function create(array $data)
    {
        $id = $this->repository->create($data);

        if (!$id) {
            throw new HTTPException(lang('MetropolitanArea.createFailed'), Response::HTTP_BAD_REQUEST);
        }

        return $this->repository->getById($id);
    }

    /**
     * Actualizar área metropolitana
     */
    public function update(string $id, array $data): bool
    {
        $updated = $this->repository->update($id, $data);

        if (!$updated) {
            throw new HTTPException(lang('MetropolitanArea.updateFailed'), Response::HTTP_BAD_REQUEST);
        }

        return true;
    }

    /**
     * Eliminar área metropolitana (Soft Delete)
     */
    public function delete(string $id): bool
    {
        $deleted = $this->repository->delete($id);

        if (!$deleted) {
            throw new HTTPException(lang('MetropolitanArea.deleteFailed'), Response::HTTP_BAD_REQUEST);
        }

        return true;
    }
}
