<?php

namespace App\Services;

use App\Repositories\ServicePriceRepository;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\Exceptions\HTTPException;

class ServicePriceService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new ServicePriceRepository();
    }

    public function getAll()
    {
        return $this->repo->getAll();
    }

    public function getById(string $id)
    {
        $price = $this->repo->getById($id);
        if (!$price) {
            throw new HTTPException(lang('ServicePrice.notFound'), Response::HTTP_NOT_FOUND);
        }
        return $price;
    }

    public function create(array $data)
    {
        $existing = $this->repo->getByUnique($data['service_id'], $data['county_id'], $data['price_type'], true);

        // Existe y activo → conflicto
        if ($existing && $existing->deleted_at === null) {
            throw new HTTPException(
                lang('ServicePrice.alreadyExists'),
                Response::HTTP_CONFLICT
            );
        }

        // Existe pero soft-deleted → restaurar y actualizar
        if ($existing && $existing->deleted_at !== null) {
            $this->repo->restore($existing->id);
            if (!empty($data)) {
                $this->repo->update($existing->id, $data);
            }
            return $existing->id;
        }

        // No existe → crear
        return $this->repo->create($data);
    }

    public function update(string $id, array $data)
    {
        $price = $this->repo->getById($id);
        if (!$price) {
            throw new HTTPException(lang('ServicePrice.notFound'), Response::HTTP_NOT_FOUND);
        }

        return $this->repo->update($id, $data);
    }

    public function delete(string $id)
    {
        $price = $this->repo->getById($id);
        if (!$price) {
            throw new HTTPException(lang('ServicePrice.notFound'), Response::HTTP_NOT_FOUND);
        }

        return $this->repo->softDelete($id);
    }
}
