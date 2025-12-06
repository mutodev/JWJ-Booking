<?php

namespace App\Services;

use App\Repositories\TypeAddonRepository;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\Exceptions\HTTPException;

class TypeAddonService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new TypeAddonRepository();
    }

    public function getAll()
    {
        return $this->repo->getAll();
    }

    public function getAllActive()
    {
        return $this->repo->getAllActive();
    }

    public function getById(string $id)
    {
        $typeAddon = $this->repo->getById($id);
        if (!$typeAddon) {
            throw new HTTPException('Type Addon not found', Response::HTTP_NOT_FOUND);
        }
        return $typeAddon;
    }

    public function create(array $data)
    {
        return $this->repo->create($data);
    }

    public function update(string $id, array $data)
    {
        $typeAddon = $this->repo->getById($id);
        if (!$typeAddon) {
            throw new HTTPException('Type Addon not found', Response::HTTP_NOT_FOUND);
        }
        return $this->repo->update($id, $data);
    }

    public function delete(string $id)
    {
        $typeAddon = $this->repo->getById($id);
        if (!$typeAddon) {
            throw new HTTPException('Type Addon not found', Response::HTTP_NOT_FOUND);
        }
        return $this->repo->softDelete($id);
    }
}
