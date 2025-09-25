<?php

/**
 * ReservationService
 *
 * Servicio principal para gestionar reservas de servicios de entretenimiento infantil.
 * Maneja todo el ciclo de vida de las reservas: creación, consulta, actualización y eliminación.
 *
 * CARACTERÍSTICAS PRINCIPALES:
 * - Creación de reservas desde formulario multi-step del frontend
 * - Cálculo automático de precios, fees y recargos
 * - Gestión de clientes (crear o encontrar existentes)
 * - Manejo transaccional de addons
 * - Validaciones de negocio completas
 * - Soporte para diferentes tipos de precios (standard/jukebox)
 *
 * ESTRUCTURA DE PRECIOS:
 * - Precio base del servicio
 * - Costo por niños adicionales (extra_child_fee)
 * - Precio total de addons seleccionados
 * - Recargos por proximidad de fecha:
 *   * < 2 días: +20%
 *   * 2-7 días: +10%
 *   * > 7 días: Sin recargo
 *
 * @package App\Services
 * @author  JamWithJamie Team
 * @version 2.0.0
 * @since   1.0.0
 */

namespace App\Services;

use App\Repositories\ReservationRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\ReservationAddonRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;

/**
 * Servicio principal para gestión de reservas
 */
class ReservationService
{
    /**
     * Repository para operaciones de reservas
     * @var ReservationRepository
     */
    protected $repository;

    /**
     * Repository para operaciones de clientes
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * Repository para relaciones reserva-addon
     * @var ReservationAddonRepository
     */
    protected $reservationAddonRepository;

    /**
     * Constructor del servicio
     * Inicializa todos los repositories necesarios
     */
    public function __construct()
    {
        $this->repository = new ReservationRepository();
        $this->customerRepository = new CustomerRepository();
        $this->reservationAddonRepository = new ReservationAddonRepository();
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
        $bookingDate = isset($data['form']['date']) ? new \DateTime($data['form']['date']) : null;
        $today = new \DateTime();

        // Calcular totales usando funciones centralizadas
        $addonsTotal = $this->calculateAddonsTotal($addons);

        // Calcular niños extra usando límite fijo de 40 niños
        $selectedKids = intval($data['form']['selectedKids'] ?? $data['kids']['count'] ?? $data['kids']['selectedKids'] ?? 0);
        $maxKidsIncluded = 40; // Límite fijo: solo se cobran extras después de 40 niños
        $extraChildren = max(0, $selectedKids - $maxKidsIncluded);
        $extraChildFee = floatval($data['price']['extra_child_fee'] ?? 0);
        $extraChildrenTotal = $extraChildren * $extraChildFee;
        $baseTotal = $servicePrice + $addonsTotal + $extraChildrenTotal;

        $eventDateStr = $bookingDate ? $bookingDate->format('Y-m-d') : null;
        $surchargeAmount = $this->calculateSurcharge($baseTotal, $eventDateStr);
        $grandTotal = $baseTotal + $surchargeAmount;

        // Calcular duración total incluyendo addons
        $baseDurationHours = floatval($data['price']['min_duration_hours'] ?? 1);
        $durationCalculation = $this->calculateTotalDuration($baseDurationHours, $addons);
        $totalDurationHours = $durationCalculation['total_hours'];

        $reservationData = [
            'customer_id' => $data['customer']['id'] ?? null,
            'service_price_id' => $data['price']['id'] ?? null,
            'zipcode_id' => $data['areas']['zipcode']['id'],
            'event_address' => $data['form']['eventAddress'],
            'event_date' => $bookingDate ? $bookingDate->format('Y-m-d') : null,
            'event_time' => $data['form']['startTime'] ?? null,
            'children_count' => $selectedKids,
            'performers_count' => $data['price']['performers_count'] ?? null,
            'duration_hours' => $totalDurationHours,
            'price_type' => $this->determinePriceType($data['addons'] ?? []),
            'base_price' => $servicePrice,
            'addons_total' => $addonsTotal,
            'expedition_fee' => 0,
            'extra_children_fee' => $extraChildrenTotal,
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
     * Crea una nueva reserva desde el formulario multi-step del frontend
     *
     * PROCESO COMPLETO:
     * 1. Valida todos los datos requeridos y formatos
     * 2. Inicia transacción de base de datos
     * 3. Crea o encuentra el cliente por email
     * 4. Calcula precios detallados (servicio + addons + extras + recargos)
     * 5. Crea la reserva principal
     * 6. Guarda relaciones de addons en tabla separada
     * 7. Confirma transacción
     *
     * VALIDACIONES APLICADAS:
     * - Datos requeridos presentes
     * - Fecha del evento futura y formato válido
     * - Al menos 1 niño requerido
     * - Precio de servicio válido (> 0)
     * - Addons con IDs y precios válidos
     * - Duración requerida si no está en servicio
     *
     * CÁLCULOS AUTOMÁTICOS:
     * - Niños extra: selectedKids - serviceIncludedKids
     * - Total addons: suma(quantity × base_price)
     * - Recargos por fecha: 20% (<2 días), 10% (2-7 días)
     * - Total final: servicio + addons + extras + recargos
     *
     * @param array $formData Datos del formulario con estructura:
     *   - customer: array {
     *       firstName: string, lastName: string,
     *       email: string, phone: string
     *     }
     *   - zipcode: array { id: string }
     *   - service: array {
     *       id: string, amount: float, extra_child_fee: float,
     *       performers_count: int, children_count: int
     *     }
     *   - kids: array { selectedKids: int }
     *   - hours: array { duration: float }
     *   - addons: array[] {
     *       id: string, base_price: float, quantity: int
     *     }
     *   - information: array {
     *       fullAddress: string, eventDate: string (Y-m-d),
     *       startTime: string, entertainmentStartTime: string,
     *       birthdayChildName: string, childAge: int,
     *       ageRange: string, songRequests: string,
     *       happyBirthdayRequest: string, instructions: string
     *     }
     *
     * @return array {
     *   reservation: object,      // Objeto reserva creada
     *   addons_saved: int,        // Cantidad de addons guardados
     *   calculation: array {      // Desglose detallado de cálculos
     *     service_price: float,
     *     addons_total: float,
     *     extra_children_total: float,
     *     extra_children_count: int,
     *     base_total: float,
     *     surcharge_amount: float,
     *     grand_total: float
     *   }
     * }
     *
     * @throws HTTPException 400 Si faltan datos requeridos
     * @throws HTTPException 400 Si la fecha es pasada o formato inválido
     * @throws HTTPException 400 Si menos de 1 niño
     * @throws HTTPException 400 Si precio de servicio inválido
     * @throws HTTPException 400 Si datos de addon inválidos
     * @throws HTTPException 400 Si falta duración requerida
     * @throws HTTPException 400 Si falla creación de cliente
     * @throws HTTPException 400 Si falla creación de reserva
     * @throws HTTPException 400 Si falla guardado de addons
     * @throws HTTPException 500 Si falla la transacción
     * @throws HTTPException 500 Si error inesperado
     *
     * @example
     * ```php
     * $data = [
     *   'customer' => ['firstName' => 'Juan', 'lastName' => 'Pérez', ...],
     *   'service' => ['id' => 'uuid', 'amount' => 350, ...],
     *   'kids' => ['selectedKids' => 10],
     *   'addons' => [['id' => 'uuid', 'base_price' => 150, 'quantity' => 1]],
     *   // ... más datos
     * ];
     * $result = $service->createFromForm($data);
     * echo $result['calculation']['grand_total']; // Total final
     * ```
     *
     * @version 2.0.0
     * @since 1.5.0
     */
    public function createFromForm(array $formData)
    {

        // Validar datos requeridos
        $customer = $formData['customer'] ?? null;
        $zipcode = $formData['zipcode'] ?? null;
        $service = $formData['service'] ?? null;
        $kids = $formData['kids'] ?? null;
        $hours = $formData['hours'] ?? null;
        $addons = $formData['addons'] ?? [];
        $information = $formData['information'] ?? null;

        // Validaciones básicas
        if (!$customer || !$zipcode || !$service || !$information) {
            throw new HTTPException('Missing required data', Response::HTTP_BAD_REQUEST);
        }

        // Validar fecha del evento
        $eventDate = $information['eventDate'] ?? null;
        if ($eventDate) {
            $eventDateTime = new \DateTime($eventDate);
            $eventDateTime->setTime(0, 0, 0); // Establecer a medianoche para comparar solo fechas

            $today = new \DateTime('today'); // Solo fecha, sin hora (ya es medianoche)

            if ($eventDateTime < $today) {
                throw new HTTPException('Event date cannot be in the past', Response::HTTP_BAD_REQUEST);
            }
        }

        // Validar campos numéricos - manejar diferentes estructuras de kids
        $selectedKids = 0;

        // Prioridad 1: selectedKids directo
        if (isset($kids['selectedKids'])) {
            $selectedKids = intval($kids['selectedKids']);
        }
        // Prioridad 2: count para tipo custom (40+ kids)
        elseif (isset($kids['count'])) {
            $selectedKids = intval($kids['count']);
        }
        // Prioridad 3: si hay rango de edades, usar 1 como default
        elseif (!empty($kids['min_age']) && !empty($kids['max_age'])) {
            $selectedKids = 1;
        }

        // Validar que tengamos al menos 1 niño
        if ($selectedKids < 1) {
            throw new HTTPException('At least one child is required', Response::HTTP_BAD_REQUEST);
        }

        $serviceAmount = floatval($service['amount'] ?? 0);
        if ($serviceAmount <= 0) {
            throw new HTTPException('Invalid service amount', Response::HTTP_BAD_REQUEST);
        }

        // Validar addons si se proporcionan
        if (!empty($addons)) {
            foreach ($addons as $addon) {
                if (empty($addon['id']) || !isset($addon['base_price']) || floatval($addon['base_price']) < 0) {
                    throw new HTTPException('Invalid addon data', Response::HTTP_BAD_REQUEST);
                }
            }
        }

        // Validar que hours esté presente si no hay min_duration_hours en service
        $hasDuration = !empty($hours['duration']) || !empty($hours['hours']) || !empty($hours['minutes']);
        if (!$hasDuration && empty($service['min_duration_hours'])) {
            throw new HTTPException('Duration is required', Response::HTTP_BAD_REQUEST);
        }

        // Iniciar transacción para garantizar consistencia
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $customerId = $this->createOrFindCustomer($customer, $information);

            // Calcular precios usando funciones centralizadas
            $servicePrice = $serviceAmount;
            $addonsTotal = $this->calculateAddonsTotal($addons);

            // Calcular recargo por niños adicionales usando límite fijo de 40 niños
            $maxKidsIncluded = 40; // Límite fijo: solo se cobran extras después de 40 niños
            $extraChildren = max(0, $selectedKids - $maxKidsIncluded);
            $extraChildFee = floatval($service['extra_child_fee'] ?? 0);
            $extraChildrenTotal = $extraChildren * $extraChildFee;

            $baseTotal = $servicePrice + $addonsTotal + $extraChildrenTotal;

            // Calcular recargo por proximidad de fecha
            $surchargeAmount = $this->calculateSurcharge($baseTotal, $eventDate);
            $grandTotal = $baseTotal + $surchargeAmount;

            // Calcular duración total incluyendo addons
            $baseDurationHours = floatval($hours['duration'] ?? $hours['hours'] ?? ($hours['minutes'] ? $hours['minutes'] / 60 : null) ?? $service['min_duration_hours'] ?? 1);
            $durationCalculation = $this->calculateTotalDuration($baseDurationHours, $addons);
            $totalDurationHours = $durationCalculation['total_hours'];
            $addonsDurationMinutes = $durationCalculation['addons_minutes'];

            // Mapear datos a la estructura de la BD
            $reservationData = [
                'customer_id' => $customerId,
                'service_price_id' => $service['id'] ?? null,
                'zipcode_id' => $zipcode['id'] ?? null,
                'event_address' => $information['fullAddress'] ?? null,
                'event_date' => $eventDate,
                'event_time' => $information['startTime'] ?? null,
                'children_count' => $selectedKids,
                'performers_count' => intval($service['performers_count'] ?? 1),
                'duration_hours' => $totalDurationHours,
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

            // Guardar addons en tabla reservation_addons
            if (!empty($addons)) {
                foreach ($addons as $addon) {
                    $addonData = [
                        'reservation_id' => $reservation->id,
                        'addon_id' => $addon['id'],
                        'quantity' => intval($addon['quantity'] ?? 1),
                        'price_at_time' => floatval($addon['base_price'] ?? 0)
                    ];

                    $addonResult = $this->reservationAddonRepository->create($addonData);
                    if (!$addonResult) {
                        throw new HTTPException('Failed to save addon relations', Response::HTTP_BAD_REQUEST);
                    }
                }
            }

            // Completar transacción
            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new HTTPException('Transaction failed', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return [
                'reservation' => $reservation,
                'addons_saved' => count($addons),
                'calculation' => [
                    'service_price' => $servicePrice,
                    'addons_total' => $addonsTotal,
                    'extra_children_total' => $extraChildrenTotal,
                    'extra_children_count' => $extraChildren,
                    'base_total' => $baseTotal,
                    'surcharge_amount' => $surchargeAmount,
                    'grand_total' => $grandTotal,
                    'base_duration_hours' => $baseDurationHours,
                    'addons_duration_minutes' => $addonsDurationMinutes,
                    'total_duration_hours' => $totalDurationHours
                ]
            ];

        } catch (\Throwable $e) {
            // Con transStart/transComplete no necesitamos rollback manual
            $db->transRollback(); // Esto es para garantizar limpieza por si acaso

            // Re-lanzar como HTTPException si ya lo es, sino crear nueva
            if ($e instanceof HTTPException) {
                throw $e;
            }

            throw new HTTPException('Failed to create reservation: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
     * Los servicios pueden tener pricing especial cuando incluyen addons de tipo 'jukebox'.
     * Esto afecta cómo se calculan algunos recargos y descuentos.
     *
     * @param array $addons Lista de addons con estructura: [['price_type' => string, ...], ...]
     * @return string 'jukebox' si algún addon es tipo jukebox, 'standard' en caso contrario
     *
     * @example
     * ```php
     * $addons = [
     *   ['price_type' => 'standard', 'name' => 'Face Paint'],
     *   ['price_type' => 'jukebox', 'name' => 'Music Hour']
     * ];
     * $type = $this->determinePriceType($addons); // Returns 'jukebox'
     * ```
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
     * LÓGICA DE NEGOCIO:
     * 1. Si el email ya existe en la BD, retorna el ID existente
     * 2. Si no existe, crea un nuevo customer con los datos proporcionados
     * 3. Combina datos del Step 1 (customer) y Step 8 (information)
     * 4. Prioriza información del Step 8 para nombres (más completa)
     *
     * @param array $customerData Datos básicos del customer (Step 1):
     *   - firstName: string
     *   - lastName: string
     *   - email: string
     *   - phone: string
     *
     * @param array $information Información detallada (Step 8):
     *   - name: string (opcional, sobrescribe firstName)
     *   - lastName: string (opcional, sobrescribe lastName)
     *
     * @return string UUID del customer creado o encontrado
     *
     * @throws HTTPException 400 Si falla la creación del customer en la BD
     *
     * @example
     * ```php
     * $customer = ['firstName' => 'Juan', 'email' => 'juan@email.com'];
     * $info = ['name' => 'Juan Carlos']; // Sobrescribe firstName
     * $customerId = $this->createOrFindCustomer($customer, $info);
     * ```
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

    /**
     * Calcula el total de addons considerando cantidad y precio
     *
     * @param array $addons Lista de addons con estructura: [['base_price' => float, 'quantity' => int], ...]
     * @return float Total de addons
     */
    private function calculateAddonsTotal(array $addons): float
    {
        return array_reduce($addons, function ($sum, $addon) {
            return $sum + (floatval($addon['base_price'] ?? 0) * intval($addon['quantity'] ?? 1));
        }, 0);
    }

    /**
     * Calcula la duración total incluyendo addons
     *
     * @param float $baseDurationHours Duración base del servicio en horas
     * @param array $addons Lista de addons con duración
     * @return array ['total_hours' => float, 'addons_minutes' => int]
     */
    private function calculateTotalDuration(float $baseDurationHours, array $addons): array
    {
        $addonsDurationMinutes = 0;

        if (!empty($addons)) {
            foreach ($addons as $addon) {
                $addonDuration = intval($addon['estimated_duration_minutes'] ?? 0);
                $addonQuantity = intval($addon['quantity'] ?? 1);
                $addonsDurationMinutes += $addonDuration * $addonQuantity;
            }
        }

        $totalDurationHours = $baseDurationHours + ($addonsDurationMinutes / 60);

        return [
            'total_hours' => $totalDurationHours,
            'addons_minutes' => $addonsDurationMinutes
        ];
    }

    /**
     * Calcula recargos por proximidad de fecha
     *
     * @param float $baseTotal Total base antes de recargos
     * @param string|null $eventDate Fecha del evento en formato Y-m-d
     * @return float Monto del recargo
     */
    private function calculateSurcharge(float $baseTotal, ?string $eventDate): float
    {
        if (!$eventDate) {
            return 0;
        }

        $bookingDate = new \DateTime($eventDate);
        $bookingDate->setTime(0, 0, 0); // Establecer a medianoche

        $today = new \DateTime('today'); // Ya es medianoche por defecto
        $diffDays = (int)$today->diff($bookingDate)->format("%r%a");

        if ($diffDays < 0) $diffDays = 0;

        if ($diffDays < 2) {
            return $baseTotal * 0.2; // 20% recargo
        } elseif ($diffDays <= 7) {
            return $baseTotal * 0.1; // 10% recargo
        }

        return 0;
    }
}
