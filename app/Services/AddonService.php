<?php

namespace App\Services;

use App\Repositories\AddonRepository;
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

    /**
     * Constructor - Initialize addon repository
     */
    public function __construct()
    {
        $this->repository = new AddonRepository();
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
     * Get active addons only
     *
     * @return array List of active addons
     */
    public function getAllActive(): array
    {
        return $this->repository->getAllActive();
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

        // Process image if exists
        $image = $request->getFile('image');
        if ($image && $image->isValid() && !$image->hasMoved()) {
            $imagePath = $this->handleImageUpload($image);
            $data['image'] = $imagePath;
        }

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

        // Process image if exists
        $image = $request->getFile('image');
        if ($image && $image->isValid() && !$image->hasMoved()) {
            $imagePath = $this->handleImageUpload($image);
            $data['image'] = $imagePath;
        }

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
            'name', 'description', 'price_type', 'base_price',
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
     * Handle image upload and validation
     * Validates type, size and saves file securely
     *
     * @param \CodeIgniter\HTTP\Files\UploadedFile $image Image file
     * @return string Web path of saved image
     * @throws HTTPException If validation or upload fails
     */
    private function handleImageUpload($image): string
    {
        // Validate image is valid
        if (!$image->isValid()) {
            throw new HTTPException(
                'Invalid image file: ' . $image->getErrorString(),
                Response::HTTP_BAD_REQUEST
            );
        }

        // Validate MIME type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($image->getMimeType(), $allowedTypes)) {
            throw new HTTPException(
                'Invalid image type. Only JPEG, PNG and GIF are allowed.',
                Response::HTTP_BAD_REQUEST
            );
        }

        // Validate size (2MB maximum)
        if ($image->getSize() > 2048 * 1024) {
            throw new HTTPException(
                'Image too large. Maximum size is 2MB.',
                Response::HTTP_BAD_REQUEST
            );
        }

        // Create destination directory if not exists
        $uploadPath = FCPATH . 'img';
        if (!is_dir($uploadPath)) {
            if (!mkdir($uploadPath, 0755, true)) {
                throw new HTTPException(
                    'Failed to create upload directory',
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }

        // Generate unique name and move file
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
