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
     * @return Session
     */
    public function createCheckoutSession(
        float $amount,
        string $customerEmail,
        string $reservationId,
        string $description = 'Event Reservation'
    ): Session {
        $frontendUrl = getenv('app.frontendURL') ?: 'http://localhost:8080';

        $session = Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'customer_email' => $customerEmail,
            'line_items' => [[
                'price_data' => [
                    'currency' => $this->currency,
                    'unit_amount' => (int) round($amount * 100), // Convert to cents
                    'product_data' => [
                        'name' => $description,
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'reservation_id' => $reservationId,
            ],
            'success_url' => rtrim($frontendUrl, '/') . '/payment-success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => rtrim($frontendUrl, '/') . '/payment-cancel?reservation_id=' . $reservationId,
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
