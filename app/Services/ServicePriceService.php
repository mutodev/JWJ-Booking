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

    /**
     * Obtener los precios del servicio por condado 
     * @param $countyId
    */
    public function getAllByCounty($countyId)
    {
        return $this->repo->getAllByCounty($countyId);
    }

    public function getById(string $id)
    {
        $price = $this->repo->getById($id);
        if (!$price) {
            throw new HTTPException(lang('ServicePrice.notFound'), Response::HTTP_NOT_FOUND);
        }
        return $price;
    }

    public function getByServiceAndCounty(string $serviceId, string $countyId)
    {
        return $this->repo->getByServiceAndCounty($serviceId, $countyId);
    }

    public function create(array $data)
    {
        $existing = $this->repo->getByUnique($data['service_id'], $data['county_id'], $data['performers_count'], true);

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

    /**
     * Crear un nuevo precio de servicio con manejo de imagen
     *
     * @param \CodeIgniter\HTTP\IncomingRequest $request
     * @return mixed
     * @throws HTTPException
     */
    public function createWithImage($request)
    {
        $data = [];

        // Verificar Content-Type para determinar cómo obtener datos
        $contentType = $request->getHeaderLine('Content-Type');

        if (strpos($contentType, 'application/json') !== false) {
            // Si es JSON, intentar parsearlo de forma segura
            try {
                $json = $request->getJSON(true);
                if ($json) {
                    $data = $json;
                }
            } catch (\Exception $e) {
                // Si falla el JSON, continuar sin datos JSON
                log_message('debug', 'JSON parse failed: ' . $e->getMessage());
            }
        }

        // Si no hay datos JSON o es multipart, usar POST
        if (empty($data)) {
            $data = $request->getPost() ?: [];

            // Convertir arrays a strings (problema común con multiselect)
            foreach ($data as $key => $value) {
                if (is_array($value) && !empty($value)) {
                    $data[$key] = $value[0]; // Tomar primer elemento
                }
            }
        }

        // Limpiar y validar campos
        $data = $this->sanitizeFormData($data);

        // Procesar imagen si existe
        $image = $request->getFile('image');
        if ($image && $image->isValid() && !$image->hasMoved()) {
            $imagePath = $this->handleImageUpload($image);
            $data['img'] = $imagePath;
        }

        // Establecer is_available por defecto
        if (!isset($data['is_available'])) {
            $data['is_available'] = true;
        }

        return $this->createDirectly($data);
    }

    /**
     * Sanitiza y valida los datos del formulario
     *
     * @param array $data
     * @return array
     */
    private function sanitizeFormData(array $data): array
    {
        $sanitized = [];
        $allowedFields = [
            'service_id', 'county_id', 'performers_count', 'amount',
            'is_available', 'notes', 'extra_child_fee', 'range_age'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];

                // Manejar valores vacíos
                if ($value === '' || $value === null) {
                    continue;
                }

                // Convertir según tipo de campo
                switch ($field) {
                    case 'performers_count':
                        $sanitized[$field] = (int)$value;
                        break;
                    case 'amount':
                    case 'extra_child_fee':
                        $sanitized[$field] = (float)$value;
                        break;
                    case 'is_available':
                        $sanitized[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        break;
                    default:
                        $sanitized[$field] = trim((string)$value);
                        break;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Maneja la carga y validación de la imagen según documentación oficial CI4
     *
     * @param \CodeIgniter\HTTP\Files\UploadedFile $image
     * @return string Ruta de la imagen
     * @throws HTTPException
     */
    private function handleImageUpload($image): string
    {
        // Validar que es una imagen válida usando métodos CI4
        if (!$image->isValid()) {
            throw new HTTPException(
                'Invalid image file: ' . $image->getErrorString(),
                Response::HTTP_BAD_REQUEST
            );
        }

        // Validar tipo MIME
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($image->getMimeType(), $allowedTypes)) {
            throw new HTTPException(
                'Invalid image type. Only JPEG, PNG and GIF are allowed.',
                Response::HTTP_BAD_REQUEST
            );
        }

        // Validar tamaño (2MB máximo)
        if ($image->getSize() > 2048 * 1024) {
            throw new HTTPException(
                'Image too large. Maximum size is 2MB.',
                Response::HTTP_BAD_REQUEST
            );
        }

        // Crear directorio si no existe
        $uploadPath = FCPATH . 'img';
        if (!is_dir($uploadPath)) {
            if (!mkdir($uploadPath, 0755, true)) {
                throw new HTTPException(
                    'Failed to create upload directory',
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }

        // Generar nombre aleatorio usando CI4
        $newName = $image->getRandomName();

        // Mover imagen usando CI4
        try {
            $image->move($uploadPath, $newName);
        } catch (\Exception $e) {
            throw new HTTPException(
                'Failed to upload image: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return '/img/' . $newName;
    }


    /**
     * Crea un service price directamente sin pasar por validaciones
     *
     * @param array $data
     * @return mixed
     * @throws HTTPException
     */
    private function createDirectly(array $data)
    {
        $existing = $this->repo->getByUnique($data['service_id'], $data['county_id'], $data['performers_count'], true);

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
                return $this->repo->update($existing->id, $data);
            }
            return $existing->id;
        }

        // Crear directamente usando query builder para evitar validaciones
        $db = \Config\Database::connect();

        // Generar UUID
        $data['id'] = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $result = $db->table('service_prices')->insert($data);

        if (!$result) {
            throw new HTTPException(
                'Error creating service price',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $data['id'];
    }
}
