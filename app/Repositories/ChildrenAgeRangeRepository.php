<?php

namespace App\Repositories;

use App\Models\ChildrenAgeRangeModel;
use App\Entities\ChildrenAgeRange;

class ChildrenAgeRangeRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = new ChildrenAgeRangeModel();
    }

    /**
     * Obtener todos los rangos de edad
     */
    public function findAll(bool $onlyActive = true): array
    {
        if ($onlyActive) {
            return $this->model->where('is_active', 1)->findAll();
        }
        return $this->model->findAll();
    }

    /**
     * Buscar por ID
     */
    public function findById(string $id): ?ChildrenAgeRange
    {
        return $this->model->find($id);
    }

    /**
     * Obtener rangos por service_price_id
     */
    public function findByServicePriceId(string $servicePriceId, bool $onlyActive = true): array
    {
        return $this->model->where('service_price_id', $servicePriceId)->where('is_active', $onlyActive)->findAll();
    }

    /**
     * Buscar rangos que contengan una edad específica
     */
    public function findByAge(int $age, bool $onlyActive = true): array
    {
        $builder = $this->model->where('min_age <=', $age)
            ->where('max_age >=', $age);

        if ($onlyActive) {
            $builder->where('is_active', 1);
        }

        return $builder->findAll();
    }

    /**
     * Crear nuevo rango de edad
     */
    public function create(array $data): ?ChildrenAgeRange
    {
        return $this->model->insert($data) ? $this->findById($data['id']) : null;
    }

    /**
     * Actualizar rango de edad
     */
    public function update(string $id, array $data): bool
    {
        return $this->model->update($id, $data);
    }

    /**
     * Eliminar rango de edad (soft delete si está configurado)
     */
    public function delete(string $id): bool
    {
        return $this->model->delete($id);
    }

    /**
     * Desactivar todos los rangos de un service_price_id
     */
    public function deactivateByServicePriceId(string $servicePriceId): bool
    {
        return $this->model->deactivateAllForServicePrice($servicePriceId);
    }

    /**
     * Activar rango de edad
     */
    public function activate(string $id): bool
    {
        return $this->update($id, ['is_active' => 1]);
    }

    /**
     * Desactivar rango de edad
     */
    public function deactivate(string $id): bool
    {
        return $this->update($id, ['is_active' => 0]);
    }

    /**
     * Verificar si existe un rango con los mismos parámetros
     */
    public function exists(array $criteria, ?string $excludeId = null): bool
    {
        $builder = $this->model;

        foreach ($criteria as $field => $value) {
            $builder->where($field, $value);
        }

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Obtener el modelo interno (para casos especiales)
     */
    public function getModel(): ChildrenAgeRangeModel
    {
        return $this->model;
    }
}
