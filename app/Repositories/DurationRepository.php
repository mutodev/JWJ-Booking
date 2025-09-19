<?php

namespace App\Repositories;

use App\Models\DurationModel;
use App\Entities\Duration;

class DurationRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = new DurationModel();
    }

    /**
     * Obtener todas las duraciones
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
    public function findById(string $id): ?Duration
    {
        return $this->model->find($id);
    }

    /**
     * Obtener duraciones por service_price_id
     */
    public function findByServicePriceId(string $servicePriceId, bool $onlyActive = true): array
    {
        return $this->model->where('service_price_id', $servicePriceId)->where('is_active', $onlyActive)->findAll();
    }

    /**
     * Obtener duraciones por cantidad de minutos
     */
    public function findByMinutes(int $minutes, bool $onlyActive = true): array
    {
        $builder = $this->model->where('minutes', $minutes);

        if ($onlyActive) {
            $builder->where('is_active', 1);
        }

        return $builder->findAll();
    }

    /**
     * Crear nueva duración
     */
    public function create(array $data): ?Duration
    {
        try {
            $result = $this->model->insert($data);
            return $result ? $this->findById($data['id']) : null;
        } catch (\Exception $e) {
            log_message('error', 'Error creating duration: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar duración
     */
    public function update(string $id, array $data): bool
    {
        try {
            return $this->model->update($id, $data);
        } catch (\Exception $e) {
            log_message('error', 'Error updating duration: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar duración
     */
    public function delete(string $id): bool
    {
        try {
            return $this->model->delete($id);
        } catch (\Exception $e) {
            log_message('error', 'Error deleting duration: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Activar duración
     */
    public function activate(string $id): bool
    {
        return $this->update($id, ['is_active' => 1]);
    }

    /**
     * Desactivar duración
     */
    public function deactivate(string $id): bool
    {
        return $this->update($id, ['is_active' => 0]);
    }

    /**
     * Desactivar todas las duraciones de un service_price_id
     */
    public function deactivateByServicePriceId(string $servicePriceId): bool
    {
        try {
            return $this->model->where('service_price_id', $servicePriceId)
                              ->set(['is_active' => 0])
                              ->update();
        } catch (\Exception $e) {
            log_message('error', 'Error deactivating durations: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si existe una duración con los mismos parámetros
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

}
