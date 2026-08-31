<?php

namespace App\Services;

use App\Repositories\ReservationAddonRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;

class ReservationAddonService
{
    protected ReservationAddonRepository $repository;

    /**
     * B6 — servicio de reservas usado para disparar el recálculo de totales tras
     * cada alta/edición/baja de add-on. En una propiedad (lazy) para poder
     * sustituirlo por un doble en los tests (regla transversal 9).
     *
     * @var \App\Services\ReservationService|null
     */
    protected $reservationService = null;

    public function __construct()
    {
        $this->repository = new ReservationAddonRepository();
    }

    /**
     * Instancia lazy de ReservationService (aislada para tests).
     */
    protected function reservationService(): \App\Services\ReservationService
    {
        if ($this->reservationService === null) {
            $this->reservationService = new \App\Services\ReservationService();
        }

        return $this->reservationService;
    }

    /**
     * B6 — Dispara el recálculo de totales de la reserva afectada y devuelve la
     * reserva actualizada (o null si el recálculo falla; un fallo del recálculo
     * nunca revierte la operación de add-on que ya se persistió).
     */
    private function recalculateReservation(?string $reservationId): ?object
    {
        if (!$reservationId || !preg_match(self::UUID_V4_REGEX, $reservationId)) {
            return null;
        }

        try {
            return $this->reservationService()->recalculateTotals($reservationId);
        } catch (\Throwable $e) {
            log_message('error', 'ReservationAddonService: recalculation failed for ' . $reservationId . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Regex UUID v4 (lo mantenemos estricto para coherencia).
     */
    private const UUID_V4_REGEX = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    /**
     * Validar datos de creación/actualización.
     * $isUpdate: si es true, solo validamos campos presentes.
     */
    private function validateOrFail(array $data, bool $isUpdate = false): void
    {
        $errors = [];

        $validateUuid = function ($value, $field) use (&$errors) {
            if (!is_string($value) || !preg_match(self::UUID_V4_REGEX, $value)) {
                $errors[$field] = lang('ReservationAddon.invalidUUID', [$field]);
            }
        };

        // reservation_id
        if (!$isUpdate || array_key_exists('reservation_id', $data)) {
            if (!isset($data['reservation_id'])) {
                $errors['reservation_id'] = lang('ReservationAddon.required', ['reservation_id']);
            } else {
                $validateUuid($data['reservation_id'], 'reservation_id');
            }
        }

        // addon_id
        if (!$isUpdate || array_key_exists('addon_id', $data)) {
            if (!isset($data['addon_id'])) {
                $errors['addon_id'] = lang('ReservationAddon.required', ['addon_id']);
            } else {
                $validateUuid($data['addon_id'], 'addon_id');
            }
        }

        // quantity
        if (!$isUpdate || array_key_exists('quantity', $data)) {
            if (!isset($data['quantity']) || filter_var($data['quantity'], FILTER_VALIDATE_INT) === false) {
                $errors['quantity'] = lang('ReservationAddon.invalidInteger', ['quantity']);
            } elseif ((int)$data['quantity'] < 1) {
                $errors['quantity'] = lang('ReservationAddon.minValue', ['quantity', 1]);
            }
        }

        // price_at_time
        if (!$isUpdate || array_key_exists('price_at_time', $data)) {
            if (!isset($data['price_at_time']) || !is_numeric($data['price_at_time'])) {
                $errors['price_at_time'] = lang('ReservationAddon.invalidNumber', ['price_at_time']);
            } elseif ($data['price_at_time'] < 0) {
                $errors['price_at_time'] = lang('ReservationAddon.minValue', ['price_at_time', 0]);
            }
        }

        if (!empty($errors)) {
            // Arrojamos un único HTTPException con los detalles
            throw new HTTPException(json_encode($errors, JSON_UNESCAPED_UNICODE), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Obtener todos.
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Obtener por ID.
     */
    public function getById(string $id)
    {
        $item = $this->repository->getById($id);

        if (!$item) {
            throw new HTTPException(lang('ReservationAddon.notFound'), Response::HTTP_NOT_FOUND);
        }

        return $item;
    }

    /**
     * Obtener por reservación.
     */
    public function getByReservation(string $reservationId): array
    {
        if (!preg_match(self::UUID_V4_REGEX, $reservationId)) {
            throw new HTTPException(lang('ReservationAddon.invalidUUID', ['reservation_id']), Response::HTTP_BAD_REQUEST);
        }

        return $this->repository->getByReservation($reservationId);
    }

    /**
     * Crear. Dispara el recálculo de totales de la reserva (B6).
     *
     * @return array{id:string, totals:?object}
     */
    public function create(array $data): array
    {
        $this->validateOrFail($data, false);

        $id = $this->repository->create($data);

        if (!$id) {
            throw new HTTPException(lang('ReservationAddon.createFailed'), Response::HTTP_BAD_REQUEST);
        }

        return [
            'id'     => $id,
            'totals' => $this->recalculateReservation($data['reservation_id'] ?? null),
        ];
    }

    /**
     * Actualizar. Dispara el recálculo de totales de la reserva (B6).
     *
     * @return array{updated:bool, totals:?object}
     */
    public function update(string $id, array $data): array
    {
        // Evitar "There is no data to update."
        if (empty($data)) {
            throw new HTTPException(lang('ReservationAddon.emptyPayload'), Response::HTTP_BAD_REQUEST);
        }

        // Validación parcial (solo lo enviado)
        $this->validateOrFail($data, true);

        // Verificar existencia y capturar la reserva afectada.
        $existing = $this->getById($id);
        $reservationId = is_array($existing)
            ? ($existing['reservation_id'] ?? null)
            : ($existing->reservation_id ?? null);

        $updated = $this->repository->update($id, $data);

        if (!$updated) {
            throw new HTTPException(lang('ReservationAddon.updateFailed'), Response::HTTP_BAD_REQUEST);
        }

        return [
            'updated' => true,
            'totals'  => $this->recalculateReservation(
                $data['reservation_id'] ?? ($reservationId ? (string) $reservationId : null)
            ),
        ];
    }

    /**
     * Eliminar (soft delete). Dispara el recálculo de totales de la reserva (B6).
     *
     * @return array{deleted:bool, totals:?object}
     */
    public function delete(string $id): array
    {
        // Verificar existencia y capturar la reserva afectada ANTES del borrado.
        $existing = $this->getById($id);
        $reservationId = is_array($existing)
            ? ($existing['reservation_id'] ?? null)
            : ($existing->reservation_id ?? null);

        $deleted = $this->repository->delete($id);

        if (!$deleted) {
            throw new HTTPException(lang('ReservationAddon.deleteFailed'), Response::HTTP_BAD_REQUEST);
        }

        return [
            'deleted' => true,
            'totals'  => $this->recalculateReservation($reservationId ? (string) $reservationId : null),
        ];
    }
}
