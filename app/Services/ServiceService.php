<?php

namespace App\Services;

use App\Repositories\ServiceRepository;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\Exceptions\HTTPException;

class ServiceService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new ServiceRepository();
    }

    /**
     * Obtiene todos los servicios disponibles.
     *
     * @return array
     */
    public function getAll()
    {
        return $this->repo->getAll();
    }

    /**
     * Obtiene todos los servicios activos.
     *
     * @return array
     */
    public function getAllActive()
    {
        return $this->repo->getAllActive();
    }

    /**
     * Obtiene un servicio por ID.
     *
     * @param string $id
     * @return object
     * @throws HTTPException
     */
    public function getById(string $id)
    {
        $service = $this->repo->getById($id);
        if (!$service) {
            throw new HTTPException(
                lang('Service.notFound'),
                Response::HTTP_NOT_FOUND
            );
        }
        return $service;
    }

    /**
     * Crea un nuevo servicio.
     * Si existe y está eliminado → se restaura.
     * Si existe y está activo → lanza conflicto.
     *
     * @param array $data
     * @return string|bool
     * @throws HTTPException
     */
    public function create(array $data)
    {
        $existing = $this->repo->getByName($data['name'], true);

        // Caso 1: existe y está activo → conflicto
        if ($existing && $existing->deleted_at === null) {
            throw new HTTPException(
                lang('Service.alreadyExists', [$data['name']]),
                Response::HTTP_CONFLICT
            );
        }

        // Caso 2: existe pero soft-deleted → restaurar y actualizar datos
        if ($existing && $existing->deleted_at !== null) {
            $this->repo->restore($existing->id);
            // Combinar datos actuales con los nuevos para evitar array vacío
            $mergedData = array_merge($existing->toRawArray(), $data);

            return $this->repo->update($existing->id, $mergedData);
        }

        // Caso 3: no existe → crear nuevo
        return $this->repo->create($data);
    }

    /**
     * Actualiza un servicio existente.
     *
     * @param string $id
     * @param array $data
     * @return bool
     * @throws HTTPException
     */
    public function update(string $id, array $data)
    {
        $service = $this->repo->getById($id);
        if (!$service) {
            throw new HTTPException(
                lang('Service.notFound'),
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->repo->update($id, $data);
    }

    /**
     * Elimina un servicio usando soft delete.
     *
     * @param string $id
     * @return bool
     * @throws HTTPException
     */
    public function delete(string $id)
    {
        $service = $this->repo->getById($id);
        if (!$service) {
            throw new HTTPException(
                lang('Service.notFound'),
                Response::HTTP_NOT_FOUND
            );
        }
        return $this->repo->softDelete($id);
    }

    /**
     * Actualiza un servicio con manejo de imagen
     */
    public function updateWithImage(string $id, $request)
    {
        $service = $this->repo->getById($id);
        if (!$service) {
            throw new HTTPException(
                lang('Service.notFound'),
                Response::HTTP_NOT_FOUND
            );
        }

        $data = [];
        $post = $request->getPost();

        if (isset($post['name'])) {
            $data['name'] = trim($post['name']);
        }
        if (isset($post['description'])) {
            $data['description'] = trim($post['description']);
        }
        if (isset($post['is_active'])) {
            $data['is_active'] = filter_var($post['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        // Procesar imagen si existe
        $image = $request->getFile('image');
        if ($image && $image->isValid() && !$image->hasMoved()) {
            $uploadPath = FCPATH . 'img';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $newName = $image->getRandomName();
            $image->move($uploadPath, $newName);
            $data['img'] = '/img/' . $newName;
        }

        return $this->repo->update($id, $data);
    }
}
