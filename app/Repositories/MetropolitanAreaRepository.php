<?php

namespace App\Repositories;

use App\Entities\MetropolitanArea;
use App\Models\MetropolitanAreaModel;

class MetropolitanAreaRepository
{

    protected $metropolitanAreaModel;

    public function __construct()
    {
        $this->metropolitanAreaModel = new MetropolitanAreaModel();
    }

    /**
     * Consulta por id
     * 
     * @param string $id
     * @return User
     */
    public function getById(string $id): ?MetropolitanArea
    {
        return $this->metropolitanAreaModel
            ->where('id', $id)
            ->first();
    }

    /**
     * Obtener toda la data
     *
     * @return array
     */
    public function getAll()
    {
        return $this->metropolitanAreaModel->findAll();
    }

    /**
     * Obtener toda la data activos
     *
     * @return array
     */
    public function getAllActive()
    {
        return $this->metropolitanAreaModel->where('is_active', true)->findAll();
    }

    /**
     * Creación de area
     * @param array $data
     * @return string|false El ID del usuario creado o false en caso de error
     */
    public function create(array $data)
    {
        return $this->metropolitanAreaModel->insert($data, true);
    }

    /**
     * Eliminar (Soft Delete)
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        return $this->metropolitanAreaModel->delete($id);
    }

    /**
     * Actualización
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function update(string $id, array $data): bool
    {
        return $this->metropolitanAreaModel->update($id, $data);
    }
}
