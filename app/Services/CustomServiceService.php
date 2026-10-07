<?php

namespace App\Services;

use App\Repositories\CustomServiceRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;

/**
 * Lógica de negocio del catálogo de servicios personalizados.
 */
class CustomServiceService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new CustomServiceRepository();
    }

    public function getAll()
    {
        return $this->repo->getAll();
    }

    public function getAllActive()
    {
        return $this->repo->getAllActive();
    }

    /**
     * @throws HTTPException
     */
    public function getById(string $id)
    {
        $customService = $this->repo->getById($id);
        if (!$customService) {
            throw new HTTPException(lang('App.custom_service_not_found'), Response::HTTP_NOT_FOUND);
        }
        return $customService;
    }

    /**
     * Crea un servicio personalizado.
     *
     * @throws HTTPException
     */
    public function create(array $data, string $userLabel)
    {
        $payload = $this->normalize($data, true);
        $payload['created_by'] = mb_substr($userLabel, 0, 255);
        $payload['updated_by'] = $payload['created_by'];

        $id = $this->repo->create($payload);
        if (!$id) {
            throw new HTTPException(lang('App.custom_service_create_failed'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->repo->getById($id);
    }

    /**
     * Actualiza solo los campos enviados.
     *
     * @throws HTTPException
     */
    public function update(string $id, array $data, string $userLabel)
    {
        $this->getById($id);

        $payload = $this->normalize($data, false);
        $payload['updated_by'] = mb_substr($userLabel, 0, 255);

        $this->repo->update($id, $payload);

        return $this->repo->getById($id);
    }

    /**
     * Soft delete.
     *
     * @throws HTTPException
     */
    public function delete(string $id)
    {
        $this->getById($id);
        return $this->repo->softDelete($id);
    }

    /**
     * Valida y limpia los campos permitidos.
     * En creación name y price son obligatorios; en actualización solo se validan si vienen.
     *
     * @throws HTTPException
     */
    private function normalize(array $data, bool $isCreate): array
    {
        $payload = [];

        if ($isCreate || array_key_exists('name', $data)) {
            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                throw new HTTPException(lang('App.custom_service_name_required'), Response::HTTP_BAD_REQUEST);
            }
            if (mb_strlen($name) > 255) {
                throw new HTTPException(lang('App.custom_service_name_too_long'), Response::HTTP_BAD_REQUEST);
            }
            $payload['name'] = $name;
        }

        if (array_key_exists('detail', $data)) {
            $detail = trim((string) ($data['detail'] ?? ''));
            $payload['detail'] = $detail === '' ? null : $detail;
        }

        if ($isCreate || array_key_exists('price', $data)) {
            $price = $data['price'] ?? null;
            if ($price === null || $price === '' || !is_numeric($price) || (float) $price < 0) {
                throw new HTTPException(lang('App.custom_service_invalid_price'), Response::HTTP_BAD_REQUEST);
            }
            $payload['price'] = round((float) $price, 2);
        }

        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        } elseif ($isCreate) {
            $payload['is_active'] = 1;
        }

        return $payload;
    }
}
