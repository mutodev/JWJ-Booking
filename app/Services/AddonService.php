<?php

namespace App\Services;

use App\Repositories\AddonRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;

class AddonService
{
    protected AddonRepository $repository;

    public function __construct()
    {
        $this->repository = new AddonRepository();
    }

    /**
     * Obtener addon por ID
     */
    public function getById(string $id)
    {
        $addon = $this->repository->getById($id);

        if (!$addon) {
            throw new HTTPException(lang('Addon.notFound'), Response::HTTP_NOT_FOUND);
        }

        return $addon;
    }

    /**
     * Obtener todos los addons
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Obtener addons activos
     */
    public function getAllActive(): array
    {
        return $this->repository->getAllActive();
    }

    /**
     * Buscar addons por nombre (like)
     */
    public function search(string $name): array
    {
        return $this->repository->searchByName($name);
    }

    /**
     * Crear un nuevo addon con validación de duplicado
     */
    public function create(array $data)
    {
        $exists = $this->repository->getByName($data['name']);
        if ($exists) {
            throw new HTTPException(lang('Addon.duplicateName'), Response::HTTP_BAD_REQUEST);
        }

        $data = $this->repository->create($data);

        if (!$data) {
            throw new HTTPException(lang('Addon.createFailed'), Response::HTTP_BAD_REQUEST);
        }

        return $data;
    }

    /**
     * Actualizar un addon
     */
    public function update(string $id, array $data): bool
    {
        $updated = $this->repository->update($id, $data);

        if (!$updated) {
            throw new HTTPException(lang('Addon.updateFailed'), Response::HTTP_BAD_REQUEST);
        }

        return true;
    }

    /**
     * Eliminar un addon
     */
    public function delete(string $id): bool
    {
        $deleted = $this->repository->delete($id);

        if (!$deleted) {
            throw new HTTPException(lang('Addon.deleteFailed'), Response::HTTP_BAD_REQUEST);
        }

        return true;
    }
}
