<?php

namespace App\Services;

use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\Response;

class SecureImageUploadService
{
    private const MAX_BYTES = 2 * 1024 * 1024;

    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
    ];

    public function upload(UploadedFile $image): string
    {
        if (!$image->isValid() || $image->hasMoved()) {
            throw new HTTPException('Invalid image file.', Response::HTTP_BAD_REQUEST);
        }
        if ($image->getSize() <= 0 || $image->getSize() > self::MAX_BYTES) {
            throw new HTTPException('Image too large. Maximum size is 2MB.', Response::HTTP_BAD_REQUEST);
        }

        $mime = strtolower($image->getMimeType());
        $dimensions = @getimagesize($image->getTempName());
        if (!isset(self::MIME_EXTENSIONS[$mime]) || $dimensions === false) {
            throw new HTTPException('Invalid image type. Only JPEG, PNG and GIF are allowed.', Response::HTTP_BAD_REQUEST);
        }

        $uploadPath = FCPATH . 'img';
        if (!is_dir($uploadPath) && !mkdir($uploadPath, 0755, true) && !is_dir($uploadPath)) {
            throw new HTTPException('Failed to create upload directory.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $newName = bin2hex(random_bytes(16)) . '.' . self::MIME_EXTENSIONS[$mime];
        $image->move($uploadPath, $newName);

        return '/img/' . $newName;
    }

    public function delete(string $publicPath): void
    {
        $imageRoot = realpath(FCPATH . 'img');
        $candidate = realpath(FCPATH . ltrim($publicPath, '/\\'));

        if ($imageRoot === false || $candidate === false) {
            return;
        }

        $prefix = rtrim($imageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($candidate, $prefix) || !is_file($candidate)) {
            return;
        }

        unlink($candidate);
    }
}
