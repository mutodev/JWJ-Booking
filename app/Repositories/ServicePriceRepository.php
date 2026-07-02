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
                counties.name as county_name,
                metropolitan_areas.name as area_name
            ")
            ->join("services", "services.id = service_prices.service_id", "left")
            ->join("counties", "counties.id = service_prices.county_id", "left")
            ->join("metropolitan_areas", "metropolitan_areas.id = counties.metropolitan_area_id", "left")
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
     * Obtener un precio por servicio y condado
     */
    public function getByServiceAndCounty(string $serviceId, string $countyId)
    {
        return $this->model
            ->where('service_id', $serviceId)
            ->where('county_id', $countyId)
            ->orderBy('amount')
            ->findAll();
    }

    /**
     * Obtener por combinación única (service_id + county_id + performers_count).
     */
    public function getByUnique(string $serviceId, string $countyId, int $performersCount, bool $withDeleted = false): ?ServicePrice
    {
        $builder = $this->model->where([
            'service_id' => $serviceId,
            'county_id'  => $countyId,
            'performers_count' => $performersCount,
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

    /**
     * Actualizar el precio base de todos los counties de un servicio.
     */
    public function updateBasePriceByService(string $serviceId, float $amount): int
    {
        $db = \Config\Database::connect();
        $db->table('service_prices')
            ->where('service_id', $serviceId)
            ->where('deleted_at IS NULL')
            ->update(['amount' => $amount, 'updated_at' => date('Y-m-d H:i:s')]);

        return $db->affectedRows();
    }

    /**
     * Contar service prices activos de un county.
     */
    public function countByCounty(string $countyId): int
    {
        return $this->model
            ->where('county_id', $countyId)
            ->where('deleted_at IS NULL')
            ->countAllResults();
    }

    /**
     * Actualizar travel fee de todos los service prices de un county.
     */
    public function updateTravelFeeByCounty(string $countyId, float $travelFee): int
    {
        $db = \Config\Database::connect();
        $db->table('service_prices')
            ->where('county_id', $countyId)
            ->where('deleted_at IS NULL')
            ->update(['travel_fee' => $travelFee, 'updated_at' => date('Y-m-d H:i:s')]);

        return $db->affectedRows();
    }

    /**
     * Obtener los precios del servicio por condado
     *
     * @return ServicePrice[]
     */
    public function getAllByCounty($countyId)
    {
        return $this->model
            ->select("
                service_prices.id,
                service_prices.county_id,
                service_prices.performers_count,
                service_prices.amount,
                service_prices.travel_fee,
                service_prices.extra_child_fee,
                service_prices.min_duration_hours,
                service_prices.range_age,
                service_prices.notes,
                services.name,
                services.description,
                services.img
            ")
            ->join("services", "services.id = service_prices.service_id", "left")
            ->where('service_prices.county_id', $countyId)
            ->where('service_prices.is_available', true)
            ->where('services.is_active', true)
            ->findAll();
    }

    /**
     * Obtener los precios del servicio por metropolitan area
     *
     * @return ServicePrice[]
     */
    public function getAllByMetropolitanArea($metropolitanAreaId)
    {
        return $this->model
            ->select("
                service_prices.id,
                service_prices.county_id,
                service_prices.performers_count,
                service_prices.amount,
                service_prices.travel_fee,
                service_prices.extra_child_fee,
                service_prices.range_age,
                service_prices.notes,
                services.name,
                services.id as service_id,
                services.description,
                services.img
            ")
            ->join("services", "services.id = service_prices.service_id", "left")
            ->join("counties", "counties.id = service_prices.county_id", "left")
            ->where('counties.metropolitan_area_id', $metropolitanAreaId)
            ->where('counties.is_active', true)
            ->where('service_prices.is_available', true)
            ->where('services.is_active', true)
            ->groupBy('service_prices.id')
            ->findAll();
    }

    /**
     * Obtener los precios del servicio por zipcode.
     * Si el zipcode tiene override_county_id, usa ese condado para el pricing
     * en vez del condado real derivado de city -> county.
     *
     * @return ServicePrice[]
     */
    public function getAllByZipcode($zipcodeId)
    {
        $db = \Config\Database::connect();

        // Resolve effective county: override if set, otherwise follow city → county
        $row = $db->query("
            SELECT COALESCE(z.override_county_id, ci.county_id) AS effective_county_id
            FROM zipcodes z
            JOIN cities ci ON ci.id = z.city_id
            WHERE z.id = ?
            LIMIT 1
        ", [$zipcodeId])->getRow();

        if (!$row || empty($row->effective_county_id)) {
            return [];
        }

        $effectiveCountyId = $row->effective_county_id;

        return $this->model
            ->select("
                service_prices.id,
                service_prices.county_id,
                service_prices.performers_count,
                service_prices.amount,
                service_prices.travel_fee,
                service_prices.extra_child_fee,
                service_prices.range_age,
                service_prices.notes,
                services.name,
                services.id as service_id,
                services.description,
                services.img,
                services.duration_hours
            ")
            ->join("services", "services.id = service_prices.service_id", "left")
            ->join("counties", "counties.id = service_prices.county_id", "left")
            ->where('service_prices.county_id', $effectiveCountyId)
            ->where('counties.is_active', true)
            ->where('service_prices.is_available', true)
            ->where('services.is_active', true)
            ->groupBy('service_prices.id')
            ->findAll();
    }
}
