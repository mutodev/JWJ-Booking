<?php

namespace App\Controllers;

use App\Services\ContactVerificationService;
use CodeIgniter\RESTful\ResourceController;

class ContactVerificationController extends ResourceController
{
    protected ContactVerificationService $service;

    public function __construct() { $this->service = new ContactVerificationService(); }

    public function requestCode() { return $this->execute('request', 201); }
    public function verifyCode() { return $this->execute('verify', 200); }

    private function execute(string $method, int $successStatus)
    {
        try {
            $data = json_decode($this->request->getBody(), true);
            if (!is_array($data)) $data = [];
            return $this->response->setStatusCode($successStatus)->setJSON(['data' => $this->service->{$method}($data)]);
        } catch (\Throwable $e) {
            $status = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            return $this->response->setStatusCode($status)->setJSON(['message' => $e->getMessage()]);
        }
    }
}
