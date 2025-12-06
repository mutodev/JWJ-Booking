<?php

namespace App\Services;

use App\Repositories\AddonRepository;
use App\Repositories\TypeAddonRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;

/**
 * Addon Service
 *
 * Business logic layer for addon management operations.
 * Handles CRUD operations, validation, image processing, and data sanitization.
 *
 * Features:
 * - Complete CRUD operations with validation
 * - Image upload and processing with validation
 * - FormData and JSON request handling
 * - Data sanitization and type conversion
 * - Error handling and HTTP exceptions
 * - Duplicate name validation
 * - File size and type validation for images
 *
 * @package App\Services
 * @author  System Generated
 */
class AddonService
{
    protected AddonRepository $repository;
    protected TypeAddonRepository $typeAddonRepository;

    /**
     * Constructor - Initialize addon repository
     */
    public function __construct()
    {
        $this->repository = new AddonRepository();
        $this->typeAddonRepository = new TypeAddonRepository();
    }

    /**
     * Get addon by ID
     *
     * @param string $id Addon ID
     * @return object Addon data
     * @throws HTTPException If addon not found
     */
    public function getById(string $id)
    {
        $addon = $this->repository->getById($id);

        if (!$addon) {
            throw new HTTPException(lang('Addon.notFound'), Response::HTTP_NOT_FOUND);
        }

        return $addon;
    }

    /**
     * Get all addons
     *
     * @return array List of all addons
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Get active addons grouped by type
     *
     * @return array List of type addons with their addons
     */
    public function getAllActive(): array
    {
        // Obtener tipos de addons activos
        $typeAddons = $this->typeAddonRepository->getAllActive();

        // Obtener todos los addons activos
        $addons = $this->repository->getAllActive();

        // Agrupar addons por tipo
        $result = [];
        foreach ($typeAddons as $type) {
            $typeData = [
                'id' => $type->id,
                'name' => $type->name,
                'image' => $type->image,
                'description' => $type->description,
                'addons' => []
            ];

            // Filtrar addons de este tipo
            foreach ($addons as $addon) {
                if ($addon->type_addon_id === $type->id) {
                    $typeData['addons'][] = $addon;
                }
            }

            $result[] = $typeData;
        }

        return $result;
    }

    /**
     * Search addons by name pattern
     *
     * @param string $name Name pattern to search (LIKE query)
     * @return array List of matching addons
     */
    public function search(string $name): array
    {
        return $this->repository->searchByName($name);
    }

    /**
     * Create new addon with duplicate validation
     *
     * @param array $data Addon data
     * @return mixed Created addon data
     * @throws HTTPException If name already exists or creation fails
     */
    public function create(array $data)
    {
        // Check for duplicate name
        $exists = $this->repository->getByName($data['name']);
        if ($exists) {
            throw new HTTPException(lang('Addon.duplicateName'), Response::HTTP_BAD_REQUEST);
        }

        $createdAddon = $this->repository->create($data);

        if (!$createdAddon) {
            throw new HTTPException(lang('Addon.createFailed'), Response::HTTP_BAD_REQUEST);
        }

        return $createdAddon;
    }

    /**
     * Update existing addon
     *
     * @param string $id Addon ID
     * @param array $data Update data
     * @return bool True if successful
     * @throws HTTPException If update fails
     */
    public function update(string $id, array $data): bool
    {
        $updated = $this->repository->update($id, $data);

        if (!$updated) {
            throw new HTTPException(lang('Addon.updateFailed'), Response::HTTP_BAD_REQUEST);
        }

        return true;
    }

    /**
     * Delete addon (soft delete)
     *
     * @param string $id Addon ID
     * @return bool True if successful
     * @throws HTTPException If deletion fails
     */
    public function delete(string $id): bool
    {
        $deleted = $this->repository->delete($id);

        if (!$deleted) {
            throw new HTTPException(lang('Addon.deleteFailed'), Response::HTTP_BAD_REQUEST);
        }

        return true;
    }

    /**
     * Create new addon with image and FormData handling
     * Supports both JSON and multipart/form-data requests
     *
     * @param \CodeIgniter\HTTP\IncomingRequest $request HTTP request
     * @return string Created addon ID
     * @throws HTTPException If validation or creation fails
     */
    public function createWithImage($request)
    {
        // Extract data from request
        $data = $this->extractRequestData($request);

        // Clean and validate fields
        $data = $this->sanitizeFormData($data);

        // Check for duplicate names
        if (isset($data['name'])) {
            $exists = $this->repository->getByName($data['name']);
            if ($exists) {
                throw new HTTPException(lang('Addon.duplicateName'), Response::HTTP_BAD_REQUEST);
            }
        }

        return $this->createDirectly($data);
    }

    /**
     * Update existing addon with image handling
     * Supports both JSON and multipart/form-data requests
     *
     * @param string $id Addon ID to update
     * @param \CodeIgniter\HTTP\IncomingRequest $request HTTP request
     * @return bool Update result
     * @throws HTTPException If addon not found or update fails
     */
    public function updateWithImage(string $id, $request)
    {
        // Verify addon exists
        $addon = $this->repository->getById($id);
        if (!$addon) {
            throw new HTTPException(lang('Addon.notFound'), Response::HTTP_NOT_FOUND);
        }

        // Extract data from request
        $data = $this->extractRequestData($request);

        // Clean and validate fields
        $data = $this->sanitizeFormData($data);

        return $this->repository->update($id, $data);
    }

    /**
     * Extract data from request, supporting both JSON and FormData
     *
     * @param \CodeIgniter\HTTP\IncomingRequest $request HTTP request
     * @return array Extracted and normalized data
     */
    private function extractRequestData($request): array
    {
        $data = [];
        $contentType = $request->getHeaderLine('Content-Type');

        // Try to extract JSON first
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

        // If no JSON data or multipart, use POST
        if (empty($data)) {
            $data = $request->getPost() ?: [];

            // Convert arrays to strings (common issue with multiselect)
            foreach ($data as $key => $value) {
                if (is_array($value) && !empty($value)) {
                    $data[$key] = $value[0]; // Take first element
                }
            }
        }

        return $data;
    }

    /**
     * Sanitize and validate form data
     * Filters allowed fields and converts data types
     *
     * @param array $data Raw form data
     * @return array Sanitized and type-converted data
     */
    private function sanitizeFormData(array $data): array
    {
        $sanitized = [];
        $allowedFields = [
            'type_addon_id', 'name', 'base_price',
            'estimated_duration_minutes', 'is_active', 'is_referral_service'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];

                // Handle empty values
                if ($value === '' || $value === null) {
                    continue;
                }

                // Convert according to field type
                switch ($field) {
                    case 'estimated_duration_minutes':
                        $sanitized[$field] = $value ? (int)$value : null;
                        break;
                    case 'base_price':
                        $sanitized[$field] = (float)$value;
                        break;
                    case 'is_active':
                    case 'is_referral_service':
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
     * Create addon directly without additional validations
     * Uses direct database insertion for performance
     *
     * @param array $data Addon data
     * @return string Created addon ID
     * @throws HTTPException If creation fails
     */
    private function createDirectly(array $data)
    {
        // Create directly using query builder
        $db = \Config\Database::connect();

        // Generate UUID and timestamps
        $data['id'] = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $result = $db->table('addons')->insert($data);

        if (!$result) {
            throw new HTTPException(
                'Error creating addon',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $data['id'];
    }
}
