<?php

namespace App\Repositories;

use App\Entities\TypeAddon;
use App\Models\TypeAddonModel;

class TypeAddonRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = new TypeAddonModel();
    }

    public function getAll()
    {
        return $this->model->findAll();
    }

    public function getAllActive()
    {
        return $this->model->where('is_active', true)->findAll();
    }

    public function getById(string $id): ?TypeAddon
    {
        return $this->model->where('id', $id)->first();
    }

    public function create(array $data): string|bool
    {
        return $this->model->insert($data, true);
    }

    public function update(string $id, array $data): bool
    {
        return $this->model->update($id, $data);
    }

    public function softDelete(string $id): bool
    {
        return $this->model->delete($id);
    }
}
