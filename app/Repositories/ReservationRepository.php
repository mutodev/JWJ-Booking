<?php

namespace App\Repositories;

use App\Models\ReservationModel;

class ReservationRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = new ReservationModel();
    }

    /**
     * Obtener todas las reservas
     */
    public function getAll()
    {
        return $this->model->findAll();
    }

    /**
     * Obtener reserva por ID
     */
    public function getById(string $id)
    {
        return $this->model->find($id);
    }

    /**
     * Crear una reserva
     */
    public function create(array $data)
    {
        $this->model->insert($data);
        return $this->getById($this->model->getInsertID());
    }

    /**
     * Actualizar reserva
     */
    public function update(string $id, array $data)
    {
        $this->model->update($id, $data);
        return $this->getById($id);
    }

    /**
     * Eliminar (soft delete)
     */
    public function delete(string $id)
    {
        return $this->model->delete($id);
    }
}
