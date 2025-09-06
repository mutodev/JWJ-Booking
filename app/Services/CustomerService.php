<?php

namespace App\Services;

use App\Repositories\CustomerRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;

class CustomerService
{
    protected $repository;

    public function __construct()
    {
        $this->repository = new CustomerRepository();
    }

    /**
     * Obtener cliente por ID
     */
    public function getById(string $id)
    {
        $customer = $this->repository->getById($id);

        if (!$customer) {
            throw new HTTPException(lang('Customer.notFound'), Response::HTTP_NOT_FOUND);
        }

        return $customer;
    }

    /**
     * Obtener todos los clientes
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Buscar clientes por nombre
     */
    public function searchByName(string $name): array
    {
        return $this->repository->searchByName($name);
    }

    /**
     * Crear nuevo cliente (validando email único)
     */
    public function create(array $data): string
    {
        $existing = $this->repository->getByEmail($data['email']);

        if ($existing) {
            throw new HTTPException(lang('Customer.alreadyExists', [$data['email']]), Response::HTTP_CONFLICT);
        }

        $id = $this->repository->create($data);

        if (!$id) {
            throw new HTTPException(lang('Customer.createFailed'), Response::HTTP_BAD_REQUEST);
        }

        return $id;
    }

    /**
     * Actualizar cliente
     */
    public function update(string $id, array $data): bool
    {
        $updated = $this->repository->update($id, $data);

        if (!$updated) {
            throw new HTTPException(lang('Customer.updateFailed'), Response::HTTP_BAD_REQUEST);
        }

        return true;
    }

    /**
     * Eliminar cliente
     */
    public function delete(string $id): bool
    {
        $deleted = $this->repository->delete($id);

        if (!$deleted) {
            throw new HTTPException(lang('Customer.deleteFailed'), Response::HTTP_BAD_REQUEST);
        }

        return true;
    }
}
