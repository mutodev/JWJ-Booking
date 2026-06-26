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
     * Actualizar reserva
     */
    public function update(string $id, array $data)
    {
        $this->model->update($id, $data);
        return $this->getById($id);
    }

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
                reservations.event_address,
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
