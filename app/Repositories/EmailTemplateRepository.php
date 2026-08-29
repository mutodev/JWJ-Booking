<?php

namespace App\Repositories;

use App\Entities\EmailTemplate;
use App\Models\EmailTemplateModel;

class EmailTemplateRepository
{
    protected EmailTemplateModel $model;

    /**
     * Fields a request payload is allowed to change directly.
     *
     * NOTE: is_customized / customized_at / customized_by are deliberately NOT
     * here. They are audit fields the client must never be able to set or
     * forge — they only reach the DB through the $systemFields argument of
     * update(), which is merged AFTER this whitelist filter.
     */
    protected $allowedFields = [
        'subject', 'body', 'available_variables', 'content', 'is_active',
    ];

    public function __construct()
    {
        $this->model = new EmailTemplateModel();
    }

    public function getAll(): array
    {
        return $this->model->select('id, slug, name, subject, available_variables, is_active, is_customized, customized_at, customized_by, created_at, updated_at')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    /**
     * @return EmailTemplate|null
     */
    public function getById(string $id)
    {
        return $this->model->where('id', $id)->first();
    }

    public function getBySlug(string $slug): ?EmailTemplate
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * @param string $id           Template id.
     * @param array  $data          Request-supplied fields (filtered against the whitelist).
     * @param array  $systemFields  Server-generated audit fields (is_customized, customized_at,
     *                              customized_by). Merged AFTER the whitelist filter so a client
     *                              can never forge or clear them.
     */
    public function update(string $id, array $data, array $systemFields = []): bool
    {
        $filtered = array_intersect_key($data, array_flip($this->allowedFields));
        $filtered = array_merge($filtered, $systemFields);
        if (empty($filtered)) return false;
        return $this->model->update($id, $filtered);
    }
}
