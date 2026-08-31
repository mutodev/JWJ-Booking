<?php

namespace App\Repositories;

use App\Models\ReservationModel;

class ReservationRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = new ReservationModel();
    }

    /**
     * Obtener todas las reservas con información completa
     */
    public function getAll()
    {
        return $this->model
            ->select("
                reservations.*,
                reservations.customer_notes as additional_notes,
                customers.full_name,
                customers.email,
                customers.phone,
                service_prices.amount as service_amount,
                services.name as service_name,
                counties.name as county_name,
                cities.name as city_name,
                zipcodes.zipcode
            ")
            ->join("customers", "customers.id = reservations.customer_id", "left")
            ->join("service_prices", "service_prices.id = reservations.service_price_id", "left")
            ->join("services", "services.id = service_prices.service_id", "left")
            ->join("zipcodes", "zipcodes.id = reservations.zipcode_id", "left")
            ->join("cities", "cities.id = zipcodes.city_id", "left")
            ->join("counties", "counties.id = cities.county_id", "left")
            ->orderBy("reservations.created_at", "DESC")
            ->findAll();
    }

    /**
     * Obtener reserva por ID con información completa
     */
    public function getById(string $id)
    {
        return $this->model
            ->select("
                reservations.*,
                reservations.customer_notes as additional_notes,
                customers.full_name,
                customers.email,
                customers.phone,
                service_prices.amount as service_amount,
                services.name as service_name,
                counties.name as county_name,
                cities.name as city_name,
                zipcodes.zipcode
            ")
            ->join("customers", "customers.id = reservations.customer_id", "left")
            ->join("service_prices", "service_prices.id = reservations.service_price_id", "left")
            ->join("services", "services.id = service_prices.service_id", "left")
            ->join("zipcodes", "zipcodes.id = reservations.zipcode_id", "left")
            ->join("cities", "cities.id = zipcodes.city_id", "left")
            ->join("counties", "counties.id = cities.county_id", "left")
            ->where("reservations.id", $id)
            ->first();
    }

    /**
     * Crear una reserva
     */
    public function create(array $data)
    {
        $this->model->insert($data);
        return $this->getById($this->model->getInsertID());
    }

    /**
     * Actualizar reserva (uso interno de confianza).
     *
     * Los servicios que escriben importes, estado de pago o balances
     * (recalculateTotals, handlePaymentCompleted, applyPromoCode,
     * regeneratePaymentSession, updateGratuity) llaman a este método
     * directamente. NUNCA debe recibir input crudo del request: para eso está
     * updateEditable().
     */
    public function update(string $id, array $data)
    {
        $this->model->update($id, $data);
        return $this->getById($id);
    }

    /**
     * B6 mass-assignment guard for PUT /api/reservations/{id}.
     *
     * The generic admin edit endpoint routes here. Only non-financial,
     * admin-editable columns survive the whitelist; every price / balance /
     * payment-identifier column (base_price, addons_total, extra_children_fee,
     * travel_fee, expedite_fee, expedition_fee, discount_amount, promo_code,
     * total_amount, amount_paid, balance_due, gratuity_amount, paid_at,
     * stripe_*, payment_url) is written exclusively by the dedicated service
     * methods and can never be forged from the request body.
     */
    public function updateEditable(string $id, array $data)
    {
        $clean = array_intersect_key($data, array_flip(self::EDITABLE_FIELDS));
        if (!empty($clean)) {
            $this->model->update($id, $clean);
        }
        return $this->getById($id);
    }

    /**
     * Columns an authenticated admin may set through PUT /api/reservations/{id}.
     * is_paid / is_invoiced / status stay editable because the admin edit form
     * legitimately toggles them; the financial columns are deliberately absent.
     */
    private const EDITABLE_FIELDS = [
        'service_price_id',
        'zipcode_id',
        'event_address',
        'event_date',
        'event_time',
        'entertainment_start_time',
        'arrival_parking_instructions',
        'children_count',
        'children_age_range',
        'birthday_child_name',
        'birthday_child_age',
        'song_requests',
        'sing_happy_birthday',
        'performers_count',
        'duration_hours',
        'status',
        'is_invoiced',
        'is_paid',
        'customer_confirmed',
        'customer_notes',
        'internal_notes',
        'event_type',
        'description',
    ];

    /**
     * Eliminar (soft delete)
     */
    public function delete(string $id)
    {
        return $this->model->delete($id);
    }

    /**
     * Eliminar reservas con event_date anterior o igual a una fecha dada (soft delete)
     *
     * @param string $cutoffDate Fecha límite en formato Y-m-d
     * @return int Cantidad de reservas eliminadas
     */
    /**
     * Reservas con evento en exactamente 7 días que aún no recibieron reminder
     */
    public function getUpcomingForReminder(): array
    {
        $targetDate = date('Y-m-d', strtotime('+7 days'));

        return $this->model
            ->select("
                reservations.id,
                reservations.event_date,
                reservations.event_time,
                reservations.entertainment_start_time,
                reservations.event_address,
                reservations.performers_count,
                reservations.status,
                customers.full_name,
                customers.email,
                services.name as service_name
            ")
            ->join("customers", "customers.id = reservations.customer_id", "left")
            ->join("service_prices", "service_prices.id = reservations.service_price_id", "left")
            ->join("services", "services.id = service_prices.service_id", "left")
            ->where("reservations.event_date", $targetDate)
            ->where("reservations.week_reminder_sent", 0)
            ->whereNotIn("reservations.status", ['cancelled'])
            ->findAll();
    }

    public function deleteBeforeDate(string $cutoffDate): int
    {
        $reservations = $this->model
            ->where('event_date <=', $cutoffDate)
            ->findAll();

        $count = 0;
        foreach ($reservations as $reservation) {
            if ($this->model->delete($reservation->id)) {
                $count++;
            }
        }

        return $count;
    }

    public function softDeleteByCustomerId(string $customerId): void
    {
        $this->model->where('customer_id', $customerId)->delete();
    }

    public function softDeleteByCustomerIds(array $customerIds): void
    {
        if (empty($customerIds)) return;
        $this->model->whereIn('customer_id', $customerIds)->delete();
    }
}
