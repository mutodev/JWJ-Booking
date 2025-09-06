<?php

namespace App\Repositories;

use App\Entities\ServicePrice;
use App\Models\ServicePriceModel;

class ServicePriceRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = new ServicePriceModel();
    }

    /**
     * Obtener todos los precios de servicios.
     *
     * @return ServicePrice[]
     */
    public function getAll()
    {
        return $this->model
            ->select("
                service_prices.*,
                services.name as service_name,
                counties.name as county_name
            ")
            ->join("services", "services.id = service_prices.service_id", "left")
            ->join("counties", "counties.id = service_prices.county_id", "left")
            ->findAll();
    }

    /**
     * Obtener un precio por ID.
     */
    public function getById(string $id): ?ServicePrice
    {
        return $this->model->where('id', $id)->first();
    }

    /**
     * Obtener por combinación única (service_id + county_id + price_type).
     */
    public function getByUnique(string $serviceId, string $countyId, string $priceType, bool $withDeleted = false): ?ServicePrice
    {
        $builder = $this->model->where([
            'service_id' => $serviceId,
            'county_id'  => $countyId,
            'price_type' => $priceType,
        ]);

        if ($withDeleted) {
            $builder = $builder->withDeleted();
        }

        return $builder->first();
    }

    /**
     * Crear un nuevo registro.
     */
    public function create(array $data): string|bool
    {
        return $this->model->insert($data, true);
    }

    /**
     * Actualizar un registro por ID.
     */
    public function update(string $id, array $data): bool
    {
        return $this->model->update($id, $data);
    }

    /**
     * Soft delete.
     */
    public function softDelete(string $id): bool
    {
        return $this->model->delete($id);
    }

    /**
     * Restaurar registro.
     */
    public function restore(string $id): bool
    {
        return $this->model->withDeleted()
            ->where('id', $id)
            ->set(['deleted_at' => null])
            ->update();
    }
}
