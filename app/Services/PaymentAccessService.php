<?php

namespace App\Services;

use App\Models\PaymentAccessTokenModel;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;

/**
 * Payment gateway: turns the durable, emailed link (`/pay/{token}`, 6 days)
 * into a fresh, short-lived Stripe Checkout Session (2h) created only at the
 * moment the customer actually clicks it.
 *
 * `target_type` is the flag that routes validation to the right flow —
 * `reservation` reuses ReservationService::regeneratePaymentSession(), which
 * already recomputes the amount from the current row and blocks paid/
 * cancelled reservations; `custom_payment_link` reuses the equivalent
 * CustomPaymentLinkService::regenerateSession().
 */
class PaymentAccessService
{
    protected PaymentAccessTokenModel $tokenModel;

    /** @var ReservationService|null Lazy — see getReservationService(). */
    protected $reservationService = null;

    /** @var CustomPaymentLinkService|null Lazy — see getCustomLinkService(). */
    protected $customLinkService = null;

    public function __construct()
    {
        $this->tokenModel = new PaymentAccessTokenModel();
    }

    protected function getReservationService(): ReservationService
    {
        if ($this->reservationService === null) {
            $this->reservationService = new ReservationService();
        }

        return $this->reservationService;
    }

    protected function getCustomLinkService(): CustomPaymentLinkService
    {
        if ($this->customLinkService === null) {
            $this->customLinkService = new CustomPaymentLinkService();
        }

        return $this->customLinkService;
    }

    /**
     * Issue a brand-new token for the target, invalidating whatever token it
     * had before. Call this whenever an email carrying the link is actually
     * sent (initial send, resend, reminder) — every send resets the 6-day
     * clock.
     */
    public function buildLink(string $targetType, string $targetId): string
    {
        $issued = $this->tokenModel->issueFor($targetType, $targetId);

        return $this->urlFor($issued->token);
    }

    /**
     * Get a usable link for the target WITHOUT resetting an already-valid
     * token's clock — for admin "view/copy" actions, which shouldn't silently
     * extend the customer's window every time someone opens the list.
     */
    public function ensureLink(string $targetType, string $targetId): string
    {
        $active = $this->tokenModel->findActiveFor($targetType, $targetId);
        $token  = $active->token ?? $this->tokenModel->issueFor($targetType, $targetId)->token;

        return $this->urlFor($token);
    }

    /**
     * Redeem a gateway token: validate it, then mint a fresh ~2h Stripe
     * Checkout Session for whatever it points to.
     *
     * @return string The Stripe Checkout URL to redirect the customer to.
     * @throws HTTPException 404/410 unknown or expired token, or whatever the
     *         underlying regenerate call throws (400 cancelled, 409 already
     *         paid). Never 401/403/419 — this is a public, unauthenticated
     *         endpoint and those codes would bounce the frontend to /login.
     */
    public function redeem(string $token, int $sessionLifetimeSeconds = 7200): string
    {
        $record = $this->tokenModel->findValid($token);

        if (!$record) {
            throw new HTTPException('This payment link has expired or is no longer valid.', Response::HTTP_GONE);
        }

        $result = $record->target_type === 'custom_payment_link'
            ? $this->getCustomLinkService()->regenerateSession((string) $record->target_id, $sessionLifetimeSeconds)
            : $this->getReservationService()->regeneratePaymentSession((string) $record->target_id, $sessionLifetimeSeconds);

        return (string) $result['payment_url'];
    }

    private function urlFor(string $token): string
    {
        $frontendUrl = getenv('app.frontendURL') ?: 'http://localhost:5173';

        return rtrim($frontendUrl, '/') . '/pay/' . $token;
    }
}
