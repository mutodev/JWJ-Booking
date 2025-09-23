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
}
