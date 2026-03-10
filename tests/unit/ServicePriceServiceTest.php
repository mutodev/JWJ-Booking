<?php

namespace Tests\Unit;

use App\Services\ServicePriceService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Tests for ServicePriceService — bulk base price update.
 * Injects a mock repository via reflection to avoid DB dependency.
 *
 * @internal
 */
final class ServicePriceServiceTest extends CIUnitTestCase
{
    private ServicePriceService $service;
    private object $repoMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoMock = new class {
            public ?string $lastServiceId = null;
            public ?float  $lastAmount    = null;
            public int     $returnCount   = 0;

            public function updateBasePriceByService(string $serviceId, float $amount): int
            {
                $this->lastServiceId = $serviceId;
                $this->lastAmount    = $amount;
                return $this->returnCount;
            }
        };

        $this->service = new ServicePriceService();

        $ref = new \ReflectionProperty(ServicePriceService::class, 'repo');
        $ref->setAccessible(true);
        $ref->setValue($this->service, $this->repoMock);
    }

    public function testUpdateBasePriceForwardsDelegateToRepository(): void
    {
        $this->repoMock->returnCount = 5;

        $result = $this->service->updateBasePriceByService('service-abc', 150.00);

        $this->assertEquals('service-abc', $this->repoMock->lastServiceId);
        $this->assertEquals(150.00, $this->repoMock->lastAmount);
        $this->assertEquals(5, $result);
    }

    public function testUpdateBasePriceReturnsZeroWhenNothingUpdated(): void
    {
        $this->repoMock->returnCount = 0;

        $result = $this->service->updateBasePriceByService('nonexistent-service', 99.99);

        $this->assertEquals(0, $result);
    }

    public function testUpdateBasePricePassesExactAmountIncludingDecimals(): void
    {
        $this->repoMock->returnCount = 3;

        $this->service->updateBasePriceByService('svc-1', 249.95);

        $this->assertEquals(249.95, $this->repoMock->lastAmount);
    }
}
