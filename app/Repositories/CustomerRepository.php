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
     * Obtener clientes por array de IDs
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) return [];
        return $this->model->whereIn('id', $ids)->findAll();
    }

    /**
     * Obtener cliente por email
     */
    public function getByEmail(string $email): ?Customer
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Obtener cliente soft-deleted por email
     */
    public function getSoftDeletedByEmail(string $email): ?Customer
    {
        return $this->model->onlyDeleted()->where('email', $email)->first();
    }

    /**
     * Restaurar cliente soft-deleted actualizando sus campos y poniendo deleted_at = null
     */
    public function restore(string $id, array $data): bool
    {
        return $this->model->db->table('customers')
            ->where('id', $id)
            ->update(array_merge($data, ['deleted_at' => null, 'updated_at' => date('Y-m-d H:i:s')]));
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

    /**
     * Eliminar múltiples clientes (soft delete)
     */
    public function bulkDelete(array $ids): int
    {
        if (empty($ids)) return 0;
        $this->model->whereIn('id', $ids)->delete();
        return $this->model->db->affectedRows();
    }
}
