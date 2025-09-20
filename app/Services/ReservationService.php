<?php

/**
 * ReservationService
 *
 * Servicio para gestionar reservas de servicios de entretenimiento.
 * Maneja la creación, consulta, actualización y eliminación de reservas,
 * incluyendo la lógica de negocio para cálculos de precios y fees.
 *
 * @package App\Services
 * @author  JamWithJamie Team
 * @version 1.0.0
 */

namespace App\Services;

use App\Repositories\ReservationRepository;
use App\Repositories\CustomerRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;

class ReservationService
{
    protected $repository;
    protected $customerRepository;

    public function __construct()
    {
        $this->repository = new ReservationRepository();
        $this->customerRepository = new CustomerRepository();
    }

    /**
     * Obtiene todas las reservas del sistema
     *
     * @return array Lista de reservas
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Obtiene una reserva específica por su ID
     *
     * @param string $id ID de la reserva
     * @return mixed Datos de la reserva
     * @throws HTTPException Si la reserva no existe
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
     * Crea una nueva reserva (método legacy - usar createFromForm)
     *
     * @param array $data Datos de la reserva en formato legacy
     * @return mixed Reserva creada
     * @throws HTTPException Si falla la creación
     * @deprecated Usar createFromForm() en su lugar
     */
    public function create(array $data)
    {
        $servicePrice = $data['price']['amount'] ?? 0;
        $addons = $data['addons'] ?? [];
        $extraChildren = $data['form']['extraChildren'] ?? 0;
        $extraChildFee = $data['price']['extra_child_fee'] ?? 0;
        $bookingDate = isset($data['form']['date']) ? new \DateTime($data['form']['date']) : null;
        $today = new \DateTime();

        $addonsTotal = array_reduce($addons, function ($sum, $addon) {
            return $sum + ($addon['base_price'] ?? 0);
        }, 0);

        $extraChildrenTotal = $extraChildren * $extraChildFee;
        $baseTotal = $servicePrice + $addonsTotal + $extraChildrenTotal;

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
            'price_type' => $this->determinePriceType($data['addons'] ?? []),
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

        $response = $this->repository->create($reservationData);

        if (!$response) {
            throw new HTTPException(lang('Reservation.createFailed'), Response::HTTP_BAD_REQUEST);
        }

        return $response;
    }

    /**
     * Crea una reserva desde los datos del formulario multi-step del frontend
     *
     * Este método procesa todos los datos recopilados en los 8 steps del wizard,
     * crea o encuentra el customer, calcula precios y fees, y guarda la reserva.
     *
     * @param array $formData Datos del formulario con estructura:
     *   - customer: Datos del cliente (Step 1)
     *   - zipcode: Código postal validado (Step 2)
     *   - service: Servicio seleccionado (Step 3)
     *   - kids: Información de niños (Step 4)
     *   - hours: Duración del evento (Step 5)
     *   - addons: Servicios adicionales (Step 6)
     *   - subtotal: Confirmación de subtotal (Step 7)
     *   - information: Información detallada del evento (Step 8)
     *
     * @return array Reserva creada con cálculos detallados
     * @throws HTTPException Si faltan datos requeridos o falla la creación
     */
    public function createFromForm(array $formData)
    {
        $customer = $formData['customer'] ?? null;
        $zipcode = $formData['zipcode'] ?? null;
        $service = $formData['service'] ?? null;
        $kids = $formData['kids'] ?? null;
        $hours = $formData['hours'] ?? null;
        $addons = $formData['addons'] ?? [];
        $information = $formData['information'] ?? null;

        if (!$customer || !$zipcode || !$service || !$information) {
            throw new HTTPException('Missing required data', Response::HTTP_BAD_REQUEST);
        }

        $customerId = $this->createOrFindCustomer($customer, $information);

        // Calcular precios
        $servicePrice = floatval($service['amount'] ?? 0);
        $addonsTotal = 0;

        if (!empty($addons)) {
            $addonsTotal = array_reduce($addons, function ($sum, $addon) {
                return $sum + floatval($addon['base_price'] ?? 0);
            }, 0);
        }

        // Calcular recargo por niños adicionales
        $extraChildren = max(0, intval($kids['selectedKids'] ?? 0) - intval($service['children_count'] ?? 0));
        $extraChildFee = floatval($service['extra_child_fee'] ?? 0);
        $extraChildrenTotal = $extraChildren * $extraChildFee;

        $baseTotal = $servicePrice + $addonsTotal + $extraChildrenTotal;

        // Recargo por proximidad de fecha
        $eventDate = $information['eventDate'] ?? null;
        $surchargeAmount = 0;

        if ($eventDate) {
            $bookingDate = new \DateTime($eventDate);
            $today = new \DateTime();
            $diffDays = (int)$today->diff($bookingDate)->format("%r%a");

            if ($diffDays < 0) $diffDays = 0;

            if ($diffDays < 2) {
                $surchargeAmount = $baseTotal * 0.2; // 20% recargo
            } elseif ($diffDays <= 7) {
                $surchargeAmount = $baseTotal * 0.1; // 10% recargo
            }
        }

        $grandTotal = $baseTotal + $surchargeAmount;

        // Mapear datos a la estructura de la BD
        $reservationData = [
            'customer_id' => $customerId,
            'service_price_id' => $service['id'] ?? null,
            'zipcode_id' => $zipcode['id'] ?? null,
            'event_address' => $information['fullAddress'] ?? null,
            'event_date' => $eventDate,
            'event_time' => $information['startTime'] ?? null,
            'children_count' => intval($kids['selectedKids'] ?? 0),
            'performers_count' => intval($service['performers_count'] ?? 1),
            'duration_hours' => floatval($hours['duration'] ?? $service['min_duration_hours'] ?? 1),
            'price_type' => $this->determinePriceType($addons),
            'base_price' => $servicePrice,
            'addons_total' => $addonsTotal,
            'expedition_fee' => $surchargeAmount,
            'extra_children_fee' => $extraChildrenTotal,
            'total_amount' => $grandTotal,
            'status' => 'new',
            'is_invoiced' => false,
            'is_paid' => false,
            'arrival_parking_instructions' => $information['instructions'] ?? null,
            'entertainment_start_time' => $information['entertainmentStartTime'] ?? null,
            'birthday_child_name' => $information['birthdayChildName'] ?? null,
            'birthday_child_age' => intval($information['childAge'] ?? 0),
            'children_age_range' => $information['ageRange'] ?? null,
            'song_requests' => $information['songRequests'] ?? null,
            'sing_happy_birthday' => ($information['happyBirthdayRequest'] ?? 'no') === 'yes',
            'customer_notes' => null,
            'internal_notes' => null
        ];

        // Crear la reserva
        $reservation = $this->repository->create($reservationData);

        if (!$reservation) {
            throw new HTTPException('Failed to create reservation', Response::HTTP_BAD_REQUEST);
        }

        // TODO: Implementar guardado de addons en tabla reservation_addons

        return [
            'reservation' => $reservation,
            'calculation' => [
                'service_price' => $servicePrice,
                'addons_total' => $addonsTotal,
                'extra_children_total' => $extraChildrenTotal,
                'base_total' => $baseTotal,
                'surcharge_amount' => $surchargeAmount,
                'grand_total' => $grandTotal
            ]
        ];
    }

    /**
     * Actualiza una reserva existente
     *
     * @param string $id ID de la reserva a actualizar
     * @param array $data Datos a actualizar
     * @return bool True si se actualizó correctamente
     * @throws HTTPException Si falla la actualización
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
     * Elimina una reserva (soft delete)
     *
     * @param string $id ID de la reserva a eliminar
     * @return bool True si se eliminó correctamente
     * @throws HTTPException Si falla la eliminación
     */
    public function delete(string $id): bool
    {
        $deleted = $this->repository->delete($id);

        if (!$deleted) {
            throw new HTTPException(lang('Reservation.deleteFailed'), Response::HTTP_BAD_REQUEST);
        }

        return true;
    }

    /**
     * Determina el tipo de precio basado en los addons seleccionados
     *
     * @param array $addons Lista de addons seleccionados
     * @return string 'jukebox' si algún addon es tipo jukebox, 'standard' en caso contrario
     */
    private function determinePriceType(array $addons): string
    {
        foreach ($addons as $addon) {
            if (isset($addon['price_type']) && $addon['price_type'] === 'jukebox') {
                return 'jukebox';
            }
        }
        return 'standard';
    }

    /**
     * Crea un nuevo customer o encuentra uno existente por email
     *
     * @param array $customerData Datos del customer del Step 1
     * @param array $information Información adicional del Step 8
     * @return string ID del customer creado o encontrado
     * @throws HTTPException Si falla la creación del customer
     */
    private function createOrFindCustomer(array $customerData, array $information): string
    {
        $email = $customerData['email'] ?? null;
        if ($email) {
            $existingCustomer = $this->customerRepository->getByEmail($email);
            if ($existingCustomer) {
                return $existingCustomer->id;
            }
        }

        $newCustomerData = [
            'first_name' => $information['name'] ?? $customerData['firstName'] ?? '',
            'last_name' => $information['lastName'] ?? $customerData['lastName'] ?? '',
            'full_name' => ($information['name'] ?? $customerData['firstName'] ?? '') . ' ' . ($information['lastName'] ?? $customerData['lastName'] ?? ''),
            'email' => $customerData['email'] ?? '',
            'phone' => $customerData['phone'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $customerId = $this->customerRepository->create($newCustomerData);

        if (!$customerId) {
            throw new HTTPException('Failed to create customer', Response::HTTP_BAD_REQUEST);
        }

        return $customerId;
    }
}
