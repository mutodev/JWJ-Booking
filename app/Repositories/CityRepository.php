<?php

namespace App\Repositories;

use App\Entities\City;
use App\Models\CityModel;

class CityRepository
{

    protected $cityModel;

    public function __construct()
    {
        $this->cityModel = new CityModel();
    }

    /**
     * Consulta por id
     * 
     * @param string $id
     * @return User
     */
    public function getById(string $id): ?City
    {
        return $this->cityModel
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
        return $this->cityModel->findAll();
    }

    /**
     * Obtiene todo por condado
     */
    public function getByCounty(string $countyId): array
    {
        return $this->cityModel->where('county_id', $countyId)
            ->findAll();
    }

    /**
     * Creación
     * @param array $data
     * @return string|false El ID del usuario creado o false en caso de error
     */
    public function create(array $data)
    {
        return $this->cityModel->insert($data, true);
    }

    /**
     * Eliminar (Soft Delete)
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        return $this->cityModel->delete($id);
    }

    /**
     * Actualización
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function update(string $id, array $data): bool
    {
        return $this->cityModel->update($id, $data);
    }
}
