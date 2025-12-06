<?php

namespace App\Services;

use App\Repositories\TypeAddonRepository;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\Exceptions\HTTPException;

class TypeAddonService
{
    protected $repo;
    protected $uploadPath;

    public function __construct()
    {
        $this->repo = new TypeAddonRepository();
        $this->uploadPath = FCPATH . 'img';

        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
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

    /**
     * Create type addon with image upload support
     */
    public function createWithImage($request)
    {
        $data = $this->extractRequestData($request);
        $data = $this->sanitizeFormData($data);

        // Handle image upload
        $image = $request->getFile('image');
        if ($image && $image->isValid() && !$image->hasMoved()) {
            $data['image'] = $this->uploadImage($image);
        }

        return $this->create($data);
    }

    /**
     * Update type addon with image handling
     */
    public function updateWithImage(string $id, $request)
    {
        $typeAddon = $this->repo->getById($id);
        if (!$typeAddon) {
            throw new HTTPException('Type Addon not found', Response::HTTP_NOT_FOUND);
        }

        $data = $this->extractRequestData($request);
        $data = $this->sanitizeFormData($data);

        // Handle image upload
        $image = $request->getFile('image');
        if ($image && $image->isValid() && !$image->hasMoved()) {
            // Delete old image if exists
            if ($typeAddon->image) {
                $this->deleteImageFile($typeAddon->image);
            }
            $data['image'] = $this->uploadImage($image);
        }

        // Handle image removal
        if ($request->getPost('remove_image') === '1') {
            if ($typeAddon->image) {
                $this->deleteImageFile($typeAddon->image);
            }
            $data['image'] = null;
        }

        return $this->repo->update($id, $data);
    }

    /**
     * Upload image and return URL
     */
    private function uploadImage($image): string
    {
        $newName = $image->getRandomName();
        $image->move($this->uploadPath, $newName);

        // Return relative URL
        return '/img/' . $newName;
    }

    /**
     * Delete image file from filesystem
     */
    private function deleteImageFile(string $imagePath): void
    {
        $fullPath = FCPATH . ltrim($imagePath, '/');
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    /**
     * Extract data from request, supporting both JSON and FormData
     */
    private function extractRequestData($request): array
    {
        $data = [];
        $contentType = $request->getHeaderLine('Content-Type');

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

        if (empty($data)) {
            $data = $request->getPost() ?: [];
        }

        return $data;
    }

    /**
     * Sanitize and validate form data
     */
    private function sanitizeFormData(array $data): array
    {
        $sanitized = [];
        $allowedFields = ['name', 'description', 'is_active'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];

                if ($value === '' || $value === null) {
                    if ($field === 'description') {
                        $sanitized[$field] = null;
                    }
                    continue;
                }

                switch ($field) {
                    case 'is_active':
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
}
