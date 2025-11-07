<?php

namespace App\Repositories;

use App\Models\PromoCodeModel;

class PromoCodeRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = new PromoCodeModel();
    }

    /**
     * Buscar un código promocional por su código
     *
     * @param string $code Código promocional
     * @return array|null Datos del código promocional o null
     */
    public function findByCode(string $code): ?array
    {
        $result = $this->model
            ->where('code', strtoupper($code))
            ->first();

        // Convertir a array si es necesario
        if ($result && is_object($result)) {
            $result = (array) $result;
        }

        return $result;
    }

    /**
     * Obtener un código promocional por ID
     *
     * @param string $id ID del código promocional
     * @return array|null
     */
    public function getById(string $id): ?array
    {
        return $this->model->where('id', $id)->first();
    }

    /**
     * Incrementar el contador de usos de un código promocional
     *
     * @param string $promoCodeId ID del código promocional
     * @return bool Éxito de la operación
     */
    public function incrementUsage(string $promoCodeId): bool
    {
        $promoCode = $this->getById($promoCodeId);

        if (!$promoCode) {
            return false;
        }

        $newUsageCount = ($promoCode['times_used'] ?? 0) + 1;

        return $this->model->update($promoCodeId, [
            'times_used' => $newUsageCount
        ]);
    }

    /**
     * Obtener todos los códigos promocionales activos
     *
     * @return array
     */
    public function getAllActive(): array
    {
        return $this->model
            ->where('is_active', true)
            ->where('deleted_at', null)
            ->findAll();
    }

    /**
     * Obtener todos los códigos promocionales (Admin)
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->model
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Crear un nuevo código promocional
     *
     * @param array $data
     * @return bool|string
     */
    public function create(array $data)
    {
        return $this->model->insert($data);
    }

    /**
     * Actualizar un código promocional
     *
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function update(string $id, array $data): bool
    {
        return $this->model->update($id, $data);
    }

    /**
     * Eliminar un código promocional (soft delete)
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        return $this->model->delete($id);
    }
}
