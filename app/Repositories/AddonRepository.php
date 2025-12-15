<?php

namespace App\Repositories;

use App\Models\AddonModel;
use App\Entities\Addon;

/**
 * Repository para manejar la capa de acceso a datos de Addons
 */
class AddonRepository
{
    protected AddonModel $model;

    public function __construct()
    {
        $this->model = new AddonModel();
    }

    /**
     * Obtener todos los addons con el nombre e imagen del type_addon
     */
    public function getAll(): array
    {
        return $this->model
            ->select('addons.*, type_addons.name as type_addon_name, type_addons.image as type_addon_image')
            ->join('type_addons', 'type_addons.id = addons.type_addon_id', 'left')
            ->findAll();
    }

    /**
     * Obtener solo addons activos
     */
    public function getAllActive(): array
    {
        return $this->model->where('is_active', true)->findAll();
    }

    /**
     * Buscar addons por nombre (LIKE)
     */
    public function searchByName(string $name): array
    {
        return $this->model
            ->like('name', $name)
            ->findAll();
    }

    /**
     * Obtener un addon por ID
     */
    public function getById(string $id): ?Addon
    {
        return $this->model->find($id);
    }

    /**
     * Obtener un addon por nombre exacto
     */
    public function getByName(string $name): ?Addon
    {
        return $this->model->where('name', $name)->first();
    }

    /**
     * Crear un nuevo addon
     */
    public function create(array $data): ?Addon
    {
        if (!$this->model->insert($data)) {
            return null;
        }
        return $this->getById($this->model->getInsertID());
    }

    /**
     * Actualizar un addon
     */
    public function update(string $id, array $data): bool
    {
        return $this->model->update($id, $data);
    }

    /**
     * Eliminar un addon
     */
    public function delete(string $id): bool
    {
        return $this->model->delete($id);
    }
}
