<?php

namespace App\Services;

use App\Repositories\ZipCodeRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;

class ZipCodeService
{
    protected $repository;

    public function __construct()
    {
        $this->repository = new ZipCodeRepository();
    }

    /**
     * Obtener código postal por ID
     */
    public function getById(string $id)
    {
        $zipcode = $this->repository->getById($id);

        if (!$zipcode) {
            throw new HTTPException(lang('Zipcode.notFound'), Response::HTTP_NOT_FOUND);
        }

        return $zipcode;
    }

    /**
     * Obtener código postal por código
     */
    public function getByCityAndCode($cityId, $code)
    {
        $zipcode = $this->repository->getByCityAndCode($cityId, $code);

        if (!$zipcode) {
            throw new HTTPException(lang('Zipcode.notFound'), Response::HTTP_NOT_FOUND);
        }

        return $zipcode;
    }

    /**
     * Obtener todos los códigos postales
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Obtener todos los códigos postales
     */
    public function getAllAndCity(): array
    {
        return $this->repository->getAllAndCity();
    }

    /**
     * Obtener códigos postales por ciudad
     */
    public function getByCity(string $cityId): array
    {
        return $this->repository->getByCity($cityId);
    }

    /**
     * Crear nuevo código postal
     */
    public function create(array $data): string
    {
        $id = $this->repository->create($data);

        if (!$id) {
            throw new HTTPException(lang('Zipcode.createFailed'), Response::HTTP_BAD_REQUEST);
        }

        return $id;
    }

    /**
     * Actualizar código postal
     */
    public function update(string $id, array $data): bool
    {
        $updated = $this->repository->update($id, $data);

        if (!$updated) {
            throw new HTTPException(lang('Zipcode.updateFailed'), Response::HTTP_BAD_REQUEST);
        }

        return true;
    }

    /**
     * Eliminar código postal (Soft Delete)
     */
    public function delete(string $id): bool
    {
        $deleted = $this->repository->delete($id);

        if (!$deleted) {
            throw new HTTPException(lang('Zipcode.deleteFailed'), Response::HTTP_BAD_REQUEST);
        }

        return true;
    }
}
