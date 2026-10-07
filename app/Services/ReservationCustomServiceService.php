<?php

namespace App\Services;

use App\Repositories\CustomServiceRepository;
use App\Repositories\ReservationCustomServiceRepository;
use App\Repositories\ReservationRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;

/**
 * Servicios personalizados de una reserva existente (modal de edición del admin).
 * Cada alta / cambio de precio / baja dispara el recálculo de totales de la
 * reserva, igual que ReservationAddonService.
 */
class ReservationCustomServiceService
{
    private const UUID_REGEX = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    protected $repository;
    protected $catalogRepository;
    protected $reservationRepository;

    /** @var ReservationService|null */
    protected $reservationService = null;

    public function __construct()
    {
        $this->repository = new ReservationCustomServiceRepository();
        $this->catalogRepository = new CustomServiceRepository();
        $this->reservationRepository = new ReservationRepository();
    }

    protected function reservationService()
    {
        if ($this->reservationService === null) {
            $this->reservationService = new ReservationService();
        }
        return $this->reservationService;
    }

    public function getByReservation(string $reservationId): array
    {
        $this->assertUuid($reservationId, 'reservation_id');
        return $this->repository->getByReservation($reservationId);
    }

    /**
     * Relaciona un servicio del catálogo a la reserva. Si no se envía
     * price_at_time se usa el precio del catálogo.
     *
     * @return array{id:string, totals:?object}
     */
    public function create(array $data, string $userLabel): array
    {
        $reservationId   = (string) ($data['reservation_id'] ?? '');
        $customServiceId = (string) ($data['custom_service_id'] ?? '');
        $this->assertUuid($reservationId, 'reservation_id');
        $this->assertUuid($customServiceId, 'custom_service_id');

        if (!$this->reservationRepository->getById($reservationId)) {
            throw new HTTPException('Reservation not found', Response::HTTP_NOT_FOUND);
        }

        $catalog = $this->catalogRepository->getById($customServiceId);
        if (!$catalog || !$catalog->is_active) {
            throw new HTTPException('Custom service not found or inactive', Response::HTTP_BAD_REQUEST);
        }

        if ($this->repository->findInReservation($reservationId, $customServiceId)) {
            throw new HTTPException('This custom service is already on the reservation', Response::HTTP_CONFLICT);
        }

        $price = array_key_exists('price_at_time', $data) && $data['price_at_time'] !== null && $data['price_at_time'] !== ''
            ? $this->validPrice($data['price_at_time'])
            : round((float) $catalog->price, 2);

        $label = mb_substr($userLabel, 0, 255);
        $id = $this->repository->create([
            'reservation_id'    => $reservationId,
            'custom_service_id' => $customServiceId,
            'name'              => (string) $catalog->name,
            'detail'            => $catalog->detail,
            'catalog_price'     => round((float) $catalog->price, 2),
            'price_at_time'     => $price,
            'created_by'        => $label,
            'updated_by'        => $label,
        ]);

        if (!$id) {
            throw new HTTPException('Custom service could not be added', Response::HTTP_BAD_REQUEST);
        }

        return [
            'id'     => $id,
            'totals' => $this->recalculate($reservationId),
        ];
    }

    /**
     * Cambia el precio cobrado en la reserva (único campo editable).
     *
     * @return array{updated:bool, totals:?object}
     */
    public function update(string $id, array $data, string $userLabel): array
    {
        $existing = $this->findOrFail($id);

        if (!array_key_exists('price_at_time', $data)) {
            throw new HTTPException('price_at_time is required', Response::HTTP_BAD_REQUEST);
        }

        $this->repository->update($id, [
            'price_at_time' => $this->validPrice($data['price_at_time']),
            'updated_by'    => mb_substr($userLabel, 0, 255),
        ]);

        return [
            'updated' => true,
            'totals'  => $this->recalculate((string) $existing->reservation_id),
        ];
    }

    /**
     * Quita el servicio de la reserva (soft delete).
     *
     * @return array{deleted:bool, totals:?object}
     */
    public function delete(string $id): array
    {
        $existing = $this->findOrFail($id);

        if (!$this->repository->delete($id)) {
            throw new HTTPException('Custom service could not be removed', Response::HTTP_BAD_REQUEST);
        }

        return [
            'deleted' => true,
            'totals'  => $this->recalculate((string) $existing->reservation_id),
        ];
    }

    private function findOrFail(string $id)
    {
        $this->assertUuid($id, 'id');
        $existing = $this->repository->getById($id);
        if (!$existing) {
            throw new HTTPException('Reservation custom service not found', Response::HTTP_NOT_FOUND);
        }
        return $existing;
    }

    private function validPrice($value): float
    {
        if ($value === null || $value === '' || !is_numeric($value) || (float) $value < 0) {
            throw new HTTPException('Custom service price must be a number greater than or equal to zero', Response::HTTP_BAD_REQUEST);
        }
        return round((float) $value, 2);
    }

    private function assertUuid(string $value, string $field): void
    {
        if (!preg_match(self::UUID_REGEX, $value)) {
            throw new HTTPException("Invalid {$field}", Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Un fallo del recálculo nunca revierte la operación ya guardada.
     */
    private function recalculate(string $reservationId): ?object
    {
        try {
            return $this->reservationService()->recalculateTotals($reservationId);
        } catch (\Throwable $e) {
            log_message('error', 'ReservationCustomServiceService: recalculation failed for ' . $reservationId . ': ' . $e->getMessage());
            return null;
        }
    }
}
