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

        $subject = $this->replacePlaceholders($template->subject, $variables);
        $body    = $this->replacePlaceholders($template->body, $variables);

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

        $subject = $this->replacePlaceholders($template->subject, $variables);
        $body    = $this->replacePlaceholders($template->body, $variables);

        return [
            'subject' => $subject,
            'body'    => $body,
        ];
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
                ]);
                break;

            case 'reservation_confirmation':
                $subject = "Reservation Received - JamWithJamie";
                $body = view($viewMap[$slug], [
                    'reservation' => $variables['_reservation'] ?? (object) $variables,
                    'eventDate'   => $variables['event_date'] ?? 'TBD',
                    'totalAmount' => $variables['total_amount'] ?? '0.00',
                ]);
                break;

            case 'welcome':
                $subject = "Welcome to JamWithJamie";
                $body = view($viewMap[$slug], [
                    'password' => $variables['password'] ?? '',
                ]);
                break;

            case 'reset_password':
                $subject = "Password Reset - JamWithJamie";
                $body = view($viewMap[$slug], [
                    'password' => $variables['password'] ?? '',
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
