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
        $servicePrice = $data['price']['amount'] ?? 0;
        $addons = $data['addons'] ?? [];
        $extraChildren = $data['form']['extraChildren'] ?? 0;
        $extraChildFee = $data['price']['extra_child_fee'] ?? 0;
        $bookingDate = isset($data['form']['date']) ? new \DateTime($data['form']['date']) : null;
        $today = new \DateTime();

        // Total de addons
        $addonsTotal = array_reduce($addons, function ($sum, $addon) {
            return $sum + ($addon['base_price'] ?? 0);
        }, 0);

        $extraChildrenTotal = $extraChildren * $extraChildFee;

        $baseTotal = $servicePrice + $addonsTotal + $extraChildrenTotal;

        // Recargo por proximidad de la fecha
        $surchargeAmount = 0;
        if ($bookingDate) {
            $diffDays = (int)$today->diff($bookingDate)->format("%r%a");
            if ($diffDays < 0) $diffDays = 0;

            if ($diffDays < 2) {
                $surchargeAmount = $baseTotal * 0.2;
            } elseif ($diffDays <= 7) {
                $surchargeAmount = $baseTotal * 0.1;
            }
        }

        $grandTotal = $baseTotal + $surchargeAmount;

        // Crear objeto según migración completo
        $reservationData = [
            'customer_id' => $data['customer']['id'] ?? null,
            'service_price_id' => $data['price']['id'] ?? null,
            'zipcode_id' => $data['areas']['zipcode']['id'],
            'event_address' => $data['form']['eventAddress'],
            'event_date' => $bookingDate ? $bookingDate->format('Y-m-d') : null,
            'event_time' => $data['form']['startTime'] ?? null,
            'children_count' => $extraChildren,
            'performers_count' => $data['price']['performers_count'] ?? null,
            'duration_hours' => $data['price']['min_duration_hours'] ?? null,
            'price_type' => $data['price']['price_type'] ?? 'standard',
            'base_price' => $servicePrice,
            'addons_total' => $addonsTotal,
            'expedition_fee' => 0,
            'extra_children_fee' => $data['form']['extraChildren'] ?? 0,
            'total_amount' => $grandTotal,
            'status' => 'new',
            'is_invoiced' => false,
            'is_paid' => false,
            'arrival_parking_instructions' => $data['form']['arrivalParkingInstructions'] ?? "-",
            'entertainment_start_time' => $data['form']['startTime'],
            'birthday_child_name' => $data['form']['birthdayChildName'] ?? null,
            'birthday_child_age' => $data['form']['birthdayChildAge'] ?? null,
            'children_age_range' => $data['form']['childrenAgeRange'] ?? "-",
            'song_requests' => $data['form']['songRequests'] ?? "-",
            'sing_happy_birthday' => $data['form']['singHappyBirthday'] ?? false,
            'customer_notes' => $data['form']['customerNotes'] ?? null,
            'internal_notes' => null
        ];

        // return $reservationData;

        $response = $this->repository->create($reservationData);

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
