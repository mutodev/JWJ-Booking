<?php

namespace App\Repositories;

use App\Entities\County;
use App\Models\CountyModel;

class CountyRepository
{

    protected $countyModel;

    public function __construct()
    {
        $this->countyModel = new CountyModel();
    }

    /**
     * Consulta por id
     * 
     * @param string $id
     * @return User
     */
    public function getById(string $id): ?County
    {
        return $this->countyModel
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
        return $this->countyModel->orderBy('name')->findAll();
    }

    /**
     * Obtener toda la data con area metropolitana
     *
     * @return array
     */
    public function getAllAndMetrpolitan()
    {
        return $this->countyModel
            ->select('
                counties.id,
                counties.name,
                counties.is_active,
                counties.metropolitan_area_id,
                metropolitan_areas.name AS metropolitan_area_name
            ')
            ->join('metropolitan_areas', 'metropolitan_areas.id = counties.metropolitan_area_id')
            ->orderBy('counties.name')
            ->orderBy('metropolitan_areas.name')
            ->findAll();
    }
    
    /**
     * Obtener toda la data activos
     *
     * @return array
     */
    public function getAllActive()
    {
        return $this->countyModel->orderBy('name')->where('is_active', true)->findAll();
    }

    /**
     * Obtiene todo por area metropolitana
     */
    public function getByMetropilitan(string $metroplitanId): array
    {
        return $this->countyModel->where('metropolitan_area_id', $metroplitanId)
            ->findAll();
    }

    /**
     * Creación
     * @param array $data
     * @return string|false El ID del usuario creado o false en caso de error
     */
    public function create(array $data)
    {
        return $this->countyModel->insert($data, true);
    }

    /**
     * Eliminar (Soft Delete)
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        return $this->countyModel->delete($id);
    }

    /**
     * Actualización
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function update(string $id, array $data): bool
    {
        return $this->countyModel->update($id, $data);
    }
}
