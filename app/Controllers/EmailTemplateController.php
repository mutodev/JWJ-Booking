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

            $logoUrl = base_url('img/logos/JWJ_logo-05.png');
            $year    = date('Y');
            $body    = $this->wrapEmailContent($htmlContent, $logoUrl, $year);

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

    private function wrapEmailContent(string $content, string $logoUrl, string $year): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f9fafb;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;-webkit-font-smoothing:antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9fafb;padding:40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.06);">
                    <tr>
                        <td style="background-color:#ffffff;padding:32px 40px;text-align:center;border-bottom:3px solid #FF74B7;">
                            <img src="{$logoUrl}" alt="Jam with Jamie" width="220" style="display:inline-block;max-width:220px;height:auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px 40px 32px;font-size:15px;line-height:1.7;color:#374151;">
                            {$content}
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f9fafb;padding:24px 40px;border-top:1px solid #e5e7eb;text-align:center;">
                            <p style="margin:0 0 4px;font-size:14px;font-weight:600;color:#FF74B7;">The Jam with Jamie Team</p>
                            <p style="margin:0;font-size:12px;color:#9ca3af;">&copy; {$year} Jam with Jamie LLC. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
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
