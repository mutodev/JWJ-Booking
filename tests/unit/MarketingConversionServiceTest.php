<?php

namespace Tests\Unit;

use App\Services\MarketingConversionService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\MarketingTracking;

class MarketingConversionServiceTest extends CIUnitTestCase
{
    public function testPaidStripeTransactionIsReturnedOnlyOnce(): void
    {
        $service = new InMemoryMarketingConversionService($this->config(true));

        $first = $service->claimStripePayment('pi_123', 42.129, 'usd');
        $second = $service->claimStripePayment('pi_123', 42.129, 'usd');

        $this->assertSame([
            'event' => 'payment_completed',
            'transaction_id' => 'pi_123',
            'value' => 42.13,
            'currency' => 'USD',
        ], $first);
        $this->assertNull($second);
        $this->assertCount(1, $service->claims);
    }

    public function testDisabledTrackingStillClaimsWithoutReturningBrowserEvent(): void
    {
        $service = new InMemoryMarketingConversionService($this->config(false));

        $this->assertNull($service->claimStripePayment('pi_disabled', 20, 'USD'));
        $this->assertCount(1, $service->claims);
    }

    public function testInvalidTransactionDoesNotCreateClaim(): void
    {
        $service = new InMemoryMarketingConversionService($this->config(true));

        $this->assertNull($service->claimStripePayment('', 20, 'USD'));
        $this->assertNull($service->claimStripePayment('pi_zero', 0, 'USD'));
        $this->assertSame([], $service->claims);
    }

    private function config(bool $enabled): MarketingTracking
    {
        $config = new MarketingTracking();
        $config->enabled = $enabled;
        return $config;
    }
}

class InMemoryMarketingConversionService extends MarketingConversionService
{
    public array $claims = [];

    public function __construct(MarketingTracking $config)
    {
        $this->config = $config;
    }

    protected function storeClaim(array $data): bool
    {
        if (isset($this->claims[$data['event_key']])) {
            return false;
        }

        $this->claims[$data['event_key']] = $data;
        return true;
    }
}
