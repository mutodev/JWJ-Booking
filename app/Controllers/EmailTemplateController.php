<?php

namespace App\Controllers;

use App\Services\EmailTemplateService;
use CodeIgniter\RESTful\ResourceController;

class EmailTemplateController extends ResourceController
{
    protected EmailTemplateService $emailTemplateService;

    public function __construct()
    {
        $this->emailTemplateService = new EmailTemplateService();
    }

    public function getAll()
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Email templates retrieved', $this->emailTemplateService->getAll()));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function getById(string $id)
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Email template retrieved', $this->emailTemplateService->getById($id)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function updateData(string $id)
    {
        try {
            $data = json_decode($this->request->getBody(), true);
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Email template updated', $this->emailTemplateService->update($id, $data)));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function preview()
    {
        try {
            $data = json_decode($this->request->getBody(), true);
            $id = $data['id'] ?? '';
            $variables = $data['variables'] ?? [];

            $result = $this->emailTemplateService->preview($id, $variables);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Preview generated', $result));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }
}
