<?php

namespace App\Repositories;

use App\Entities\Zipcode;
use App\Models\ZipCodeModel;

class ZipCodeRepository
{

    protected $zipCodeModel;

    public function __construct()
    {
        $this->zipCodeModel = new ZipCodeModel();
    }

    /**
     * Consulta por id
     * 
     * @param string $id
     * @return User
     */
    public function getById(string $id): ?Zipcode
    {
        return $this->zipCodeModel
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
        return $this->zipCodeModel->findAll();
    }

    /**
     * Obtiene todo por ciudad
     */
    public function getByCity(string $cityId): array
    {
        return $this->zipCodeModel->where('city_id', $cityId)
            ->findAll();
    }

    /**
     * Creación
     * @param array $data
     * @return string|false El ID del usuario creado o false en caso de error
     */
    public function create(array $data)
    {
        return $this->zipCodeModel->insert($data, true);
    }

    /**
     * Eliminar (Soft Delete)
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        return $this->zipCodeModel->delete($id);
    }

    /**
     * Actualización
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function update(string $id, array $data): bool
    {
        return $this->zipCodeModel->update($id, $data);
    }
}
