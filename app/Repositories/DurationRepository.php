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
    public function findAll(bool $onlyActive = true, bool $withDeleted = false): array
    {
        $builder = $this->model;

        if ($onlyActive) {
            $builder->where('is_active', 1);
        }

        if ($withDeleted) {
            $builder->withDeleted();
        }

        return $builder->findAll();
    }

    /**
     * Buscar por ID
     */
    public function findById(string $id, bool $withDeleted = false): ?Duration
    {
        if ($withDeleted) {
            return $this->model->withDeleted()->find($id);
        }
        return $this->model->find($id);
    }

    /**
     * Obtener duraciones por service_price_id
     */
    public function findByServicePriceId(string $servicePriceId, bool $onlyActive = true, bool $withDeleted = false): array
    {
        return $this->model->getByServicePriceId($servicePriceId, $onlyActive, $withDeleted);
    }

    /**
     * Obtener duraciones por cantidad de minutos
     */
    public function findByMinutes(int $minutes, bool $onlyActive = true, bool $withDeleted = false): array
    {
        return $this->model->getByMinutes($minutes, $onlyActive, $withDeleted);
    }

    /**
     * Crear nueva duración
     */
    public function create(array $data): ?Duration
    {
        try {
            return $this->model->insert($data) ? $this->findById($data['id']) : null;
        } catch (\Exception $e) {
            log_message('error', 'Error creating duration: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar duración
     */
    public function update(string $id, array $data, bool $withDeleted = false): bool
    {
        try {
            if ($withDeleted) {
                $this->model->withDeleted();
            }
            return $this->model->update($id, $data);
        } catch (\Exception $e) {
            log_message('error', 'Error updating duration: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar duración (soft delete)
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
     * Restaurar duración eliminada (soft delete)
     */
    public function restore(string $id): bool
    {
        try {
            return $this->model->withDeleted()->update($id, ['deleted_at' => null]);
        } catch (\Exception $e) {
            log_message('error', 'Error restoring duration: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminación permanente (force delete)
     */
    public function forceDelete(string $id): bool
    {
        try {
            return $this->model->delete($id, true);
        } catch (\Exception $e) {
            log_message('error', 'Error force deleting duration: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Desactivar todas las duraciones de un service_price_id
     */
    public function deactivateByServicePriceId(string $servicePriceId): bool
    {
        try {
            return $this->model->deactivateAllForServicePrice($servicePriceId);
        } catch (\Exception $e) {
            log_message('error', 'Error deactivating durations: ' . $e->getMessage());
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
     * Verificar si existe una duración con los mismos parámetros
     */
    public function exists(array $criteria, ?string $excludeId = null, bool $withDeleted = false): bool
    {
        $builder = $this->model;

        foreach ($criteria as $field => $value) {
            $builder->where($field, $value);
        }

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        if ($withDeleted) {
            $builder->withDeleted();
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Formatear minutos a texto legible
     */
    public function formatMinutes(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0 && $remainingMinutes > 0) {
            return "{$hours}h {$remainingMinutes}min";
        } elseif ($hours > 0) {
            return "{$hours} hora" . ($hours > 1 ? 's' : '');
        } else {
            return "{$minutes} minuto" . ($minutes > 1 ? 's' : '');
        }
    }

    /**
     * Obtener opciones de duración para formularios
     */
    public function getDurationOptions(string $servicePriceId): array
    {
        $durations = $servicePriceId
            ? $this->findByServicePriceId($servicePriceId)
            : $this->findAll();

        $options = [];
        foreach ($durations as $duration) {
            $options[$duration->id] = $this->formatMinutes($duration->minutes);
        }

        return $options;
    }

    /**
     * Obtener el modelo interno (para casos especiales)
     */
    public function getModel(): DurationModel
    {
        return $this->model;
    }
}
