<?php

namespace App\Services;

use App\Repositories\ChildrenAgeRangeRepository;
use App\Entities\ChildrenAgeRange;
use Ramsey\Uuid\Uuid;

class ChildrenAgeRangeService
{
    protected $repository;

    public function __construct()
    {
        $this->repository = new ChildrenAgeRangeRepository();
    }

    /**
     * Obtener todos los rangos de edad
     */
    public function getAllRanges(bool $onlyActive = true): array
    {
        return $this->repository->findAll($onlyActive);
    }

    /**
     * Obtener rango por ID
     */
    public function getRangeById(string $id): ?ChildrenAgeRange
    {
        return $this->repository->findById($id);
    }

    /**
     * Obtener rangos por service_price_id
     */
    public function getRangesByServicePrice(string $servicePriceId, bool $onlyActive = true): array
    {
        return $this->repository->findByServicePriceId($servicePriceId, $onlyActive);
    }

    /**
     * Obtener rangos que contengan una edad específica
     */
    public function getRangesByAge(int $age, bool $onlyActive = true): array
    {
        return $this->repository->findByAge($age, $onlyActive);
    }

    /**
     * Crear nuevo rango de edad
     */
    public function createRange(array $data): array
    {
        // Validar datos requeridos
        if (!isset($data['service_price_id']) || !isset($data['min_age']) || !isset($data['max_age'])) {
            return [
                'success' => false,
                'message' => 'Datos incompletos. Se requieren service_price_id, min_age y max_age.',
                'data' => null
            ];
        }

        // Validar que min_age <= max_age
        if ($data['min_age'] > $data['max_age']) {
            return [
                'success' => false,
                'message' => 'La edad mínima no puede ser mayor que la edad máxima.',
                'data' => null
            ];
        }

        // Verificar si ya existe un rango con los mismos parámetros
        $criteria = [
            'service_price_id' => $data['service_price_id'],
            'min_age' => $data['min_age'],
            'max_age' => $data['max_age']
        ];

        if ($this->repository->exists($criteria)) {
            return [
                'success' => false,
                'message' => 'Ya existe un rango de edad con estos parámetros.',
                'data' => null
            ];
        }

        // Preparar datos para creación
        $rangeData = [
            'id' => Uuid::uuid4()->toString(),
            'service_price_id' => $data['service_price_id'],
            'min_age' => (int) $data['min_age'],
            'max_age' => (int) $data['max_age'],
            'is_active' => $data['is_active'] ?? true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Crear el rango
        $range = $this->repository->create($rangeData);

        if (!$range) {
            return [
                'success' => false,
                'message' => 'Error al crear el rango de edad.',
                'data' => null
            ];
        }

        return [
            'success' => true,
            'message' => 'Rango de edad creado exitosamente.',
            'data' => $range
        ];
    }

    /**
     * Actualizar rango de edad
     */
    public function updateRange(string $id, array $data): array
    {
        // Verificar si el rango existe
        $existingRange = $this->repository->findById($id);
        if (!$existingRange) {
            return [
                'success' => false,
                'message' => 'Rango de edad no encontrado.',
                'data' => null
            ];
        }

        // Validar que min_age <= max_age si se proporcionan
        if (isset($data['min_age']) && isset($data['max_age']) && $data['min_age'] > $data['max_age']) {
            return [
                'success' => false,
                'message' => 'La edad mínima no puede ser mayor que la edad máxima.',
                'data' => null
            ];
        }

        // Verificar si ya existe otro rango con los mismos parámetros
        if (isset($data['min_age']) || isset($data['max_age']) || isset($data['service_price_id'])) {
            $criteria = [
                'service_price_id' => $data['service_price_id'] ?? $existingRange->service_price_id,
                'min_age' => $data['min_age'] ?? $existingRange->min_age,
                'max_age' => $data['max_age'] ?? $existingRange->max_age
            ];

            if ($this->repository->exists($criteria, $id)) {
                return [
                    'success' => false,
                    'message' => 'Ya existe otro rango de edad con estos parámetros.',
                    'data' => null
                ];
            }
        }

        // Preparar datos para actualización
        $updateData = [];
        if (isset($data['min_age'])) $updateData['min_age'] = (int) $data['min_age'];
        if (isset($data['max_age'])) $updateData['max_age'] = (int) $data['max_age'];
        if (isset($data['is_active'])) $updateData['is_active'] = (bool) $data['is_active'];
        if (isset($data['service_price_id'])) $updateData['service_price_id'] = $data['service_price_id'];

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        // Actualizar el rango
        $success = $this->repository->update($id, $updateData);

        if (!$success) {
            return [
                'success' => false,
                'message' => 'Error al actualizar el rango de edad.',
                'data' => null
            ];
        }

        return [
            'success' => true,
            'message' => 'Rango de edad actualizado exitosamente.',
            'data' => $this->repository->findById($id)
        ];
    }

    /**
     * Eliminar rango de edad
     */
    public function deleteRange(string $id): array
    {
        // Verificar si el rango existe
        $range = $this->repository->findById($id);
        if (!$range) {
            return [
                'success' => false,
                'message' => 'Rango de edad no encontrado.',
                'data' => null
            ];
        }

        // Eliminar el rango
        $success = $this->repository->delete($id);

        if (!$success) {
            return [
                'success' => false,
                'message' => 'Error al eliminar el rango de edad.',
                'data' => null
            ];
        }

        return [
            'success' => true,
            'message' => 'Rango de edad eliminado exitosamente.',
            'data' => null
        ];
    }

    /**
     * Activar rango de edad
     */
    public function activateRange(string $id): array
    {
        return $this->updateRange($id, ['is_active' => true]);
    }

    /**
     * Desactivar rango de edad
     */
    public function deactivateRange(string $id): array
    {
        return $this->updateRange($id, ['is_active' => false]);
    }

    /**
     * Desactivar todos los rangos de un service_price_id
     */
    public function deactivateAllForServicePrice(string $servicePriceId): array
    {
        $success = $this->repository->deactivateByServicePriceId($servicePriceId);

        return [
            'success' => $success,
            'message' => $success ?
                'Todos los rangos de edad desactivados exitosamente.' :
                'Error al desactivar los rangos de edad.',
            'data' => null
        ];
    }

    /**
     * Obtener el repository (para casos especiales)
     */
    public function getRepository(): ChildrenAgeRangeRepository
    {
        return $this->repository;
    }
}
