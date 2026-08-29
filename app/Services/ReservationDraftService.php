<?php

namespace App\Services;

use App\Models\ReservationDraftModel;
use App\Models\ReservationEmailHistoryModel;
use App\Services\BrevoEmailService;
use App\Services\EmailTemplateService;

class ReservationDraftService
{
    protected ReservationDraftModel $draftModel;

    public function __construct()
    {
        $this->draftModel = new ReservationDraftModel();
    }

    /**
     * Save or update draft
     *
     * @param array $data
     * @return array
     */
    public function saveDraft(array $data): array
    {
        try {
            $sessionId = $data['session_id'] ?? null;
            $email = $data['email'] ?? null;

            if (!$sessionId) {
                throw new \InvalidArgumentException('Session ID is required');
            }

            // Try to find existing draft
            $existingDraft = null;

            // First try by session
            if ($sessionId) {
                $existingDraft = $this->draftModel->findBySession($sessionId);
            }

            // If not found and has email, try by email
            if (!$existingDraft && $email) {
                $existingDraft = $this->draftModel->findByEmail($email);
            }

            $draftData = [
                'session_id' => $sessionId,
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'current_step' => $data['current_step'] ?? 1,
                'form_data' => $data['form_data'] ?? [],
                'ip_address' => $data['ip_address'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
                'last_activity_at' => date('Y-m-d H:i:s')
            ];

            if ($existingDraft) {
                // Update existing draft
                $this->draftModel->update($existingDraft->id, $draftData);
                $draftId = $existingDraft->id;
            } else {
                // Create new draft
                $draftId = $this->draftModel->insert($draftData);
            }

            return [
                'success' => true,
                'draft_id' => $draftId,
                'message' => 'Draft saved successfully'
            ];

        } catch (\Exception $e) {
            log_message('error', 'Error saving draft: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to save draft: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get draft by session or email
     *
     * @param string $sessionId
     * @param string|null $email
     * @return array|null
     */
    public function getDraft(string $sessionId, ?string $email = null): ?array
    {
        try {
            $draft = $this->draftModel->findBySession($sessionId);

            if (!$draft && $email) {
                $draft = $this->draftModel->findByEmail($email);
            }

            if (!$draft) {
                return null;
            }

            return [
                'id' => $draft->id,
                'current_step' => $draft->current_step,
                'form_data' => $draft->form_data,
                'last_activity_at' => $draft->last_activity_at
            ];

        } catch (\Exception $e) {
            log_message('error', 'Error getting draft: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Mark draft as completed
     *
     * @param string $sessionId
     * @param string $reservationId
     * @return bool
     */
    public function completeDraft(string $sessionId, string $reservationId): bool
    {
        try {
            $draft = $this->draftModel->findBySession($sessionId);

            if (!$draft) {
                return false;
            }

            $this->draftModel->update($draft->id, [
                'completed' => 1,
                'reservation_id' => $reservationId
            ]);

            return true;

        } catch (\Exception $e) {
            log_message('error', 'Error completing draft: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get abandoned drafts for follow-up
     *
     * @param int $hoursOld
     * @return array
     */
    public function getAbandoned(int $hoursOld = 24): array
    {
        return $this->draftModel->getAbandoned($hoursOld);
    }

    /**
     * Get funnel analytics
     *
     * @return array
     */
    public function getFunnelStats(): array
    {
        return $this->draftModel->getFunnelStats();
    }

    /**
     * Get all drafts (Admin)
     *
     * @return array
     */
    public function getAllDrafts(): array
    {
        try {
            return $this->draftModel->orderBy('last_activity_at', 'DESC')->findAll();
        } catch (\Exception $e) {
            log_message('error', 'Error getting all drafts: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Send follow-up email to an abandoned cart
     *
     * @param string $id Draft ID
     * @return object|false
     */
    public function sendFollowUpEmail(string $id)
    {
        $draft = $this->draftModel->find($id);

        if (!$draft || $draft->completed || empty($draft->email)) {
            return false;
        }

        $formData = is_string($draft->form_data)
            ? (json_decode($draft->form_data, true) ?? [])
            : (array) $draft->form_data;

        $customerName = $formData['full_name'] ?? $formData['name'] ?? 'there';

        $templateService = new EmailTemplateService();
        $emailService    = new BrevoEmailService();

        $rendered = $templateService->render('abandoned_cart_followup', [
            'customer_name' => $customerName,
            'resume_url'    => base_url(),
        ]);

        $emailResult = $emailService->sendEmail($draft->email, $rendered['subject'], $rendered['body']);
        if (!$emailResult) {
            return false;
        }

        $sentAt = date('Y-m-d H:i:s');
        if (!$this->draftModel->update($draft->id, ['follow_up_sent_at' => $sentAt])) {
            throw new \RuntimeException('Follow-up email was sent, but the sent timestamp could not be saved.');
        }

        // Timeline registration (B3): only when the draft is already linked to a
        // reservation — reservation_email_history.reservation_id is a mandatory FK.
        // Never let a history failure break the follow-up flow.
        if (!empty($draft->reservation_id)) {
            try {
                (new ReservationEmailHistoryModel())->insert([
                    'reservation_id'  => $draft->reservation_id,
                    'template_id'     => null,
                    'template_name'   => 'Abandoned Cart Follow-Up',
                    'event_type'      => 'email',
                    'sent_by'         => 'System',
                    'recipient_email' => $draft->email,
                    'cc_emails'       => null,
                    'email_subject'   => $rendered['subject'] ?? '',
                    'email_body'      => ($rendered['body'] ?? '') !== '' ? $rendered['body'] : '—',
                    'status'          => 'Sent',
                    'sent_at'         => $sentAt,
                ]);
            } catch (\Throwable $e) {
                log_message('error', 'Failed to record abandoned cart follow-up history: ' . $e->getMessage());
            }
        }

        return $this->draftModel->find($draft->id);
    }

    /**
     * Get draft by ID (Admin)
     *
     * @param string $id
     * @return array|null
     */
    public function getDraftById(string $id): ?array
    {
        try {
            return $this->draftModel->find($id);
        } catch (\Exception $e) {
            log_message('error', 'Error getting draft by ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete a single draft (Admin)
     *
     * @param string $id
     * @return bool
     */
    public function deleteDraft(string $id): bool
    {
        try {
            return (bool) $this->draftModel->delete($id);
        } catch (\Exception $e) {
            log_message('error', 'Error deleting draft: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete multiple drafts at once (Admin)
     *
     * @param array $ids
     * @return array { deleted: int, total: int }
     */
    public function bulkDeleteDrafts(array $ids): array
    {
        $deleted = 0;
        foreach ($ids as $id) {
            if ($this->deleteDraft((string) $id)) {
                $deleted++;
            }
        }

        return ['deleted' => $deleted, 'total' => count($ids)];
    }
}
