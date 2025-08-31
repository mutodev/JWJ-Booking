<?php

namespace App\Services;

use App\Repositories\CityRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;

class CityService
{
    protected $repository;

    public function __construct()
    {
        $this->repository = new CityRepository();
    }

    /**
     * Obtener ciudad por ID
     */
    public function getById(string $id)
    {
        $city = $this->repository->getById($id);

        if (!$city) {
            throw new HTTPException(lang('City.notFound'), Response::HTTP_NOT_FOUND);
        }

        return $city;
    }

    /**
     * Obtener todas las ciudades
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Obtener ciudades por condado
     */
    public function getByCounty(string $countyId): array
    {
        return $this->repository->getByCounty($countyId);
    }

    /**
     * Crear nueva ciudad
     */
    public function create(array $data): string
    {
        $id = $this->repository->create($data);

        if (!$id) {
            throw new HTTPException(lang('City.createFailed'), Response::HTTP_BAD_REQUEST);
        }

        return $id;
    }

    /**
     * Actualizar ciudad
     */
    public function update(string $id, array $data): bool
    {
        $updated = $this->repository->update($id, $data);

        if (!$updated) {
            throw new HTTPException(lang('City.updateFailed'), Response::HTTP_BAD_REQUEST);
        }

        return true;
    }

    /**
     * Eliminar ciudad (Soft Delete)
     */
    public function delete(string $id): bool
    {
        $deleted = $this->repository->delete($id);

        if (!$deleted) {
            throw new HTTPException(lang('City.deleteFailed'), Response::HTTP_BAD_REQUEST);
        }

        return true;
    }
}
