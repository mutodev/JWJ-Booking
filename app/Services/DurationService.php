<?php

namespace App\Services;

use App\Repositories\DurationRepository;
use App\Entities\Duration;
use Ramsey\Uuid\Uuid;

class DurationService
{
    protected $repository;

    public function __construct()
    {
        $this->repository = new DurationRepository();
    }

    /**
     * Obtener todas las duraciones
     */
    public function getAllDurations(bool $onlyActive = true): array
    {
        return $this->repository->findAll($onlyActive);
    }

    /**
     * Obtener duración por ID
     */
    public function getDurationById(string $id): ?Duration
    {
        return $this->repository->findById($id);
    }

    /**
     * Obtener duraciones por service_price_id
     */
    public function getDurationsByServicePrice(string $servicePriceId, bool $onlyActive = true): array
    {
        $durations = $this->repository->findByServicePriceId($servicePriceId, $onlyActive);

        // Formatear para el frontend
        $formatted = [];
        foreach ($durations as $duration) {
            $hours = $duration->minutes / 60;
            $formatted[] = [
                'id' => $duration->id,
                'service_price_id' => $duration->service_price_id,
                'minutes' => $duration->minutes,
                'hours' => $hours,
                'label' => $this->formatDurationLabel($duration->minutes),
                'is_active' => $duration->is_active
            ];
        }

        return $formatted;
    }

    /**
     * Obtener duraciones por cantidad de minutos
     */
    public function getDurationsByMinutes(int $minutes, bool $onlyActive = true): array
    {
        return $this->repository->findByMinutes($minutes, $onlyActive);
    }

    /**
     * Crear nueva duración
     */
    public function createDuration(array $data): array
    {
        // Validar datos requeridos
        if (!isset($data['service_price_id']) || !isset($data['minutes'])) {
            return [
                'success' => false,
                'message' => 'Datos incompletos. Se requieren service_price_id y minutes.',
                'data' => null
            ];
        }

        // Validar que minutes sea positivo
        if ($data['minutes'] <= 0) {
            return [
                'success' => false,
                'message' => 'Los minutos deben ser mayor que cero.',
                'data' => null
            ];
        }

        // Verificar si ya existe una duración con los mismos parámetros
        $criteria = [
            'service_price_id' => $data['service_price_id'],
            'minutes' => $data['minutes']
        ];

        if ($this->repository->exists($criteria)) {
            return [
                'success' => false,
                'message' => 'Ya existe una duración con los mismos parámetros.',
                'data' => null
            ];
        }

        // Generar UUID si no existe
        if (!isset($data['id'])) {
            $data['id'] = Uuid::uuid4()->toString();
        }

        // Establecer como activo por defecto
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        try {
            $duration = $this->repository->create($data);

            if ($duration) {
                return [
                    'success' => true,
                    'message' => 'Duración creada exitosamente.',
                    'data' => $duration
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al crear la duración.',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Actualizar duración
     */
    public function updateDuration(string $id, array $data): array
    {
        // Verificar que existe
        $duration = $this->repository->findById($id);
        if (!$duration) {
            return [
                'success' => false,
                'message' => 'Duración no encontrada.',
                'data' => null
            ];
        }

        // Validar minutes si se proporciona
        if (isset($data['minutes']) && $data['minutes'] <= 0) {
            return [
                'success' => false,
                'message' => 'Los minutos deben ser mayor que cero.',
                'data' => null
            ];
        }

        // Verificar duplicados si se cambian datos críticos
        if (isset($data['service_price_id']) || isset($data['minutes'])) {
            $criteria = [
                'service_price_id' => $data['service_price_id'] ?? $duration->service_price_id,
                'minutes' => $data['minutes'] ?? $duration->minutes
            ];

            if ($this->repository->exists($criteria, $id)) {
                return [
                    'success' => false,
                    'message' => 'Ya existe otra duración con los mismos parámetros.',
                    'data' => null
                ];
            }
        }

        try {
            $result = $this->repository->update($id, $data);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Duración actualizada exitosamente.',
                    'data' => $this->repository->findById($id)
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al actualizar la duración.',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Eliminar duración
     */
    public function deleteDuration(string $id): array
    {
        // Verificar que existe
        $duration = $this->repository->findById($id);
        if (!$duration) {
            return [
                'success' => false,
                'message' => 'Duración no encontrada.',
                'data' => null
            ];
        }

        try {
            $result = $this->repository->delete($id);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Duración eliminada exitosamente.',
                    'data' => true
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al eliminar la duración.',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Activar duración
     */
    public function activateDuration(string $id): array
    {
        // Verificar que existe
        $duration = $this->repository->findById($id);
        if (!$duration) {
            return [
                'success' => false,
                'message' => 'Duración no encontrada.',
                'data' => null
            ];
        }

        try {
            $result = $this->repository->activate($id);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Duración activada exitosamente.',
                    'data' => $this->repository->findById($id)
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al activar la duración.',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Desactivar duración
     */
    public function deactivateDuration(string $id): array
    {
        // Verificar que existe
        $duration = $this->repository->findById($id);
        if (!$duration) {
            return [
                'success' => false,
                'message' => 'Duración no encontrada.',
                'data' => null
            ];
        }

        try {
            $result = $this->repository->deactivate($id);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Duración desactivada exitosamente.',
                    'data' => $this->repository->findById($id)
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al desactivar la duración.',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Desactivar todas las duraciones de un service_price_id
     */
    public function deactivateAllForServicePrice(string $servicePriceId): array
    {
        try {
            $result = $this->repository->deactivateByServicePriceId($servicePriceId);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Duraciones desactivadas exitosamente.',
                    'data' => true
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al desactivar las duraciones.',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Formatear etiqueta de duración
     */
    private function formatDurationLabel(int $minutes): string
    {
        $hours = $minutes / 60;

        if ($hours == 1) {
            return '1 hour';
        } elseif ($hours == floor($hours)) {
            return floor($hours) . ' hours';
        } else {
            return number_format($hours, 1) . ' hours';
        }
    }
}