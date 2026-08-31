<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeService
{
    protected string $secretKey;
    protected string $webhookSecret;
    protected string $currency;

    public function __construct()
    {
        $this->secretKey = getenv('stripe.secretKey') ?: '';
        $this->webhookSecret = getenv('stripe.webhookSecret') ?: '';
        $this->currency = getenv('stripe.currency') ?: 'usd';

        Stripe::setApiKey($this->secretKey);
    }

    /**
     * Create a Stripe Checkout Session
     *
     * @param float $amount Total amount in dollars
     * @param string $customerEmail Customer email
     * @param string $reservationId Reservation UUID
     * @param string $description Line item description
     * @param float $gratuity Optional gratuity line item
     * @param array $metadata Extra Stripe metadata merged on top of the defaults.
     *                        Used by B5 custom payment links
     *                        (type = 'custom_payment_link', payment_link_id).
     *                        Added at the end with a default to keep the existing
     *                        signature backwards compatible.
     * @return Session
     */
    public function createCheckoutSession(
        float $amount,
        string $customerEmail,
        string $reservationId,
        string $description = 'Event Reservation',
        float $gratuity = 0.0,
        array $metadata = []
    ): Session {
        $frontendUrl = getenv('app.frontendURL') ?: 'http://localhost:8080';

        $lineItems = [[
            'price_data' => [
                'currency'     => $this->currency,
                'unit_amount'  => (int) round($amount * 100),
                'product_data' => ['name' => $description],
            ],
            'quantity' => 1,
        ]];

        if ($gratuity > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency'     => $this->currency,
                    'unit_amount'  => (int) round($gratuity * 100),
                    'product_data' => ['name' => 'Gratuity / Tip'],
                ],
                'quantity' => 1,
            ];
        }

        // Default metadata is unchanged for reservation payments; B5 links pass
        // an empty $reservationId and supply their own metadata instead.
        $meta = [];
        if ($reservationId !== '') {
            $meta['reservation_id'] = $reservationId;
        }
        $meta = array_merge($meta, $metadata);

        $cancelUrl = $reservationId !== ''
            ? rtrim($frontendUrl, '/') . '/payment-cancel?reservation_id=' . $reservationId
            : rtrim($frontendUrl, '/') . '/payment-cancel';

        $session = Session::create([
            'payment_method_types' => ['card'],
            'mode'           => 'payment',
            'customer_email' => $customerEmail,
            'line_items'     => $lineItems,
            'metadata'       => $meta,
            'success_url'    => rtrim($frontendUrl, '/') . '/payment-success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'     => $cancelUrl,
        ]);

        return $session;
    }

    /**
     * Verify webhook signature and return the event
     *
     * @param string $payload Raw request body
     * @param string $sigHeader Stripe-Signature header value
     * @return \Stripe\Event
     * @throws SignatureVerificationException
     */
    public function verifyWebhookSignature(string $payload, string $sigHeader): \Stripe\Event
    {
        return Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);
    }

    /**
     * Retrieve a Checkout Session by ID
     *
     * @param string $sessionId Stripe Session ID
     * @return Session
     */
    public function retrieveSession(string $sessionId): Session
    {
        return Session::retrieve($sessionId);
    }
}
