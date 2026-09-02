<?php

namespace App\Services;

use App\Models\ReservationDraftModel;
use App\Services\BrevoEmailService;
use App\Services\EmailTemplateService;

class ReservationDraftService
{
    protected ReservationDraftModel $draftModel;

    /**
     * Collaborators used by the follow-up flow. Kept as nullable properties and
     * resolved through lazy getters so tests can substitute doubles via
     * Reflection without touching the database or Brevo (same pattern as
     * ReservationService::historyModel()).
     */
    protected ?EmailTemplateService $templateService = null;
    protected ?BrevoEmailService $emailService = null;

    public function __construct()
    {
        $this->draftModel = new ReservationDraftModel();
    }

    /**
     * Email template renderer (lazy, substitutable in tests).
     */
    protected function templateService(): EmailTemplateService
    {
        if ($this->templateService === null) {
            $this->templateService = new EmailTemplateService();
        }
        return $this->templateService;
    }

    /**
     * Transactional email sender (lazy, substitutable in tests).
     */
    protected function emailService(): BrevoEmailService
    {
        if ($this->emailService === null) {
            $this->emailService = new BrevoEmailService();
        }
        return $this->emailService;
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
     * Send the follow-up email to a single abandoned cart (manual admin action).
     *
     * Public contract (relied on by ReservationDraftController::sendFollowUp):
     *   - returns the refreshed draft object on success;
     *   - returns false when the draft does not exist, is already completed,
     *     has no email, or Brevo rejects the send;
     *   - throws RuntimeException when the email was sent but the
     *     follow_up_sent_at timestamp could not be persisted.
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

        if (!$this->dispatchFollowUp($draft)) {
            return false;
        }

        return $this->draftModel->find($draft->id);
    }

    /**
     * Send the automated one-time follow-up to every abandoned cart (B4 cron).
     *
     * "Abandoned" is the frozen definition enforced by
     * ReservationDraftModel::getAbandonedForFollowUp(): not completed, has an
     * email, inactive for exactly [$daysOld, $daysOld + 1) days (the 7-day
     * mark only, not older), never contacted before.
     *
     * Safety / anti-spam:
     *   - follow_up_sent_at is written and then re-verified per draft; a draft
     *     is only counted once the marker is confirmed persisted;
     *   - a single failing draft is logged and skipped — it never aborts the run
     *     (criterion 5);
     *   - the batch is capped so a backlog cannot flood Brevo in one run.
     *
     * Idempotent: running it twice in a row sends 0 the second time.
     *
     * @param int $daysOld Lower bound of the inactivity window in days (cast to
     *                     int; values < 1 are normalised to the default of 7).
     * @return int Number of drafts to which the email was sent AND for which
     *             follow_up_sent_at was successfully marked.
     */
    public function sendAbandonedFollowUps(int $daysOld = 7): int
    {
        $daysOld = (int) $daysOld;
        if ($daysOld < 1) {
            $daysOld = 7;
        }

        $batchLimit = 200;

        $drafts = $this->draftModel->getAbandonedForFollowUp($daysOld);

        if (count($drafts) > $batchLimit) {
            log_message(
                'info',
                'Abandoned cart follow-up: ' . count($drafts) . ' eligible drafts, processing '
                . $batchLimit . ' this run; the rest will be picked up on the next run.'
            );
            $drafts = array_slice($drafts, 0, $batchLimit);
        }

        $sent = 0;

        foreach ($drafts as $draft) {
            // Defensive re-check: the model already filters these out.
            if (empty($draft->email) || $draft->completed || !empty($draft->follow_up_sent_at)) {
                continue;
            }

            try {
                if ($this->dispatchFollowUp($draft)) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                log_message(
                    'error',
                    "Abandoned cart follow-up failed for draft {$draft->id}: " . $e->getMessage()
                );
                // Keep going: one failure must not abort the batch (criterion 5).
            }
        }

        return $sent;
    }

    /**
     * Render, send and mark a single abandoned-cart follow-up.
     *
     * Shared body of the manual (sendFollowUpEmail) and batch
     * (sendAbandonedFollowUps) paths. The caller is responsible for filtering
     * the draft (not completed, has an email, not already contacted).
     *
     * @return bool true when Brevo accepted the send AND follow_up_sent_at was
     *              persisted; false when Brevo rejected the send.
     * @throws \RuntimeException when the email was sent but follow_up_sent_at
     *              could not be saved (critical: the next run may re-send).
     *
     * F1 (B4) — dead-code removal: the previous version registered an
     * "Abandoned Cart Follow-Up" row in reservation_email_history when
     * $draft->reservation_id was set. That branch is unreachable in the real
     * flow: completeDraft() always writes reservation_id together with
     * completed = 1, and both entry points bail on $draft->completed before
     * reaching here. The history registration was removed rather than
     * replicated; re-introduce only when a genuine converted-draft follow-up
     * case exists.
     */
    private function dispatchFollowUp(object $draft): bool
    {
        $formData = is_string($draft->form_data)
            ? (json_decode($draft->form_data, true) ?? [])
            : (array) $draft->form_data;

        $customerName = $formData['full_name'] ?? $formData['name'] ?? 'there';

        $rendered = $this->templateService()->render('abandoned_cart_followup', [
            'customer_name' => $customerName,
            'resume_url'    => base_url(),
        ]);

        $emailResult = $this->emailService()->sendEmail(
            $draft->email,
            $rendered['subject'],
            $rendered['body']
        );

        if (!$emailResult) {
            return false;
        }

        // Mark first, then verify — the marker is the only spam protection, so a
        // silent persistence failure must be loud and must stop this draft from
        // being counted as sent.
        $sentAt = date('Y-m-d H:i:s');
        $this->draftModel->update($draft->id, ['follow_up_sent_at' => $sentAt]);

        $fresh = $this->draftModel->find($draft->id);
        if (!$fresh || empty($fresh->follow_up_sent_at)) {
            log_message(
                'critical',
                "Abandoned cart follow-up was sent for draft {$draft->id} but follow_up_sent_at "
                . 'could not be persisted; the next run may re-send.'
            );
            throw new \RuntimeException('Follow-up email was sent, but the sent timestamp could not be saved.');
        }

        return true;
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
