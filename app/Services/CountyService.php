<?php

namespace App\Services;

use App\Repositories\CountyRepository;
use App\Entities\County;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;

class CountyService
{
    protected $countyRepository;

    public function __construct()
    {
        $this->countyRepository = new CountyRepository();
    }

    /**
     * Obtener un condado por su ID
     */
    public function getById(string $id)
    {
        $county = $this->countyRepository->getById($id);

        if (!$county)
            throw new HTTPException(lang('County.notFound'), Response::HTTP_NOT_FOUND);

        return $county;
    }

    /**
     * Obtener todos los condados
     */
    public function getAll(): array
    {
        return $this->countyRepository->getAll();
    }

    /**
     * Obtener todos los condados con areas
     */
    public function getAllAndMetrpolitan(): array
    {
        return $this->countyRepository->getAllAndMetrpolitan();
    }

    /**
     * Obtener condados por área metropolitana
     */
    public function getByMetropolitan(string $metropolitanId): array
    {
        return $this->countyRepository->getByMetropilitan($metropolitanId);
    }

    /**
     * Crear un nuevo condado
     */
    public function create(array $data): string
    {
        $id = $this->countyRepository->create($data);

        if (!$id) {
            throw new HTTPException(
                lang('County.createFailed'),
                Response::HTTP_BAD_REQUEST
            );
        }

        return $id;
    }

    /**
     * Actualizar condado
     */
    public function update(string $id, array $data): bool
    {
        $updated = $this->countyRepository->update($id, $data);

        if (!$updated) {
            throw new HTTPException(
                lang('County.updateFailed'),
                Response::HTTP_BAD_REQUEST
            );
        }

        return true;
    }

    /**
     * Eliminar condado (Soft Delete)
     */
    public function delete(string $id): bool
    {
        $deleted = $this->countyRepository->delete($id);

        if (!$deleted) {
            throw new HTTPException(
                lang('County.deleteFailed'),
                Response::HTTP_BAD_REQUEST
            );
        }

        return true;
    }
}
