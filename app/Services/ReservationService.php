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
use App\Repositories\PromoCodeRepository;
use App\Services\BrevoEmailService;
use App\Services\EmailTemplateService;
use App\Services\StripeService;
use App\Models\ReservationEmailHistoryModel;
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
     * Servicio para envío de emails
     * @var BrevoEmailService
     */
    protected $emailService;

    /**
     * Servicio para renderizar templates de email
     * @var EmailTemplateService
     */
    protected $emailTemplateService;

    /**
     * Repository para operaciones de promo codes
     * @var PromoCodeRepository
     */
    protected $promoCodeRepository;

    /**
     * Servicio para integración con Stripe (lazy-loaded)
     * @var StripeService|null
     */
    protected $stripeService = null;

    /**
     * Constructor del servicio
     * Inicializa todos los repositories necesarios
     */
    public function __construct()
    {
        $this->repository = new ReservationRepository();
        $this->customerRepository = new CustomerRepository();
        $this->reservationAddonRepository = new ReservationAddonRepository();
        $this->promoCodeRepository = new PromoCodeRepository();
        $this->emailService = new BrevoEmailService();
        $this->emailTemplateService = new EmailTemplateService();
    }

    /**
     * Get StripeService instance (lazy initialization)
     */
    protected function getStripeService(): StripeService
    {
        if ($this->stripeService === null) {
            $this->stripeService = new StripeService();
        }
        return $this->stripeService;
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

        // Calcular niños extra
        // Admin form envía form.extraChildren directamente; customer form envía selectedKids (total - 40)
        if (isset($data['form']['extraChildren'])) {
            $extraChildren = max(0, intval($data['form']['extraChildren']));
            $selectedKids = $extraChildren; // children_count
        } else {
            $selectedKids = intval($data['form']['selectedKids'] ?? $data['kids']['count'] ?? $data['kids']['selectedKids'] ?? 0);
            $extraChildren = max(0, $selectedKids - 40);
        }
        $extraChildFee = floatval($data['price']['extra_child_fee'] ?? 0);
        $extraChildrenTotal = $extraChildren * $extraChildFee;
        $baseTotal = $servicePrice + $addonsTotal + $extraChildrenTotal;

        $eventDateStr = $bookingDate ? $bookingDate->format('Y-m-d') : null;
        $surchargeAmount = $this->calculateSurcharge($baseTotal, $eventDateStr);

        // Calcular travel fee del zipcode
        $travelFee = 0;
        $zipcode = $data['areas']['zipcode'] ?? null;
        $performers = intval($data['price']['performers_count'] ?? 1);
        if ($zipcode) {
            if ($performers >= 2 && !empty($zipcode['travel_fee_2_performers'])) {
                $travelFee = floatval($zipcode['travel_fee_2_performers']);
            } elseif (!empty($zipcode['travel_fee_1_performer'])) {
                $travelFee = floatval($zipcode['travel_fee_1_performer']);
            }
        }
        if ($travelFee == 0) {
            $travelFee = floatval($data['price']['travel_fee'] ?? 0);
        }

        // Descuento de promo code (solo aplica al baseTotal, no al surcharge)
        $discountAmount = floatval($data['promoCode']['discount_amount'] ?? 0);
        $promoCodeUsed = $data['promoCode']['code'] ?? null;

        $grandTotal = $baseTotal + $travelFee + $surchargeAmount - $discountAmount;

        // Calcular duración total incluyendo addons.
        // La duración base viene del type service; el precio queda como fallback legacy.
        $baseDurationHours = floatval($data['service']['duration_hours'] ?? $data['price']['min_duration_hours'] ?? 1);
        if (($data['areas']['zipcode']['zone_type'] ?? '') === 'minimum_2h') {
            $baseDurationHours = max($baseDurationHours, 2.0);
        }
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
            'expedition_fee' => $travelFee + $surchargeAmount,
            'extra_children_fee' => $extraChildrenTotal,
            'discount_amount' => $discountAmount,
            'promo_code' => $promoCodeUsed,
            'total_amount' => $grandTotal,
            'status' => 'new',
            'is_invoiced' => false,
            'is_paid' => false,
            'arrival_parking_instructions' => $data['form']['arrivalParkingInstructions'] ?? "-",
            'entertainment_start_time' => $data['form']['entertainmentStartTime'] ?? $data['form']['startTime'],
            'birthday_child_name' => $data['form']['birthdayChildName'] ?? null,
            'birthday_child_age' => $data['form']['childAge'] ?? $data['form']['birthdayChildAge'] ?? null,
            'children_age_range' => $data['form']['childrenAgeRange'] ?? "-",
            'song_requests' => $data['form']['songRequests'] ?? "-",
            'sing_happy_birthday' => ($data['form']['happyBirthdayRequest'] ?? $data['form']['singHappyBirthday'] ?? '') === 'yes',
            'customer_notes' => $data['form']['customerNotes'] ?? null,
            'internal_notes' => null
        ];

        $response = $this->repository->create($reservationData);

        if (!$response) {
            throw new HTTPException(lang('Reservation.createFailed'), Response::HTTP_BAD_REQUEST);
        }

        if ($promoCodeUsed) {
            $promoCode = $this->promoCodeRepository->findByCode($promoCodeUsed);
            if ($promoCode) {
                $this->promoCodeRepository->incrementUsage($promoCode['id']);
            }
        }

        // Save addons to reservation_addons table
        if (!empty($addons)) {
            foreach ($addons as $addon) {
                $this->reservationAddonRepository->create([
                    'reservation_id' => $response->id,
                    'addon_id'       => $addon['id'],
                    'quantity'       => intval($addon['quantity'] ?? 1),
                    'suboption'      => $addon['suboption'] ?? $addon['selectedOption'] ?? null,
                    'price_at_time'  => floatval($addon['selectedPrice'] ?? $addon['base_price'] ?? 0),
                ]);
            }
        }

        // Send "Reservation Received" confirmation email (no payment link)
        try {
            $fullReservation = $this->repository->getById($response->id);
            if ($fullReservation) {
                $this->sendConfirmationEmail($fullReservation);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Failed to send confirmation email after admin reservation creation: ' . $e->getMessage());
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
        $price   = $formData['price'] ?? [];
        $subtotal = $formData['subtotal'] ?? null;
        $addons = $formData['addons'] ?? [];
        $information = $formData['information'] ?? null;

        // Validaciones básicas
        if (!$customer || !$zipcode || !$service || !$information) {
            throw new HTTPException('Missing required data', Response::HTTP_BAD_REQUEST);
        }

        // Validar fecha del evento - puede venir en customer.eventDateTime o information.eventDate
        $eventDate = null;

        // Prioridad 1: eventDateTime de customer (nuevo formulario)
        if (!empty($customer['eventDateTime'])) {
            // Convertir "2025-11-06 00:00:00" a "2025-11-06"
            $eventDateTime = new \DateTime($customer['eventDateTime']);
            $eventDate = $eventDateTime->format('Y-m-d');
        }
        // Prioridad 2: eventDate de information (formulario antiguo)
        elseif (!empty($information['eventDate'])) {
            $eventDate = $information['eventDate'];
        }

        // Validación de fecha removida - se valida solo en el frontend

        // Validar campos numéricos - leer desde customer (nuevo formulario)
        $selectedKids = 0;

        // Nuevo formulario: childrenRange y exactChildrenCount en customer
        if (isset($customer['childrenRange'])) {
            $childrenRange = $customer['childrenRange'];

            if ($childrenRange === '31+ kids' && isset($customer['exactChildrenCount'])) {
                $selectedKids = intval($customer['exactChildrenCount']);
            } elseif ($childrenRange === '11-30 kids') {
                $selectedKids = 20; // Punto medio del rango
            } elseif ($childrenRange === '1-10 kids') {
                $selectedKids = 5; // Punto medio del rango
            }
        }
        // Formulario antiguo: kids con selectedKids o count
        elseif (isset($formData['kids'])) {
            $kids = $formData['kids'];

            if (isset($kids['selectedKids'])) {
                $selectedKids = intval($kids['selectedKids']);
            } elseif (isset($kids['count'])) {
                $selectedKids = intval($kids['count']);
            } elseif (!empty($kids['min_age']) && !empty($kids['max_age'])) {
                $selectedKids = 1;
            }
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

        // Validación de duración removida - no es necesaria para el nuevo flujo

        // Iniciar transacción para garantizar consistencia
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $customerId = $this->createOrFindCustomer($customer, $information);

            // Usar los precios calculados del frontend si están disponibles (nuevo formulario)
            // Si no están, calcular usando las funciones centralizadas (formulario antiguo)
            $servicePrice = $serviceAmount;
            $addonsTotal = 0;
            $extraChildrenTotal = 0;
            $extraChildren = 0;
            $travelFee = 0;
            $discount = 0;
            $grandTotal = 0;
            $baseTotal = 0;
            $surchargeAmount = 0;

            if ($subtotal) {
                // Nuevo formulario: usar valores ya calculados
                $servicePrice = floatval($subtotal['servicePrice'] ?? $serviceAmount);
                $addonsTotal = floatval($subtotal['addonsTotal'] ?? 0);
                $extraChildrenTotal = floatval($subtotal['extraChildrenTotal'] ?? 0);
                $travelFee = floatval($subtotal['travelFee'] ?? 0);
                $discount = floatval($subtotal['discount'] ?? 0);
                $grandTotal = floatval($subtotal['subtotal'] ?? 0);

                // Calcular extraChildren count para el reporte
                $maxKidsIncluded = intval($service['max_kids_included'] ?? 40);
                $extraChildren = max(0, $selectedKids - $maxKidsIncluded);

                // Calcular baseTotal y surchargeAmount
                // baseTotal = servicePrice + addonsTotal + extraChildrenTotal - discount
                $baseTotal = $servicePrice + $addonsTotal + $extraChildrenTotal - $discount;
                // El surchargeAmount se considera 0 ya que está incluido en el travelFee
                $surchargeAmount = 0;
            } else {
                // Formulario antiguo: calcular usando funciones centralizadas
                $addonsTotal = $this->calculateAddonsTotal($addons);

                // Obtener travel_fee del servicio
                $travelFee = floatval($service['travel_fee'] ?? 0);

                // Calcular recargo por niños adicionales
                $maxKidsIncluded = intval($service['max_kids_included'] ?? 40);
                $extraChildren = max(0, $selectedKids - $maxKidsIncluded);
                $extraChildFee = floatval($service['extra_child_fee'] ?? 0);
                $extraChildrenTotal = $extraChildren * $extraChildFee;

                $baseTotal = $servicePrice + $addonsTotal + $extraChildrenTotal;

                // Calcular recargo por proximidad de fecha
                $surchargeAmount = $this->calculateSurcharge($baseTotal, $eventDate);
                $grandTotal = $baseTotal + $surchargeAmount + $travelFee;
            }

            // Calcular duración total incluyendo addons.
            // La duración base viene del type service; min_duration_hours queda como fallback legacy.
            $baseDurationHours = floatval($service['duration_hours'] ?? $service['min_duration_hours'] ?? $price['min_duration_hours'] ?? 1);
            if (($zipcode['zone_type'] ?? '') === 'minimum_2h') {
                $baseDurationHours = max($baseDurationHours, 2.0);
            }
            $durationCalculation = $this->calculateTotalDuration($baseDurationHours, $addons);
            $totalDurationHours = $durationCalculation['total_hours'];
            $addonsDurationMinutes = $durationCalculation['addons_minutes'];

            // Mapear datos a la estructura de la BD
            $reservationData = [
                'customer_id' => $customerId,
                'event_type' => $customer['eventType'] ?? null, // Type of Event from Step 1
                'service_price_id' => $service['id'] ?? null,
                'zipcode_id' => $zipcode['id'] ?? null,
                'event_address' => $information['fullAddress'] ?? 'To be confirmed',
                'event_date' => $eventDate,
                'event_time' => $information['startTime'] ?? null,
                'children_count' => $selectedKids,
                'children_age_range' => $customer['childrenRange'] ?? null,
                'performers_count' => intval($service['performers_count'] ?? 1),
                'duration_hours' => $totalDurationHours,
                'price_type' => $this->determinePriceType($addons),
                'base_price' => $servicePrice,
                'addons_total' => $addonsTotal,
                'expedition_fee' => $surchargeAmount + $travelFee, // Incluir travel fee en expedition_fee
                'extra_children_fee' => $extraChildrenTotal,
                'discount_amount' => $discount, // Descuento del promo code
                'promo_code' => $subtotal['promoCode'] ?? null, // Código promocional usado
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
                'customer_confirmed' => false,
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
                    // Usar selectedPrice si existe (para Jukebox Live), sino usar base_price
                    $priceToSave = floatval($addon['selectedPrice'] ?? $addon['base_price'] ?? 0);

                    $addonData = [
                        'reservation_id' => $reservation->id,
                        'addon_id' => $addon['id'],
                        'quantity' => intval($addon['quantity'] ?? 1),
                        'suboption' => $addon['suboption'] ?? $addon['selectedOption'] ?? null,
                        'price_at_time' => $priceToSave
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

            $promoCodeStr = $subtotal['promoCode'] ?? null;
            if ($promoCodeStr) {
                $promoCode = $this->promoCodeRepository->findByCode((string) $promoCodeStr);
                if ($promoCode) {
                    $this->promoCodeRepository->incrementUsage($promoCode['id']);
                }
            }

            // Enviar email de confirmación al cliente
            $this->sendConfirmationEmail($reservation);

            return [
                'reservation' => $reservation,
                'addons_saved' => count($addons),
                'calculation' => [
                    'service_price' => $servicePrice,
                    'addons_total' => $addonsTotal,
                    'extra_children_total' => $extraChildrenTotal,
                    'extra_children_count' => $extraChildren,
                    'travel_fee' => $travelFee,
                    'discount' => $discount,
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
     * Aplica o quita un promo code en una reservación existente.
     * Recalcula discount_amount y total_amount usando los montos ya guardados.
     * Si $code es null o vacío, quita el código y restaura el total original.
     */
    public function applyPromoCode(string $id, ?string $code): array
    {
        $reservation = $this->repository->getById($id);
        if (!$reservation) {
            throw new HTTPException('Reservation not found', Response::HTTP_NOT_FOUND);
        }

        $basePrice        = floatval($reservation->base_price ?? 0);
        $addonsTotal      = floatval($reservation->addons_total ?? 0);
        $extraChildrenFee = floatval($reservation->extra_children_fee ?? 0);
        $expeditionFee    = floatval($reservation->expedition_fee ?? 0);
        $grossTotal       = $basePrice + $addonsTotal + $extraChildrenFee + $expeditionFee;

        if (!$code) {
            // Quitar promo code: restaurar total original
            $this->repository->update($id, [
                'promo_code'      => null,
                'discount_amount' => 0,
                'total_amount'    => $grossTotal,
            ]);
            return [
                'promo_code'      => null,
                'discount_amount' => 0,
                'total_amount'    => $grossTotal,
            ];
        }

        // Validar el código
        $validation = $this->promoCodeRepository->findByCode($code);
        if (!$validation) {
            throw new HTTPException('Promo code not found', Response::HTTP_BAD_REQUEST);
        }
        if (!$validation['is_active']) {
            throw new HTTPException('Promo code is inactive', Response::HTTP_BAD_REQUEST);
        }
        if (!empty($validation['valid_until']) && strtotime($validation['valid_until']) < time()) {
            throw new HTTPException('Promo code has expired', Response::HTTP_BAD_REQUEST);
        }
        if (!empty($validation['valid_from']) && strtotime($validation['valid_from']) > time()) {
            throw new HTTPException('Promo code is not yet valid', Response::HTTP_BAD_REQUEST);
        }
        if ($validation['max_uses'] !== null && ($validation['times_used'] ?? 0) >= $validation['max_uses']) {
            throw new HTTPException('Promo code usage limit reached', Response::HTTP_BAD_REQUEST);
        }

        // Calcular descuento
        $discountBase = $basePrice + $addonsTotal + $extraChildrenFee;
        if ($validation['applies_to_travel_fee']) {
            $discountBase += $expeditionFee;
        }

        if ($validation['discount_type'] === 'percentage') {
            $discountAmount = $discountBase * floatval($validation['discount_value']) / 100;
        } else {
            $discountAmount = min(floatval($validation['discount_value']), $discountBase);
        }

        $newTotal = $grossTotal - $discountAmount;

        // Si la reservación ya tenía este mismo código no incrementamos el uso de nuevo
        $previousCode = $reservation->promo_code ?? null;
        $isNewCode    = strtoupper(trim($previousCode ?? '')) !== strtoupper(trim($code));

        $this->repository->update($id, [
            'promo_code'      => strtoupper($code),
            'discount_amount' => $discountAmount,
            'total_amount'    => $newTotal,
        ]);

        if ($isNewCode) {
            $this->promoCodeRepository->incrementUsage($validation['id']);
        }

        return [
            'promo_code'      => strtoupper($code),
            'discount_amount' => $discountAmount,
            'total_amount'    => $newTotal,
            'discount_type'   => $validation['discount_type'],
            'discount_value'  => $validation['discount_value'],
        ];
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

    public function bulkDelete(array $ids): array
    {
        if (empty($ids)) {
            throw new \Exception('No IDs provided', 400);
        }

        $deleted = 0;
        foreach ($ids as $id) {
            if ($this->repository->delete((string) $id)) {
                $deleted++;
            }
        }

        return ['deleted' => $deleted, 'total' => count($ids)];
    }

    /**
     * Elimina (soft delete) todas las reservas con event_date <= al domingo pasado
     *
     * @return array Resultado con la cantidad eliminada y la fecha de corte usada
     */
    public function deleteOldReservations(): array
    {
        // Calcular el domingo pasado
        $today = new \DateTime();
        $dayOfWeek = (int) $today->format('w'); // 0=domingo, 1=lunes, ...
        $daysBack = $dayOfWeek === 0 ? 7 : $dayOfWeek;
        $lastSunday = (clone $today)->modify("-{$daysBack} days");
        $cutoffDate = $lastSunday->format('Y-m-d');

        $deletedCount = $this->repository->deleteBeforeDate($cutoffDate);

        return [
            'deleted_count' => $deletedCount,
            'cutoff_date'   => $cutoffDate,
        ];
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
            // Usar selectedPrice si existe (para Jukebox Live), sino usar base_price
            $price = floatval($addon['selectedPrice'] ?? $addon['base_price'] ?? 0);
            return $sum + ($price * intval($addon['quantity'] ?? 1));
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
        $bookingDate->setTime(0, 0, 0);

        $today = new \DateTime('today');
        $diffDays = (int)$today->diff($bookingDate)->format("%r%a");

        if ($diffDays < 0) $diffDays = 0;

        // $50 flat surcharge for bookings with 3 days or less notice
        if ($diffDays <= 3) {
            return 50.0;
        }

        return 0;
    }

    /**
     * Sends the payment email with the public confirmation URL.
     *
     * @param string $reservationId ID de la reserva
     * @return array Confirmation URL data
     * @throws HTTPException Si la reserva no existe o falla el envío del email
     */
    public function sendPaymentEmail(string $reservationId): array
    {
        // Obtener datos completos de la reserva
        $reservation = $this->repository->getById($reservationId);

        if (!$reservation) {
            throw new HTTPException('Reservation not found', Response::HTTP_NOT_FOUND);
        }

        // Build and send the email using template service
        $frontendUrl = getenv('app.frontendURL') ?: 'http://localhost:5173';
        $confirmationUrl = rtrim($frontendUrl, '/') . '/confirmation/' . $reservationId;
        $eventDate = isset($reservation->event_date) ? date('F j, Y', strtotime($reservation->event_date)) : 'TBD';

        $descriptionBlock = '';
        if (!empty($reservation->description)) {
            $descriptionBlock = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;"><tr><td style="background-color: #FFF9E6; border-left: 4px solid #FFEF81; border-radius: 0 8px 8px 0; padding: 16px 20px;"><p style="margin: 0 0 4px; font-size: 13px; font-weight: 700; color: #1F2937; text-transform: uppercase; letter-spacing: 0.5px;">Event Description</p><p style="margin: 0; font-size: 14px; line-height: 1.6; color: #374151;">' . esc($reservation->description) . '</p></td></tr></table>';
        }

        $birthdayBlock = '';
        if (!empty($reservation->birthday_child_name)) {
            $birthdayBlock = '<tr><td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Birthday Child</td><td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">' . esc($reservation->birthday_child_name) . '</td></tr>';
        }

        $promoBlock    = '';
        $discountBlock = '';
        if (!empty($reservation->promo_code)) {
            $promoBlock    = '<tr><td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Promo Code</td><td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;"><span style="background-color: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 13px;">' . esc($reservation->promo_code) . '</span></td></tr>';
            $discountBlock = '<tr><td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Discount</td><td style="padding: 12px 16px; font-size: 14px; font-weight: 700; color: #059669; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">-$' . number_format((float)($reservation->discount_amount ?? 0), 2) . '</td></tr>';
        }

        $totalDurationLabel = $this->formatDurationLabel($reservation->duration_hours ?? 0);
        $totalDurationRow = $this->buildDurationRow($reservation->duration_hours ?? 0);

        $templateVars = [
            'customer_name'       => strtok(trim($reservation->full_name ?? ''), ' '),
            'reservation_id'      => $reservation->id,
            'service_name'        => $reservation->service_name ?? '',
            'event_date'          => $eventDate,
            'event_time'          => $reservation->event_time ?? '',
            'event_address'       => $reservation->event_address ?? '',
            'children_count'      => $reservation->children_age_range ?: ($reservation->children_count ?? ''),
            'birthday_child_name' => $birthdayBlock,
            'total_duration_row'  => $totalDurationRow,
            'promo_code_row'      => $promoBlock,
            'discount_row'        => $discountBlock,
            'duration_hours'      => $totalDurationLabel,
            'total_duration'      => $totalDurationLabel,
            'total_duration_label' => $totalDurationLabel,
            'total_amount'        => number_format($reservation->total_amount, 2),
            'description'         => $descriptionBlock,
            'confirmation_url'    => $confirmationUrl,
            'payment_url'         => $confirmationUrl,
            '_reservation'        => $reservation,
        ];

        $rendered = $this->emailTemplateService->render('payment_notification', $templateVars);

        try {
            $this->emailService->sendEmail($reservation->email, $rendered['subject'], $rendered['body']);
        } catch (\Throwable $e) {
            throw new HTTPException('Failed to send payment email: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return [
            'confirmation_url' => $confirmationUrl,
            'payment_url'      => $confirmationUrl,
        ];
    }

    public function renderTemplateEmail(string $reservationId, string $templateId): array
    {
        $reservation = $this->repository->getById($reservationId);
        if (!$reservation) {
            throw new HTTPException('Reservation not found', Response::HTTP_NOT_FOUND);
        }

        $this->emailTemplateService->getById($templateId);

        return $this->emailTemplateService->composePreview(
            $templateId,
            $this->buildReservationEmailVariables($reservation)
        );
    }

    public function sendTemplateEmail(string $reservationId, string $templateId, string $subject, string $body, bool $isFullHtml = false): array
    {
        $reservation = $this->repository->getById($reservationId);
        if (!$reservation) {
            throw new HTTPException('Reservation not found', Response::HTTP_NOT_FOUND);
        }

        $template = $this->emailTemplateService->getById($templateId);

        if (empty($reservation->email)) {
            throw new HTTPException('Customer email is required', Response::HTTP_BAD_REQUEST);
        }

        $subject = trim($subject);
        $body = trim($body);

        if ($subject === '') {
            throw new HTTPException('Subject is required', Response::HTTP_BAD_REQUEST);
        }

        if ($body === '') {
            throw new HTTPException('Content is required', Response::HTTP_BAD_REQUEST);
        }

        $variables = $this->buildReservationEmailVariables($reservation);
        $subject = $this->replaceReservationPlaceholders($subject, $variables);
        $body = $this->replaceReservationPlaceholders($body, $variables);
        if (($template->slug ?? '') === 'payment_needed_secure_event') {
            $body = $this->ensurePaymentReminderCtaButton($body, $variables['payment_url'] ?? '');
        }
        $html = $isFullHtml ? $body : $this->emailTemplateService->wrapContent($body);

        try {
            $this->emailService->sendEmail($reservation->email, $subject, $html);
        } catch (\Throwable $e) {
            throw new HTTPException('Failed to send email: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->recordEmailHistory([
            'reservation_id'  => $reservationId,
            'template_id'     => $templateId,
            'template_name'   => $template->name ?? '',
            'sent_by'         => $this->getAuthenticatedUserName(),
            'recipient_email' => $reservation->email,
            'email_subject'   => $subject,
            'email_body'      => $html,
            'status'          => 'Sent',
            'sent_at'         => date('Y-m-d H:i:s'),
        ]);

        return [
            'sent' => 1,
            'email' => $reservation->email,
        ];
    }

    public function getEmailHistory(string $reservationId): array
    {
        $reservation = $this->repository->getById($reservationId);
        if (!$reservation) {
            throw new HTTPException('Reservation not found', Response::HTTP_NOT_FOUND);
        }

        return (new ReservationEmailHistoryModel())
            ->where('reservation_id', $reservationId)
            ->orderBy('sent_at', 'DESC')
            ->findAll();
    }

    private function recordEmailHistory(array $data): void
    {
        (new ReservationEmailHistoryModel())->insert($data);
    }

    private function getAuthenticatedUserName(): string
    {
        $user = service('auth')->user();
        if (!$user) {
            return 'System';
        }

        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        if ($name !== '') {
            return $name;
        }

        return $user->email ?? 'System';
    }

    private function buildReservationEmailVariables(object $reservation): array
    {
        $frontendUrl = getenv('app.frontendURL') ?: 'http://localhost:5173';
        $confirmationUrl = rtrim($frontendUrl, '/') . '/confirmation/' . $reservation->id;
        $eventDate = isset($reservation->event_date) ? date('F j, Y', strtotime($reservation->event_date)) : 'TBD';
        $paymentUrl = $reservation->payment_url ?? $confirmationUrl;
        $customerName = trim($reservation->full_name ?? '');
        $entertainmentStartTime = $reservation->entertainment_start_time ?? '';
        $performersCount = $reservation->performers_count ?? '';

        return [
            'customer_name' => $customerName !== '' ? $customerName : 'Customer',
            'reservation_id' => $reservation->id,
            'service_name' => $reservation->service_name ?? '',
            'event_date' => $eventDate,
            'event_time' => $reservation->event_time ?? '',
            'entertainment_start_time' => $entertainmentStartTime,
            'event_address' => $reservation->event_address ?? '',
            'address' => $reservation->event_address ?? '',
            'children_count' => $reservation->children_age_range ?: ($reservation->children_count ?? ''),
            'performers_count' => $performersCount,
            'performers_names' => '',
            'event_contact_name' => '',
            'event_contact_phone' => '',
            'performer_venmo_handles' => '',
            'total_amount' => '$' . number_format((float) ($reservation->total_amount ?? 0), 2),
            'confirmation_url' => $confirmationUrl,
            'payment_url' => $paymentUrl,
            'payment_link' => $paymentUrl,
            'entertainment_start_time_row' => $this->buildOptionalSummaryRow('Entertainment Start Time', $entertainmentStartTime, true),
            'performers_row' => $this->buildOptionalSummaryRow('Performer(s)', $performersCount, false),
        ];
    }

    private function buildOptionalSummaryRow(string $label, mixed $value, bool $shaded): string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return '';
        }

        $background = $shaded ? ' background-color: #f9fafb;' : '';

        return '<tr><td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280;' . $background . ' width: 40%; border-bottom: 1px solid #e5e7eb;">' . esc($label) . '</td><td style="padding: 12px 16px; font-size: 14px; color: #1F2937;' . $background . ' border-bottom: 1px solid #e5e7eb;">' . esc($value) . '</td></tr>';
    }

    private function replaceReservationPlaceholders(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            if (!is_scalar($value) && $value !== null) {
                continue;
            }
            $content = str_replace('{{' . $key . '}}', (string) ($value ?? ''), $content);
        }

        return $content;
    }

    private function ensurePaymentReminderCtaButton(string $body, string $paymentUrl): string
    {
        if (stripos($body, 'Complete Payment') === false) {
            return $body;
        }

        if (stripos($body, 'background-color: #FF74B7') !== false && stripos($body, '<a ') !== false) {
            return $body;
        }

        $button = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 24px 0;">
    <tr><td align="center"><a href="' . esc($paymentUrl) . '" style="display: inline-block; background-color: #FF74B7; color: #000000; padding: 16px 40px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 700; letter-spacing: 0.3px;">Complete Payment</a></td></tr>
</table>';

        $body = preg_replace(
            '/<p>\s*<a\s+[^>]*href="[^"]*"[^>]*>\s*Complete Payment\s*<\/a>\s*<\/p>/i',
            $button,
            $body,
            1,
            $count
        );

        if ($count > 0) {
            return $body;
        }

        $body = preg_replace(
            '/<p>\s*Complete Payment\s*<\/p>/i',
            $button,
            $body,
            1,
            $count
        );

        if ($count > 0) {
            return $body;
        }

        return preg_replace('/Complete Payment/i', $button, $body, 1) ?? $body;
    }

    /**
     * Regenerates a Stripe Checkout Session for an unpaid reservation (no email sent)
     *
     * @param string $reservationId ID de la reserva
     * @return array Stripe session data with payment_url
     * @throws HTTPException Si la reserva no existe, ya está pagada o está cancelada
     */
    public function regeneratePaymentSession(string $reservationId): array
    {
        $reservation = $this->repository->getById($reservationId);

        if (!$reservation) {
            throw new HTTPException('Reservation not found', Response::HTTP_NOT_FOUND);
        }

        if ($reservation->is_paid) {
            throw new HTTPException('Reservation is already paid', Response::HTTP_BAD_REQUEST);
        }

        if ($reservation->status === 'cancelled') {
            throw new HTTPException('Reservation is cancelled', Response::HTTP_BAD_REQUEST);
        }

        // Create new Stripe Checkout Session
        $session = $this->getStripeService()->createCheckoutSession(
            (float) $reservation->total_amount,
            $reservation->email,
            $reservationId,
            'Event Reservation - ' . ($reservation->service_name ?? 'JamWithJamie'),
            (float) ($reservation->gratuity_amount ?? 0)
        );

        $paymentUrl = $session->url;

        // Update stripe session id and payment URL
        $this->repository->update($reservationId, [
            'payment_url'        => $paymentUrl,
            'stripe_session_id'  => $session->id,
        ]);

        return [
            'session_id'  => $session->id,
            'payment_url' => $paymentUrl,
        ];
    }

    /**
     * Handle a completed payment from Stripe webhook
     *
     * @param string $reservationId ID de la reserva
     * @param string $paymentIntentId Stripe Payment Intent ID
     * @return bool
     */
    public function handlePaymentCompleted(string $reservationId, string $paymentIntentId): bool
    {
        $reservation = $this->repository->getById($reservationId);

        if (!$reservation) {
            log_message('error', "Stripe webhook: reservation {$reservationId} not found");
            return false;
        }

        // Avoid processing duplicates
        if ($reservation->is_paid) {
            log_message('info', "Stripe webhook: reservation {$reservationId} already marked as paid");
            return true;
        }

        $result = $this->repository->update($reservationId, [
            'is_paid'                   => true,
            'stripe_payment_intent_id'  => $paymentIntentId,
            'paid_at'                   => date('Y-m-d H:i:s'),
        ]);

        if ($result !== null) {
            $this->sendPaymentConfirmationEmail($result);
            return true;
        }

        return false;
    }

    /**
     * Verify a Stripe Checkout Session and mark reservation as paid if completed
     *
     * @param string $sessionId Stripe Checkout Session ID
     * @return array Result with reservation status
     */
    public function verifyPayment(string $sessionId): array
    {
        $session = $this->getStripeService()->retrieveSession($sessionId);

        $reservationId = $session->metadata->reservation_id ?? null;

        if (!$reservationId) {
            throw new HTTPException('Reservation not found in session', Response::HTTP_NOT_FOUND);
        }

        if ($session->payment_status === 'paid') {
            $this->handlePaymentCompleted($reservationId, $session->payment_intent ?? '');
        }

        return [
            'reservation_id' => $reservationId,
            'payment_status' => $session->payment_status,
            'is_paid'        => $session->payment_status === 'paid',
        ];
    }

    /**
     * Update only confirmation-related fields
     *
     * @param string $id ID de la reserva
     * @param array $data Datos a actualizar
     * @return bool True si se actualizó correctamente
     * @throws HTTPException Si falla la actualización
     */
    public function updateConfirmation(string $id, array $data): bool
    {
        $updated = $this->repository->update($id, $data);

        if (!$updated) {
            throw new HTTPException('Failed to update confirmation details', Response::HTTP_BAD_REQUEST);
        }

        return true;
    }

    /**
     * Construye el contenido HTML del email de pago
     *
     * @param object $reservation Datos de la reserva
     * @param string $paymentUrl URL de pago
     * @param string $reservationId ID de la reserva
     * @return string Contenido HTML del email
     */
    private function buildPaymentEmailContent($reservation, string $paymentUrl, string $reservationId): string
    {
        $eventDate = isset($reservation->event_date) ? date('F j, Y', strtotime($reservation->event_date)) : 'TBD';
        $totalAmount = number_format($reservation->total_amount, 2);

        // Construir URL de confirmación usando la URL del frontend
        $frontendUrl = getenv('app.frontendURL') ?: 'http://localhost:5173';
        $confirmationUrl = rtrim($frontendUrl, '/') . '/confirmation/' . $reservationId;

        // Preparar datos para la vista
        $data = [
            'reservation' => $reservation,
            'paymentUrl' => $paymentUrl,
            'confirmationUrl' => $confirmationUrl,
            'eventDate' => $eventDate,
            'totalAmount' => $totalAmount
        ];

        // Cargar la vista y capturar el output
        return view('emails/payment_notification', $data);
    }

    /**
     * Envía email de confirmación al cliente cuando se crea una reserva desde el Home
     *
     * @param object $reservation Reserva creada (con JOINs: full_name, email, service_name)
     * @return void
     */
    public function sendPaymentConfirmationEmail($reservation): void
    {
        if (empty($reservation->email)) {
            return;
        }

        $eventDate = isset($reservation->event_date) ? date('F j, Y', strtotime($reservation->event_date)) : 'TBD';

        $totalDurationLabel = $this->formatDurationLabel($reservation->duration_hours ?? 0);
        $totalDurationRow = $this->buildDurationRow($reservation->duration_hours ?? 0);

        $promoBlockPc    = '';
        $discountBlockPc = '';
        if (!empty($reservation->promo_code)) {
            $promoBlockPc    = '<tr><td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Promo Code</td><td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;"><span style="background-color: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 13px;">' . esc($reservation->promo_code) . '</span></td></tr>';
            $discountBlockPc = '<tr><td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Discount</td><td style="padding: 12px 16px; font-size: 14px; font-weight: 700; color: #059669; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">-$' . number_format((float)($reservation->discount_amount ?? 0), 2) . '</td></tr>';
        }

        $gratuityAmount = (float) ($reservation->gratuity_amount ?? 0);
        $gratuityRow    = $gratuityAmount > 0
            ? '<tr><td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Gratuity / Tip</td><td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;">$' . number_format($gratuityAmount, 2) . '</td></tr>'
            : '';
        $totalPaid = number_format((float) $reservation->total_amount + $gratuityAmount, 2);

        $templateVars = [
            'customer_name'      => strtok(trim($reservation->full_name ?? ''), ' '),
            'reservation_id'     => $reservation->id,
            'service_name'       => $reservation->service_name ?? '',
            'event_date'         => $eventDate,
            'event_time'         => $reservation->event_time ?? 'To be confirmed',
            'event_address'      => $reservation->event_address ?? 'To be confirmed',
            'children_count'     => $reservation->children_age_range ?: ($reservation->children_count ?? ''),
            'total_duration_row' => $totalDurationRow,
            'duration_hours'     => $totalDurationLabel,
            'total_duration'     => $totalDurationLabel,
            'total_duration_label' => $totalDurationLabel,
            'promo_code_row'     => $promoBlockPc,
            'discount_row'       => $discountBlockPc,
            'gratuity_row'       => $gratuityRow,
            'total_amount'       => number_format($reservation->total_amount, 2),
            'total_paid'         => $totalPaid,
            '_reservation'       => $reservation,
        ];

        $rendered = $this->emailTemplateService->render('payment_confirmation', $templateVars);

        try {
            $this->emailService->sendEmail($reservation->email, $rendered['subject'], $rendered['body']);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to send payment confirmation email: ' . $e->getMessage());
        }
    }

    public function sendConfirmationEmail($reservation): void
    {
        if (empty($reservation->email)) {
            return;
        }

        $eventDate = isset($reservation->event_date) ? date('F j, Y', strtotime($reservation->event_date)) : 'TBD';

        $promoBlockRc    = '';
        $discountBlockRc = '';
        if (!empty($reservation->promo_code)) {
            $promoBlockRc    = '<tr><td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Promo Code</td><td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;"><span style="background-color: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 13px;">' . esc($reservation->promo_code) . '</span></td></tr>';
            $discountBlockRc = '<tr><td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Discount</td><td style="padding: 12px 16px; font-size: 14px; font-weight: 700; color: #059669; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">-$' . number_format((float)($reservation->discount_amount ?? 0), 2) . '</td></tr>';
        }

        $totalDurationLabel = $this->formatDurationLabel($reservation->duration_hours ?? 0);

        $templateVars = [
            'customer_name'      => strtok(trim($reservation->full_name ?? ''), ' '),
            'reservation_id'     => $reservation->id,
            'service_name'       => $reservation->service_name ?? '',
            'event_date'         => $eventDate,
            'event_time'         => $reservation->event_time ?? 'To be confirmed',
            'event_address'      => $reservation->event_address ?? 'To be confirmed',
            'children_count'     => $reservation->children_age_range ?: ($reservation->children_count ?? ''),
            'total_duration_row' => $this->buildDurationRow($reservation->duration_hours ?? 0),
            'duration_hours'     => $totalDurationLabel,
            'total_duration'     => $totalDurationLabel,
            'total_duration_label' => $totalDurationLabel,
            'promo_code_row'     => $promoBlockRc,
            'discount_row'       => $discountBlockRc,
            'total_amount'       => number_format($reservation->total_amount, 2),
            '_reservation'       => $reservation,
        ];

        $rendered = $this->emailTemplateService->render('reservation_confirmation', $templateVars);

        try {
            $this->emailService->sendEmail($reservation->email, $rendered['subject'], $rendered['body']);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to send confirmation email: ' . $e->getMessage());
        }
    }

    /**
     * Guarda el monto de gratuity para una reserva antes de redirigir a Stripe.
     *
     * @param string $id Reservation ID
     * @param float $amount Gratuity amount (0 = no tip)
     * @return void
     */
    public function updateGratuity(string $id, float $amount): void
    {
        $reservation = $this->repository->getById($id);

        if (!$reservation) {
            throw new HTTPException('Reservation not found', Response::HTTP_NOT_FOUND);
        }

        if ($reservation->is_paid) {
            throw new HTTPException('Reservation is already paid', Response::HTTP_BAD_REQUEST);
        }

        $this->repository->update($id, [
            'gratuity_amount' => max(0, round($amount, 2)),
        ]);
    }

    /**
     * Envía recordatorio semanal a reservas con evento en 7 días.
     * Marca week_reminder_sent = 1 para evitar duplicados.
     *
     * @return int Cantidad de emails enviados
     */
    public function sendWeekReminders(): int
    {
        $reservations = $this->repository->getUpcomingForReminder();
        $sent = 0;

        foreach ($reservations as $reservation) {
            if (empty($reservation->email)) {
                continue;
            }

            $templateVars = $this->buildReservationEmailVariables($reservation);

            try {
                $rendered = $this->emailTemplateService->render('week_reminder', $templateVars);
                $this->emailService->sendEmail($reservation->email, $rendered['subject'], $rendered['body']);
                $this->repository->update($reservation->id, ['week_reminder_sent' => 1]);
                $sent++;
            } catch (\Throwable $e) {
                log_message('error', "Failed to send week reminder to {$reservation->email}: " . $e->getMessage());
            }
        }

        return $sent;
    }

    private function buildDurationRow(float|int|string $durationHours): string
    {
        $label = $this->formatDurationLabel($durationHours);
        if ($label === '') {
            return '';
        }

        return '<tr>'
            . '<td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Total Duration</td>'
            . '<td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">' . $label . '</td>'
            . '</tr>';
    }

    private function formatDurationLabel(float|int|string $durationHours): string
    {
        $hours = floatval($durationHours);
        if ($hours <= 0) {
            return '';
        }

        $wholeH = (int) floor($hours);
        $mins   = (int) round(($hours - $wholeH) * 60);

        if ($mins === 60) {
            $wholeH++;
            $mins = 0;
        }

        if ($mins === 0) {
            $label = $wholeH . ' hour' . ($wholeH !== 1 ? 's' : '');
        } elseif ($wholeH === 0) {
            $label = $mins . ' minute' . ($mins !== 1 ? 's' : '');
        } else {
            $label = $wholeH . ' hour' . ($wholeH !== 1 ? 's' : '') . ' ' . $mins . ' minute' . ($mins !== 1 ? 's' : '');
        }

        return $label;
    }
}
