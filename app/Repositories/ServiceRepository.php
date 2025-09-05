<?php

namespace App\Repositories;

use App\Entities\Service;
use App\Models\ServiceModel;

class ServiceRepository
{
    protected ServiceModel $model;
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    public function __construct()
    {
        $this->model = new ServiceModel();
    }

    /**
     * Obtiene todos los servicios (sin incluir soft-deleted).
     *
     * @return Service[]
     */
    public function getAll(): array
    {
        return $this->model->findAll();
    }

    /**
     * Obtiene un servicio por UUID.
     *
     * @param string $id
     * @return Service|null
     */
    public function getById(string $id): ?Service
    {
        return $this->model->where('id', $id)->first();
    }

    /**
     * Obtiene un servicio por nombre.
     * Permite incluir registros soft-deleted.
     *
     * @param string $name
     * @param bool $withDeleted
     * @return Service|null
     */
    public function getByName(string $name, bool $withDeleted = false): ?Service
    {
        $builder = $this->model->where('name', $name);

        if ($withDeleted) {
            $builder->withDeleted();
        }

        return $builder->first();
    }

    /**
     * Crea un nuevo servicio.
     *
     * @param array $data
     * @return string|false Retorna el UUID insertado o false si falla
     */
    public function create(array $data)
    {
        return $this->model->insert($data, true); // retorna UUID si está configurado
    }

    /**
     * Actualiza un servicio existente por UUID.
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
     * Realiza soft delete en un servicio (marca deleted_at).
     *
     * @param string $id
     * @return bool
     */
    public function softDelete(string $id): bool
    {
        return $this->model->where('id', $id)->delete();
    }

    /**
     * Restaura un servicio previamente eliminado.
     * Opcionalmente actualiza datos.
     *
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function restore(string $id): bool
    {
        // Usar query builder directo para evitar filtros del modelo
        $db = \Config\Database::connect();
        return $db->table('services') // Cambia 'services' por el nombre real de tu tabla
            ->where('id', $id)
            ->update(['deleted_at' => null]);
    }
}
