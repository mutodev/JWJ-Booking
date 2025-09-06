<?php

namespace App\Repositories;

use App\Models\CustomerModel;
use App\Entities\Customer;

class CustomerRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = new CustomerModel();
    }

    /**
     * Obtener cliente por ID
     */
    public function getById(string $id): ?Customer
    {
        return $this->model->find($id);
    }

    /**
     * Obtener todos los clientes
     */
    public function getAll(): array
    {
        return $this->model->findAll();
    }

    /**
     * Buscar clientes por nombre (like)
     */
    public function searchByName(string $name): array
    {
        return $this->model
            ->like('full_name', $name)
            ->findAll();
    }

    /**
     * Obtener cliente por email
     */
    public function getByEmail(string $email): ?Customer
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Crear cliente
     */
    public function create(array $data): ?string
    {
        if ($this->model->insert($data, true)) {
            return $this->model->getInsertID();
        }
        return null;
    }

    /**
     * Actualizar cliente
     */
    public function update(string $id, array $data): bool
    {
        return $this->model->update($id, $data);
    }

    /**
     * Eliminar cliente
     */
    public function delete(string $id): bool
    {
        return $this->model->delete($id);
    }
}
