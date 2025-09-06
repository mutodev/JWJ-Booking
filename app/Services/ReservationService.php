<?php

namespace App\Services;

use App\Repositories\ReservationRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;

class ReservationService
{
    protected $repository;

    public function __construct()
    {
        $this->repository = new ReservationRepository();
    }

    /**
     * Obtener todas las reservas
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Obtener reserva por ID
     */
    public function getById(string $id)
    {
        $reservation = $this->repository->getById($id);

        if (!$reservation) {
            throw new HTTPException(lang('Reservation.notFound'), Response::HTTP_NOT_FOUND);
        }

        return $reservation;
    }

    /**
     * Crear nueva reserva
     */
    public function create(array $data)
    {
        $response = $this->repository->create($data);

        if (!$response) {
            throw new HTTPException(lang('Reservation.createFailed'), Response::HTTP_BAD_REQUEST);
        }

        return $response;
    }

    /**
     * Actualizar reserva
     */
    public function update(string $id, array $data): bool
    {
        $updated = $this->repository->update($id, $data);

        if (!$updated) {
            throw new HTTPException(lang('Reservation.updateFailed'), Response::HTTP_BAD_REQUEST);
        }

        return true;
    }

    /**
     * Eliminar reserva (Soft Delete)
     */
    public function delete(string $id): bool
    {
        $deleted = $this->repository->delete($id);

        if (!$deleted) {
            throw new HTTPException(lang('Reservation.deleteFailed'), Response::HTTP_BAD_REQUEST);
        }

        return true;
    }
}
