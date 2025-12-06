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

    /**
     * Obtener addons más populares con estadísticas
     */
    public function getMostPopular(int $limit = 10): array
    {
        return $this->model
            ->select([
                'addons.name as addon_name',
                'type_addons.name as addon_description',
                'SUM(reservation_addons.quantity) as total_sold',
                'SUM(reservation_addons.quantity * reservation_addons.price_at_time) as total_revenue',
                'COUNT(DISTINCT reservation_addons.reservation_id) as reservations_count',
                'AVG(reservation_addons.price_at_time) as avg_price'
            ])
            ->join('addons', 'addons.id = reservation_addons.addon_id', 'left')
            ->join('type_addons', 'type_addons.id = addons.type_addon_id', 'left')
            ->join('reservations', 'reservations.id = reservation_addons.reservation_id', 'left')
            ->where('reservations.status !=', 'cancelled')
            ->groupBy(['addons.id', 'addons.name', 'type_addons.name'])
            ->orderBy('total_sold', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }
}
