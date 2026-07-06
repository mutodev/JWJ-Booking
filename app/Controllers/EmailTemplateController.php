<?php

namespace App\Controllers;

use App\Services\EmailTemplateService;
use App\Services\BrevoEmailService;
use App\Repositories\CustomerRepository;
use CodeIgniter\RESTful\ResourceController;

class EmailTemplateController extends ResourceController
{
    protected EmailTemplateService $emailTemplateService;
    protected BrevoEmailService $brevo;
    protected CustomerRepository $customerRepository;

    public function __construct()
    {
        $this->emailTemplateService = new EmailTemplateService();
        $this->brevo = new BrevoEmailService();
        $this->customerRepository = new CustomerRepository();
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

    public function sendCustomEmail()
    {
        try {
            $data = json_decode($this->request->getBody(), true);
            $subject     = trim($data['subject'] ?? '');
            $htmlContent = $data['html_content'] ?? '';
            $recipientIds = $data['recipient_ids'] ?? [];
            $sendToAll   = $data['send_to_all'] ?? false;
            $isFullHtml  = (bool) ($data['is_full_html'] ?? false);

            if (empty($subject)) {
                return $this->response->setStatusCode(400)->setJSON(['message' => 'Subject is required']);
            }
            if (empty($htmlContent)) {
                return $this->response->setStatusCode(400)->setJSON(['message' => 'Content is required']);
            }

            $customers = $sendToAll
                ? $this->customerRepository->getAll()
                : $this->customerRepository->findByIds($recipientIds);

            if (empty($customers)) {
                return $this->response->setStatusCode(400)->setJSON(['message' => 'No recipients found']);
            }

            $body = $isFullHtml ? $htmlContent : $this->emailTemplateService->wrapContent($htmlContent);

            $sent = 0;
            foreach ($customers as $customer) {
                $email = is_array($customer) ? ($customer['email'] ?? null) : ($customer->email ?? null);
                if ($email) {
                    $this->brevo->sendEmail($email, $subject, $body);
                    $sent++;
                }
            }

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response("Email sent to {$sent} recipient(s)", ['sent' => $sent]));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function composePreview()
    {
        try {
            $data = json_decode($this->request->getBody(), true);
            $id = $data['id'] ?? '';
            $variables = $data['variables'] ?? [];

            if (empty($id)) {
                return $this->response->setStatusCode(400)->setJSON(['message' => 'Template is required']);
            }

            $result = $this->emailTemplateService->composePreview($id, $variables);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('Template loaded', $result));
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
