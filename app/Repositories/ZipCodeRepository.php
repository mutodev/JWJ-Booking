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
     * Consulta por código
     *
     * @param string $code
     * @return User
     */
    public function getByCityAndCode($cityId, $code): ?Zipcode
    {
        return $this->zipCodeModel
            ->where('zipcode', $code)
            ->where('city_id', $cityId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Consulta por Metropolitan Area y código
     *
     * @param string $metropolitanAreaId
     * @param string $code
     * @return Zipcode|null
     */
    public function getByMetropolitanAreaAndCode($metropolitanAreaId, $code): ?Zipcode
    {
        return $this->zipCodeModel
            ->select('zipcodes.*')
            ->join('cities', 'cities.id = zipcodes.city_id')
            ->join('counties', 'counties.id = cities.county_id')
            ->where('zipcodes.zipcode', $code)
            ->where('counties.metropolitan_area_id', $metropolitanAreaId)
            ->where('zipcodes.is_active', true)
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
        return $this->zipCodeModel
            ->orderBy('zipcode')
            ->where('is_active', true)
            ->where('city_id', $cityId)
            ->findAll();
    }

    /**
     * Obtener toda la data con ciudad
     *
     * @return array
     */
    public function getAllAndCity()
    {
        return $this->zipCodeModel
            ->select('
                zipcodes.id,
                zipcodes.zipcode,
                zipcodes.is_active,
                zipcodes.city_id,
                cities.name AS city_name
            ')
            ->join('cities', 'cities.id = zipcodes.city_id')
            ->orderBy('cities.name')
            ->orderBy('zipcodes.zipcode')
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
