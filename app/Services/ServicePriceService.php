<?php

namespace App\Services;

use App\Repositories\ServicePriceRepository;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\Exceptions\HTTPException;

/**
 * Servicio para gestión de precios de servicios
 * Maneja la lógica de negocio para CRUD de service prices
 */
class ServicePriceService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new ServicePriceRepository();
    }

    /**
     * Obtiene todos los precios de servicios
     * @return array Lista de precios de servicios
     */
    public function getAll()
    {
        return $this->repo->getAll();
    }

    /**
     * Obtiene los precios del servicio por condado
     * @param string $countyId ID del condado
     * @return array Lista de precios filtrados por condado
     */
    public function getAllByCounty($countyId)
    {
        return $this->repo->getAllByCounty($countyId);
    }

    /**
     * Obtiene los precios del servicio por metropolitan area
     * @param string $metropolitanAreaId ID del área metropolitana
     * @return array Lista de precios filtrados por área metropolitana
     */
    public function getAllByMetropolitanArea($metropolitanAreaId)
    {
        return $this->repo->getAllByMetropolitanArea($metropolitanAreaId);
    }

    /**
     * Obtiene los precios del servicio por zipcode
     * Consulta: zipcode -> city -> county -> service_prices
     * @param string $zipcodeId ID del zipcode
     * @return array Lista de precios filtrados por county del zipcode
     */
    public function getAllByZipcode($zipcodeId)
    {
        return $this->repo->getAllByZipcode($zipcodeId);
    }

    /**
     * Obtiene un precio de servicio por ID
     * @param string $id ID del precio de servicio
     * @return object Precio de servicio encontrado
     * @throws HTTPException Si no se encuentra el precio
     */
    public function getById(string $id)
    {
        $price = $this->repo->getById($id);
        if (!$price) {
            throw new HTTPException(lang('ServicePrice.notFound'), Response::HTTP_NOT_FOUND);
        }
        return $price;
    }

    /**
     * Obtiene precios por servicio y condado específicos
     * @param string $serviceId ID del servicio
     * @param string $countyId ID del condado
     * @return array Lista de precios que coinciden
     */
    public function getByServiceAndCounty(string $serviceId, string $countyId)
    {
        return $this->repo->getByServiceAndCounty($serviceId, $countyId);
    }

    /**
     * Crea un nuevo precio de servicio
     * Maneja duplicados y soft deletes automáticamente
     * @param array $data Datos del precio de servicio
     * @return string ID del precio creado o restaurado
     * @throws HTTPException Si ya existe un precio activo con los mismos parámetros
     */
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

    /**
     * Actualiza un precio de servicio existente
     * @param string $id ID del precio de servicio
     * @param array $data Datos a actualizar
     * @return bool Resultado de la actualización
     * @throws HTTPException Si no se encuentra el precio
     */
    public function update(string $id, array $data)
    {
        $price = $this->repo->getById($id);
        if (!$price) {
            throw new HTTPException(lang('ServicePrice.notFound'), Response::HTTP_NOT_FOUND);
        }

        return $this->repo->update($id, $data);
    }

    /**
     * Elimina un precio de servicio (soft delete)
     * @param string $id ID del precio de servicio
     * @return bool Resultado de la eliminación
     * @throws HTTPException Si no se encuentra el precio
     */
    public function delete(string $id)
    {
        $price = $this->repo->getById($id);
        if (!$price) {
            throw new HTTPException(lang('ServicePrice.notFound'), Response::HTTP_NOT_FOUND);
        }

        return $this->repo->softDelete($id);
    }

    /**
     * Crear un nuevo precio de servicio con manejo de imagen y FormData
     * Soporta tanto JSON como multipart/form-data
     *
     * @param \CodeIgniter\HTTP\IncomingRequest $request Petición HTTP
     * @return string ID del precio de servicio creado
     * @throws HTTPException Si hay errores en validación o creación
     */
    public function createWithImage($request)
    {
        // Extraer datos del request
        $data = $this->extractRequestData($request);

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
     * Extrae datos del request, soportando JSON y FormData
     * @param \CodeIgniter\HTTP\IncomingRequest $request
     * @return array Datos extraídos y normalizados
     */
    private function extractRequestData($request): array
    {
        $data = [];
        $contentType = $request->getHeaderLine('Content-Type');

        // Intentar extraer JSON primero
        if (strpos($contentType, 'application/json') !== false) {
            try {
                $json = $request->getJSON(true);
                if ($json) {
                    $data = $json;
                }
            } catch (\Exception $e) {
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

        return $data;
    }

    /**
     * Sanitiza y valida los datos del formulario
     * Filtra campos permitidos y convierte tipos de datos
     *
     * @param array $data Datos sin procesar del formulario
     * @return array Datos sanitizados y tipados correctamente
     */
    private function sanitizeFormData(array $data): array
    {
        $sanitized = [];
        $allowedFields = [
            'service_id', 'county_id', 'performers_count', 'amount',
            'travel_fee', 'is_available', 'notes', 'extra_child_fee', 'range_age'
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
                    case 'travel_fee':
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
     * Maneja la carga y validación de imagen
     * Valida tipo, tamaño y guarda el archivo de forma segura
     *
     * @param \CodeIgniter\HTTP\Files\UploadedFile $image Archivo de imagen
     * @return string Ruta web de la imagen guardada
     * @throws HTTPException Si hay errores de validación o carga
     */
    private function handleImageUpload($image): string
    {
        // Validar que es una imagen válida
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

        // Crear directorio de destino si no existe
        $uploadPath = FCPATH . 'img';
        if (!is_dir($uploadPath)) {
            if (!mkdir($uploadPath, 0755, true)) {
                throw new HTTPException(
                    'Failed to create upload directory',
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }

        // Generar nombre único y mover archivo
        $newName = $image->getRandomName();
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

    /**
     * Actualiza un precio de servicio existente con manejo de imagen
     * Soporta tanto JSON como multipart/form-data
     *
     * @param string $id ID del precio de servicio a actualizar
     * @param \CodeIgniter\HTTP\IncomingRequest $request Petición HTTP
     * @return mixed Resultado de la actualización
     * @throws HTTPException Si no se encuentra el precio o hay errores
     */
    public function updateWithImage(string $id, $request)
    {
        // Verificar que el registro existe
        $price = $this->repo->getById($id);
        if (!$price) {
            throw new HTTPException(lang('ServicePrice.notFound'), Response::HTTP_NOT_FOUND);
        }

        // Extraer datos del request
        $data = $this->extractRequestData($request);

        // Limpiar y validar campos
        $data = $this->sanitizeFormData($data);

        // Procesar imagen si existe
        $image = $request->getFile('image');
        if ($image && $image->isValid() && !$image->hasMoved()) {
            $imagePath = $this->handleImageUpload($image);
            $data['img'] = $imagePath;
        }

        return $this->repo->update($id, $data);
    }
}
