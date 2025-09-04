<?php

namespace App\Repositories;

use App\Models\ServiceModel;

class ServiceRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = new ServiceModel();
    }

    /**
     * Obtiene todos los servicios (sin incluir soft-deleted).
     *
     * @return array
     */
    public function getAll()
    {
        return $this->model->findAll();
    }

    /**
     * Obtiene un servicio por ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getById(int $id)
    {
        return $this->model->find($id);
    }

    /**
     * Obtiene un servicio por nombre.
     * Permite incluir registros eliminados suavemente.
     *
     * @param string $name
     * @param bool $withDeleted
     * @return array|null
     */
    public function getByName(string $name, bool $withDeleted = false)
    {
        $builder = $this->model;
        if ($withDeleted) {
            $builder = $builder->withDeleted();
        }
        return $builder->where('name', $name)->first();
    }

    /**
     * Crea un nuevo servicio.
     *
     * @param array $data
     * @return int|string|false Retorna el ID insertado o false si falla
     */
    public function create(array $data)
    {
        return $this->model->insert($data, true); // true → retorna ID
    }

    /**
     * Actualiza un servicio existente por ID.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data)
    {
        return $this->model->update($id, $data);
    }

    /**
     * Realiza soft delete en un servicio (marca deleted_at).
     *
     * @param int $id
     * @return bool
     */
    public function softDelete(int $id)
    {
        return $this->model->delete($id);
    }

    /**
     * Restaura un servicio previamente eliminado (borra deleted_at).
     *
     * @param int $id
     * @return bool
     */
    public function restore(int $id)
    {
        return $this->model->update($id, ['deleted_at' => null]);
    }
}
