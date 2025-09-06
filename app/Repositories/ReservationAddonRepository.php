<?php

namespace App\Repositories;

use App\Models\ReservationAddonModel;

class ReservationAddonRepository
{
    protected ReservationAddonModel $model;

    public function __construct()
    {
        $this->model = new ReservationAddonModel();
    }

    /**
     * Obtener todos los registros (sin incluir soft-deleted).
     */
    public function getAll(): array
    {
        return $this->model->findAll();
    }

    /**
     * Obtener un registro por ID (UUID).
     */
    public function getById(string $id)
    {
        return $this->model->find($id);
    }

    /**
     * Obtener todos los addons de una reservación.
     */
    public function getByReservation(string $reservationId): array
    {
        return $this->model
            ->where('reservation_id', $reservationId)
            ->findAll();
    }

    /**
     * Crear un registro. Con UUID generado en beforeInsert.
     * Retorna el ID insertado (UUID) o false si falla.
     */
    public function create(array $data)
    {
        // insert($data, true) intenta retornar el ID.
        return $this->model->insert($data, true);
    }

    /**
     * Actualizar un registro por ID.
     */
    public function update(string $id, array $data): bool
    {
        return $this->model->update($id, $data);
    }

    /**
     * Eliminar (soft delete) un registro por ID.
     */
    public function delete(string $id): bool
    {
        return $this->model->delete($id);
    }
}
