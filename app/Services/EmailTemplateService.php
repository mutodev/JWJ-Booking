<?php

namespace App\Services;

use App\Repositories\EmailTemplateRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;

class EmailTemplateService
{
    protected EmailTemplateRepository $repository;

    public function __construct()
    {
        $this->repository = new EmailTemplateRepository();
    }

    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    public function getById(string $id)
    {
        $template = $this->repository->getById($id);
        if (!$template) {
            throw new HTTPException('Email template not found', Response::HTTP_NOT_FOUND);
        }
        return $template;
    }

    public function update(string $id, array $data): bool
    {
        $template = $this->repository->getById($id);
        if (!$template) {
            throw new HTTPException('Email template not found', Response::HTTP_NOT_FOUND);
        }

        $updated = $this->repository->update($id, $data);
        if (!$updated) {
            throw new HTTPException('Failed to update email template', Response::HTTP_BAD_REQUEST);
        }

        return true;
    }

    /**
     * Render a template by slug, replacing {{key}} placeholders with variables.
     * Falls back to the original PHP view if template not found or inactive.
     *
     * @param string $slug Template slug
     * @param array $variables Key-value pairs for replacement
     * @return array ['subject' => string, 'body' => string]
     */
    public function render(string $slug, array $variables): array
    {
        $template = $this->repository->getBySlug($slug);

        // Fallback to original PHP view if template doesn't exist or is inactive
        if (!$template || !$template->is_active) {
            return $this->fallback($slug, $variables);
        }

        $contentVars = $this->getContentVars($template);

        // Pass 1: inject editable content vars ({{content_*}} placeholders)
        $subject = $this->replaceOnly($template->subject, $contentVars);
        $body    = $this->replaceOnly($template->body, $contentVars);

        // Pass 2: inject runtime vars + global vars
        $subject = $this->replacePlaceholders($subject, $variables);
        $body    = $this->replacePlaceholders($body, $variables);

        return [
            'subject' => $subject,
            'body'    => $body,
        ];
    }

    /**
     * Preview a template with sample data (without saving)
     */
    public function preview(string $id, array $variables): array
    {
        $template = $this->repository->getById($id);
        if (!$template) {
            throw new HTTPException('Email template not found', Response::HTTP_NOT_FOUND);
        }

        $contentVars = $this->getContentVars($template);

        $subject = $this->replaceOnly($template->subject, $contentVars);
        $body    = $this->replaceOnly($template->body, $contentVars);

        $subject = $this->replacePlaceholders($subject, $variables);
        $body    = $this->replacePlaceholders($body, $variables);

        return [
            'subject' => $subject,
            'body'    => $body,
        ];
    }

    /**
     * Render a template for one-off composition. Templates with a content.message
     * field return only that editable fragment; legacy templates fall back to the
     * full rendered body.
     */
    public function composePreview(string $id, array $variables): array
    {
        $template = $this->repository->getById($id);
        if (!$template) {
            throw new HTTPException('Email template not found', Response::HTTP_NOT_FOUND);
        }

        $contentVars = $this->getContentVars($template);
        $subject = $this->replaceOnly($template->subject, $contentVars);
        $subject = $this->replacePlaceholders($subject, $variables);

        $content = json_decode($template->content ?? '{}', true) ?? [];
        if (array_key_exists('message', $content)) {
            $editable = $this->replacePlaceholders((string) $content['message'], $variables);

            return [
                'subject' => $subject,
                'body' => $editable,
                'is_full_html' => false,
            ];
        }

        $rendered = $this->preview($id, $variables);
        return [
            'subject' => $rendered['subject'],
            'body' => $rendered['body'],
            'is_full_html' => true,
        ];
    }

    public function wrapContent(string $content): string
    {
        $logoUrl = base_url('img/logos/JWJ_logo-05.png');
        $year = date('Y');

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

    /**
     * Extract content variables (prefixed with content_) from the template's content JSON.
     */
    private function getContentVars(\App\Entities\EmailTemplate $template): array
    {
        if (empty($template->content)) {
            return [];
        }

        $content = json_decode($template->content, true) ?? [];
        $vars = [];
        foreach ($content as $key => $value) {
            $stringVal = (string) ($value ?? '');
            // HTML from Quill editor is output as-is; legacy plain text gets nl2br
            $vars['content_' . $key] = strip_tags($stringVal) !== $stringVal
                ? $stringVal
                : nl2br($stringVal);
        }
        return $vars;
    }

    /**
     * Replace {{key}} placeholders without injecting global variables.
     */
    private function replaceOnly(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            if (!is_scalar($value) && $value !== null) {
                continue;
            }
            $content = str_replace('{{' . $key . '}}', (string) ($value ?? ''), $content);
        }
        return $content;
    }

    /**
     * Variables globales que se inyectan automáticamente en todos los templates
     */
    private function getGlobalVariables(): array
    {
        return [
            'logo_url'     => base_url('img/logos/JWJ_logo-05.png'),
            'current_year' => date('Y'),
        ];
    }

    /**
     * Replace {{key}} placeholders in a string
     */
    private function replacePlaceholders(string $content, array $variables): string
    {
        // Merge global variables (user variables take priority)
        $allVariables = array_merge($this->getGlobalVariables(), $variables);

        foreach ($allVariables as $key => $value) {
            // Skip non-scalar values (objects, arrays) used only by fallback views
            if (!is_scalar($value) && $value !== null) {
                continue;
            }
            $content = str_replace('{{' . $key . '}}', (string) ($value ?? ''), $content);
        }
        return $content;
    }

    /**
     * Fallback to original PHP views when template is unavailable
     */
    private function fallback(string $slug, array $variables): array
    {
        $viewMap = [
            'payment_notification'    => 'emails/payment_notification',
            'reservation_confirmation' => 'emails/reservation_confirmation',
            'welcome'                 => 'emails/welcome',
            'reset_password'          => 'emails/reset_password',
            'payment_confirmation'    => 'emails/payment_confirmation',
        ];

        if (!isset($viewMap[$slug])) {
            throw new HTTPException("No fallback view for template: {$slug}", Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        switch ($slug) {
            case 'payment_notification':
                $subject = "Payment Information for Your Event Reservation - ID: " . ($variables['reservation_id'] ?? '');
                $body = view($viewMap[$slug], [
                    'reservation'     => $variables['_reservation'] ?? (object) $variables,
                    'paymentUrl'      => $variables['confirmation_url'] ?? '',
                    'confirmationUrl' => $variables['confirmation_url'] ?? '',
                    'eventDate'       => $variables['event_date'] ?? 'TBD',
                    'totalAmount'     => $variables['total_amount'] ?? '0.00',
                    'totalDurationRow' => $variables['total_duration_row'] ?? '',
                ]);
                break;

            case 'reservation_confirmation':
                $subject = "Reservation Received - Jam with Jamie";
                $body = view($viewMap[$slug], [
                    'reservation' => $variables['_reservation'] ?? (object) $variables,
                    'eventDate'   => $variables['event_date'] ?? 'TBD',
                    'totalAmount' => $variables['total_amount'] ?? '0.00',
                    'totalDurationRow' => $variables['total_duration_row'] ?? '',
                ]);
                break;

            case 'welcome':
                $subject = "Welcome to Jam with Jamie";
                $body = view($viewMap[$slug], [
                    'password' => $variables['password'] ?? '',
                ]);
                break;

            case 'reset_password':
                $subject = "Password Reset - Jam with Jamie";
                $body = view($viewMap[$slug], [
                    'password' => $variables['password'] ?? '',
                ]);
                break;

            case 'payment_confirmation':
                $subject = "Payment Confirmed - Jam with Jamie Reservation #" . ($variables['reservation_id'] ?? '');
                $body = view($viewMap[$slug], [
                    'reservation' => $variables['_reservation'] ?? (object) $variables,
                    'eventDate'   => $variables['event_date'] ?? 'TBD',
                    'totalAmount' => $variables['total_amount'] ?? '0.00',
                    'totalDurationRow' => $variables['total_duration_row'] ?? '',
                ]);
                break;

            default:
                throw new HTTPException("No fallback view for template: {$slug}", Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return [
            'subject' => $subject,
            'body'    => $body,
        ];
    }
}
