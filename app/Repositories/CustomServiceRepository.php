<?php

namespace App\Repositories;

use App\Entities\CustomService;
use App\Models\CustomServiceModel;

class CustomServiceRepository
{
    protected CustomServiceModel $model;

    public function __construct()
    {
        $this->model = new CustomServiceModel();
    }

    /**
     * Obtiene todos los servicios personalizados (sin incluir soft-deleted).
     *
     * @return CustomService[]
     */
    public function getAll(): array
    {
        return $this->model->orderBy('name')->findAll();
    }

    /**
     * Obtiene los servicios personalizados activos.
     *
     * @return CustomService[]
     */
    public function getAllActive(): array
    {
        return $this->model->orderBy('name')->where('is_active', true)->findAll();
    }

    /**
     * Obtiene un servicio personalizado por UUID.
     */
    public function getById(string $id): ?CustomService
    {
        return $this->model->where('id', $id)->first();
    }

    /**
     * Crea un servicio personalizado.
     *
     * @return string|false UUID insertado o false si falla
     */
    public function create(array $data)
    {
        return $this->model->insert($data, true);
    }

    /**
     * Actualiza un servicio personalizado por UUID.
     */
    public function update(string $id, array $data): bool
    {
        return $this->model->update($id, $data);
    }

    /**
     * Soft delete (marca deleted_at).
     */
    public function softDelete(string $id): bool
    {
        return $this->model->where('id', $id)->delete();
    }
}
