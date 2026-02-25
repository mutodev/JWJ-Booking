<?php

namespace App\Repositories;

use App\Entities\EmailTemplate;
use App\Models\EmailTemplateModel;

class EmailTemplateRepository
{
    protected EmailTemplateModel $model;

    protected $allowedFields = [
        'subject', 'body', 'available_variables', 'is_active',
    ];

    public function __construct()
    {
        $this->model = new EmailTemplateModel();
    }

    public function getAll(): array
    {
        return $this->model->select('id, slug, name, subject, available_variables, is_active, created_at, updated_at')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    public function getById(string $id): ?EmailTemplate
    {
        return $this->model->where('id', $id)->first();
    }

    public function getBySlug(string $slug): ?EmailTemplate
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function update(string $id, array $data): bool
    {
        $filtered = array_intersect_key($data, array_flip($this->allowedFields));
        if (empty($filtered)) return false;
        return $this->model->update($id, $filtered);
    }
}
