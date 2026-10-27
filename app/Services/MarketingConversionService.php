<?php

namespace App\Services;

use App\Models\MarketingConversionClaimModel;
use Config\MarketingTracking;

/**
 * Atomically claims browser conversion events. Tracking failures must never
 * affect the reservation/payment flow, so every persistence error fails open.
 */
class MarketingConversionService
{
    protected MarketingConversionClaimModel $model;
    protected MarketingTracking $config;

    public function __construct(
        ?MarketingConversionClaimModel $model = null,
        ?MarketingTracking $config = null
    ) {
        $this->model = $model ?? new MarketingConversionClaimModel();
        $this->config = $config ?? new MarketingTracking();
    }

    /**
     * @return array{event:string,transaction_id:string,value:float,currency:string}|null
     */
    public function claimStripePayment(string $transactionId, float $amount, string $currency = 'USD'): ?array
    {
        $transactionId = trim($transactionId);
        $currency = strtoupper(trim($currency));

        if ($transactionId === '' || $amount <= 0) {
            return null;
        }

        $eventKey = hash('sha256', 'payment_completed:' . $transactionId);

        try {
            if (!$this->storeClaim([
                'event_key'    => $eventKey,
                'event_type'   => 'payment_completed',
                'reference_id' => $transactionId,
                'amount'       => round($amount, 2),
                'currency'     => $currency !== '' ? substr($currency, 0, 3) : 'USD',
                'created_at'   => date('Y-m-d H:i:s'),
            ])) {
                return null;
            }
        } catch (\Throwable $e) {
            // A duplicate key is the expected result of a refresh or concurrent
            // verification. Other errors are logged, but never break payment.
            $message = strtolower($e->getMessage());
            $isDuplicate = (int) $e->getCode() === 1062
                || str_contains($message, 'duplicate')
                || str_contains($message, 'unique constraint');
            if (!$isDuplicate) {
                log_message('error', 'Marketing conversion claim failed: ' . $e->getMessage());
            }
            return null;
        }

        // Store the claim even while tracking is disabled. That prevents an old
        // success URL from becoming a new conversion if tracking is enabled later.
        if (!$this->config->enabled) {
            return null;
        }

        return [
            'event'          => 'payment_completed',
            'transaction_id' => $transactionId,
            'value'          => round($amount, 2),
            'currency'       => $currency !== '' ? substr($currency, 0, 3) : 'USD',
        ];
    }

    protected function storeClaim(array $data): bool
    {
        return $this->model->insert($data, false) !== false;
    }
}
