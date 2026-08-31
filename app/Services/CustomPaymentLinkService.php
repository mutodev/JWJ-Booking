<?php

namespace App\Services;

use App\Models\ReservationEmailHistoryModel;
use App\Repositories\CustomPaymentLinkRepository;
use App\Repositories\ReservationRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;
use Ramsey\Uuid\Uuid;

/**
 * B5 — Custom payment links (arbitrary amount + free description).
 *
 * Standalone lightweight entity. A link may optionally reference a reservation
 * (so B6 can charge differences) but paying it never touches the reservation
 * totals. The amount is defined once here, on the server, and the Stripe
 * checkout reads it from the persisted row — never from a URL parameter.
 *
 * Testability: every collaborator lives in a property (or a lazy getter for
 * StripeService, mirroring ReservationService::getStripeService()) so tests can
 * swap doubles by Reflection without a database.
 */
class CustomPaymentLinkService
{
    /** Hard cap to prevent catastrophic typo charges (acceptance criterion 2). */
    public const MAX_AMOUNT = 10000.0;

    /** Slug of the email template seeded by CustomPaymentLinkEmailSeeder. */
    private const TEMPLATE_SLUG = 'custom_payment_link';

    protected CustomPaymentLinkRepository $repo;
    protected ReservationRepository $reservationRepository;
    protected EmailTemplateService $emailTemplateService;
    protected BrevoEmailService $emailService;

    /** @var StripeService|null Lazy — see getStripeService(). */
    protected $stripeService = null;

    /** @var ReservationEmailHistoryModel|null Lazy — see historyModel(). */
    protected $historyModel = null;

    public function __construct()
    {
        $this->repo                  = new CustomPaymentLinkRepository();
        $this->reservationRepository = new ReservationRepository();
        $this->emailTemplateService  = new EmailTemplateService();
        $this->emailService          = new BrevoEmailService();
    }

    protected function getStripeService(): StripeService
    {
        if ($this->stripeService === null) {
            $this->stripeService = new StripeService();
        }

        return $this->stripeService;
    }

    protected function historyModel(): ReservationEmailHistoryModel
    {
        if ($this->historyModel === null) {
            $this->historyModel = new ReservationEmailHistoryModel();
        }

        return $this->historyModel;
    }

    // ---------------------------------------------------------------------
    // Queries
    // ---------------------------------------------------------------------

    /**
     * @return object[]
     */
    public function listLinks(): array
    {
        return $this->repo->getAll();
    }

    public function getLink(string $id): object
    {
        $link = $this->repo->findById($id);

        if (!$link) {
            throw new HTTPException('Payment link not found', Response::HTTP_NOT_FOUND);
        }

        return $link;
    }

    // ---------------------------------------------------------------------
    // Create
    // ---------------------------------------------------------------------

    /**
     * Validate the payload, create the Stripe Checkout Session, then persist the
     * row. The row is written AFTER Stripe succeeds, so a Stripe failure can
     * never leave an orphan link (acceptance criterion 3).
     *
     * @param array  $data      customer_name, customer_email, description, amount,
     *                           reservation_id (optional), currency (optional)
     * @param string $createdBy Human-readable identity of the admin (audit only).
     */
    public function createLink(array $data, string $createdBy = 'System'): object
    {
        $amount      = $this->assertValidAmount($data['amount'] ?? null);
        $description = $this->assertValidDescription($data['description'] ?? null);
        $email       = $this->assertValidEmail($data['customer_email'] ?? null);
        $name        = $this->normalizeName($data['customer_name'] ?? null);
        $reservationId = $this->assertValidReservationId($data['reservation_id'] ?? null);
        $currency    = $this->normalizeCurrency($data['currency'] ?? null);

        // Pre-generate the id so it can travel in the Stripe metadata before the
        // row exists.
        $id = Uuid::uuid4()->toString();

        try {
            $session = $this->getStripeService()->createCheckoutSession(
                $amount,
                $email,
                '',                 // no reservation_id in the default metadata
                $description,
                0.0,
                [
                    'type'            => 'custom_payment_link',
                    'payment_link_id' => $id,
                ]
            );
        } catch (\Throwable $e) {
            log_message('error', 'Custom payment link: Stripe session creation failed: ' . $e->getMessage());
            throw new HTTPException(
                'Could not create the payment session. Please try again.',
                Response::HTTP_BAD_GATEWAY
            );
        }

        // Stripe succeeded: safe to persist. Only whitelisted fields reach the DB.
        $this->repo->create([
            'reservation_id' => $reservationId,
            'customer_name'  => $name,
            'customer_email' => $email,
            'description'    => $description,
            'amount'         => $amount,
            'currency'       => $currency,
            'created_by'     => mb_substr($createdBy, 0, 255),
        ], $id);

        $expiresAt = isset($session->expires_at) && $session->expires_at
            ? date('Y-m-d H:i:s', (int) $session->expires_at)
            : null;

        $this->repo->attachSession($id, (string) $session->id, (string) $session->url, $expiresAt);

        $link = $this->repo->findById($id);

        // Best-effort: send the link email on creation. A send failure must not
        // fail the create call — the admin can resend from the list.
        try {
            $this->dispatchLinkEmail($link);
        } catch (\Throwable $e) {
            log_message('error', 'Custom payment link: initial email send failed: ' . $e->getMessage());
        }

        return $link;
    }

    // ---------------------------------------------------------------------
    // Email
    // ---------------------------------------------------------------------

    /**
     * Send / resend the payment link email. Public entry point for the
     * POST /payment-links/{id}/send-email endpoint.
     */
    public function sendLinkEmail(string $id): object
    {
        $link = $this->getLink($id);

        if ($link->status === 'cancelled') {
            throw new HTTPException('Cannot send a cancelled payment link', Response::HTTP_BAD_REQUEST);
        }

        $this->dispatchLinkEmail($link);

        return $link;
    }

    /**
     * Render the `custom_payment_link` template and send it. Records the send in
     * the reservation timeline when the link is tied to a reservation
     * (acceptance criterion 9).
     *
     * @throws HTTPException 500 when the template is missing (clear message, not
     *                       an opaque error) or when the send itself throws.
     */
    private function dispatchLinkEmail(object $link): void
    {
        $recipient = (string) ($link->customer_email ?? '');
        if ($recipient === '') {
            throw new HTTPException('The payment link has no customer email', Response::HTTP_BAD_REQUEST);
        }

        $amountLabel = number_format((float) $link->amount, 2);

        // XSS: description and customer_name are free admin text that lands in
        // the email HTML. EmailTemplateService::render() does a plain
        // str_replace, so escape here. payment_url is a Stripe URL; encode it
        // for an attribute context.
        $vars = [
            'customer_name' => esc($link->customer_name ?: 'there'),
            'description'   => esc((string) $link->description),
            'amount'        => esc($amountLabel),
            'payment_url'   => esc((string) ($link->payment_url ?? ''), 'attr'),
        ];

        // render() throws (via fallback()) when the slug is unknown and has no
        // PHP view. Turn that into a clear, actionable message instead of an
        // opaque 500.
        try {
            $rendered = $this->emailTemplateService->render(self::TEMPLATE_SLUG, $vars);
        } catch (\Throwable $e) {
            log_message('error', 'Custom payment link: template render failed: ' . $e->getMessage());
            throw new HTTPException(
                'The "custom_payment_link" email template is missing. Run CustomPaymentLinkEmailSeeder.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if (trim((string) ($rendered['body'] ?? '')) === '') {
            throw new HTTPException(
                'The "custom_payment_link" email template is missing. Run CustomPaymentLinkEmailSeeder.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        try {
            $this->emailService->sendEmail($recipient, $rendered['subject'], $rendered['body']);
            $status = 'Sent';
        } catch (\Throwable $e) {
            log_message('error', 'Custom payment link email send failed: ' . $e->getMessage());
            $status = 'Failed';
        }

        if (!empty($link->reservation_id)) {
            $this->recordReservationTimeline(
                (string) $link->reservation_id,
                'Custom Payment Link Sent',
                'email',
                $recipient,
                (string) ($rendered['subject'] ?? 'Custom payment link'),
                (string) ($rendered['body'] ?? ''),
                $status
            );
        }

        if ($status === 'Failed') {
            throw new HTTPException('The payment link email could not be sent', Response::HTTP_BAD_GATEWAY);
        }
    }

    // ---------------------------------------------------------------------
    // Lifecycle
    // ---------------------------------------------------------------------

    public function cancelLink(string $id): object
    {
        $link = $this->getLink($id);

        if ($link->status === 'paid') {
            throw new HTTPException('Cannot cancel a paid payment link', Response::HTTP_BAD_REQUEST);
        }

        $this->repo->updateStatus($id, 'cancelled');

        return $this->getLink($id);
    }

    public function deleteLink(string $id): void
    {
        $this->getLink($id);
        $this->repo->delete($id);
    }

    // ---------------------------------------------------------------------
    // Payment completion (webhook + verifyPayment)
    // ---------------------------------------------------------------------

    /**
     * Mark a custom payment link as paid from a completed Stripe Checkout
     * Session. Treats the session metadata as untrusted: the referenced link
     * must exist in our DB. Idempotent — a second delivery keeps the first
     * `paid_at` (acceptance criterion 7). Never touches a reservation
     * (acceptance criterion 4).
     *
     * @param object $session The Stripe Checkout Session object.
     * @return bool true when the link is (now or already) paid.
     */
    public function handlePaidSession(object $session): bool
    {
        $metadata = $session->metadata ?? null;
        $linkId   = null;
        if (is_object($metadata)) {
            $linkId = $metadata->payment_link_id ?? null;
        } elseif (is_array($metadata)) {
            $linkId = $metadata['payment_link_id'] ?? null;
        }

        if (!$linkId) {
            log_message('error', 'Custom payment link webhook: missing payment_link_id in metadata');
            return false;
        }

        $link = $this->repo->findById((string) $linkId);
        if (!$link) {
            log_message('error', 'Custom payment link webhook: unknown payment_link_id ' . $linkId);
            return false;
        }

        if ($link->status === 'paid') {
            log_message('info', 'Custom payment link ' . $linkId . ' already marked as paid');
            return true;
        }

        $paymentIntentId = (string) ($session->payment_intent ?? '');
        $this->repo->markPaid((string) $linkId, $paymentIntentId, date('Y-m-d H:i:s'));

        $updated = $this->repo->findById((string) $linkId);

        if ($updated && !empty($updated->reservation_id)) {
            $this->recordReservationTimeline(
                (string) $updated->reservation_id,
                'Custom Payment Received',
                'payment',
                (string) ($updated->customer_email ?? ''),
                'Custom payment received',
                $this->buildPaymentReceivedBody($updated, $paymentIntentId),
                'Sent'
            );
        }

        return true;
    }

    // ---------------------------------------------------------------------
    // Validation helpers
    // ---------------------------------------------------------------------

    private function assertValidAmount($raw): float
    {
        if ($raw === null || $raw === '' || is_bool($raw) || is_array($raw) || !is_numeric($raw)) {
            throw new HTTPException('Amount must be a number', Response::HTTP_BAD_REQUEST);
        }

        $amount = round((float) $raw, 2);

        if ($amount < 0.01) {
            throw new HTTPException('Amount must be greater than 0', Response::HTTP_BAD_REQUEST);
        }

        if ($amount > self::MAX_AMOUNT) {
            throw new HTTPException(
                'Amount must not exceed $' . number_format(self::MAX_AMOUNT, 2),
                Response::HTTP_BAD_REQUEST
            );
        }

        return $amount;
    }

    private function assertValidDescription($raw): string
    {
        if (!is_string($raw)) {
            throw new HTTPException('Description is required', Response::HTTP_BAD_REQUEST);
        }

        $description = trim($raw);

        if ($description === '') {
            throw new HTTPException('Description is required', Response::HTTP_BAD_REQUEST);
        }

        if (mb_strlen($description) > 255) {
            throw new HTTPException('Description must be at most 255 characters', Response::HTTP_BAD_REQUEST);
        }

        return $description;
    }

    private function assertValidEmail($raw): string
    {
        if (!is_string($raw) || filter_var(trim($raw), FILTER_VALIDATE_EMAIL) === false) {
            throw new HTTPException('A valid customer email is required', Response::HTTP_BAD_REQUEST);
        }

        return strtolower(trim($raw));
    }

    private function assertValidReservationId($raw): ?string
    {
        if ($raw === null || $raw === '' || $raw === false) {
            return null;
        }

        if (!is_string($raw)) {
            throw new HTTPException('Invalid reservation reference', Response::HTTP_BAD_REQUEST);
        }

        $reservation = $this->reservationRepository->getById($raw);
        if (!$reservation) {
            throw new HTTPException('The referenced reservation does not exist', Response::HTTP_BAD_REQUEST);
        }

        return $raw;
    }

    private function normalizeName($raw): ?string
    {
        if (!is_string($raw)) {
            return null;
        }

        $name = trim($raw);

        return $name === '' ? null : mb_substr($name, 0, 150);
    }

    private function normalizeCurrency($raw): string
    {
        if (!is_string($raw) || trim($raw) === '') {
            return 'usd';
        }

        return mb_substr(strtolower(trim($raw)), 0, 10);
    }

    // ---------------------------------------------------------------------
    // Timeline recording (B3 equivalent pattern)
    // ---------------------------------------------------------------------

    /**
     * Insert a row in reservation_email_history. Mirrors
     * ReservationService::recordSystemEmail() (which is private): template_id
     * null, sent_by System. Failures are swallowed — timeline bookkeeping must
     * never break a payment or an email send.
     */
    private function recordReservationTimeline(
        string $reservationId,
        string $templateName,
        string $eventType,
        string $recipient,
        string $subject,
        string $body,
        string $status
    ): void {
        try {
            $this->historyModel()->insert([
                'reservation_id'  => $reservationId,
                'template_id'     => null,
                'template_name'   => $templateName,
                'event_type'      => $eventType,
                'sent_by'         => 'System',
                'recipient_email' => $recipient !== '' ? $recipient : '—',
                'cc_emails'       => null,
                'email_subject'   => mb_substr($subject, 0, 255),
                'email_body'      => $body !== '' ? $body : '—',
                'status'          => in_array($status, ['Sent', 'Failed', 'Pending'], true) ? $status : 'Sent',
                'sent_at'         => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Custom payment link: failed to record reservation timeline: ' . $e->getMessage());
        }
    }

    private function buildPaymentReceivedBody(object $link, string $paymentIntentId): string
    {
        return '<div style="font-family: Arial, sans-serif; font-size: 14px; color: #1F2937;">'
            . '<p style="margin: 0 0 8px;"><strong>Custom payment received via Stripe.</strong></p>'
            . '<p style="margin: 0 0 4px;">Description: ' . esc((string) $link->description) . '</p>'
            . '<p style="margin: 0 0 4px;">Amount: $' . esc(number_format((float) $link->amount, 2)) . '</p>'
            . '<p style="margin: 0;">Payment intent: ' . esc($paymentIntentId !== '' ? $paymentIntentId : 'N/A') . '</p>'
            . '</div>';
    }
}
