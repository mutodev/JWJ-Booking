<?php

namespace App\Repositories;

use App\Entities\ReservationCustomService;
use App\Models\ReservationCustomServiceModel;

class ReservationCustomServiceRepository
{
    protected ReservationCustomServiceModel $model;

    public function __construct()
    {
        $this->model = new ReservationCustomServiceModel();
    }

    /**
     * Servicios personalizados de una reserva (sin soft-deleted), en el orden en que se agregaron.
     *
     * @return ReservationCustomService[]
     */
    public function getByReservation(string $reservationId): array
    {
        return $this->model
            ->where('reservation_id', $reservationId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    public function getById(string $id): ?ReservationCustomService
    {
        return $this->model->where('id', $id)->first();
    }

    /**
     * Fila activa de un servicio del catálogo dentro de una reserva (para evitar duplicados).
     */
    public function findInReservation(string $reservationId, string $customServiceId): ?ReservationCustomService
    {
        return $this->model
            ->where('reservation_id', $reservationId)
            ->where('custom_service_id', $customServiceId)
            ->first();
    }

    /**
     * @return string|false UUID insertado o false si falla
     */
    public function create(array $data)
    {
        return $this->model->insert($data, true);
    }

    public function update(string $id, array $data): bool
    {
        return $this->model->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->model->delete($id);
    }
}
