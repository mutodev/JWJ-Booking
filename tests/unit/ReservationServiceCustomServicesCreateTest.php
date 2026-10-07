<?php

namespace Tests\Unit;

use App\Entities\CustomService;
use App\Services\ReservationService;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Tests for ReservationService::create() with optional custom services
 * (admin reservation modal). Repositories are injected via reflection.
 *
 * @internal
 */
final class ReservationServiceCustomServicesCreateTest extends CIUnitTestCase
{
    private ReservationService $service;
    private object $repoMock;
    private object $catalogMock;
    private object $pivotMock;
    private array $baseData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoMock = new class {
            public array $lastCreated = [];
            public function create(array $data)
            {
                $this->lastCreated = $data;
                return (object) array_merge(['id' => 'res-uuid'], $data);
            }
        };

        $this->catalogMock = new class {
            public array $items = [];
            public function getById(string $id): ?CustomService
            {
                return $this->items[$id] ?? null;
            }
        };

        $this->pivotMock = new class {
            public array $created = [];
            public function create(array $data)
            {
                $this->created[] = $data;
                return 'pivot-' . count($this->created);
            }
        };

        $this->catalogMock->items = [
            'cs-1' => new CustomService(['id' => 'cs-1', 'name' => 'Face Painting', 'detail' => '1 hour', 'price' => 150.0, 'is_active' => 1]),
            'cs-2' => new CustomService(['id' => 'cs-2', 'name' => 'Balloon Art', 'detail' => null, 'price' => 80.0, 'is_active' => 1]),
            'cs-off' => new CustomService(['id' => 'cs-off', 'name' => 'Old', 'detail' => null, 'price' => 10.0, 'is_active' => 0]),
        ];

        $this->service = new ReservationService();
        $this->inject('repository', $this->repoMock);
        $this->inject('customServiceRepository', $this->catalogMock);
        $this->inject('reservationCustomServiceRepository', $this->pivotMock);

        $this->baseData = [
            'customer' => ['id' => 'cust-1'],
            'price'    => [
                'id'               => 'price-1',
                'amount'           => 200.00,
                'extra_child_fee'  => 10.00,
                'performers_count' => 1,
            ],
            'areas'  => ['zipcode' => ['id' => 'zip-1']],
            'addons' => [],
            'form'   => [
                'date'          => (new \DateTime('+30 days'))->format('Y-m-d'),
                'startTime'     => '14:00',
                'extraChildren' => 0,
                'eventAddress'  => '123 Main St',
            ],
        ];
    }

    private function inject(string $prop, $value): void
    {
        $ref = new \ReflectionProperty(ReservationService::class, $prop);
        $ref->setAccessible(true);
        $ref->setValue($this->service, $value);
    }

    public function testWithoutCustomServicesNothingChanges(): void
    {
        $this->service->create($this->baseData);

        $this->assertSame(0.0, $this->repoMock->lastCreated['custom_services_total']);
        $this->assertEquals(200.0, $this->repoMock->lastCreated['total_amount']);
        $this->assertSame([], $this->pivotMock->created);
    }

    public function testCustomServicesAreSummedWithReservationPrice(): void
    {
        $this->baseData['customServices'] = [
            ['custom_service_id' => 'cs-1', 'price' => 120.00],
            ['custom_service_id' => 'cs-2', 'price' => 80],
        ];

        $this->service->create($this->baseData);

        $saved = $this->repoMock->lastCreated;
        $this->assertSame(200.0, $saved['custom_services_total']);
        $this->assertEquals(400.0, $saved['total_amount']);
    }

    public function testPivotRowsSnapshotCatalogDataAndReservationPrice(): void
    {
        $this->baseData['customServices'] = [
            ['custom_service_id' => 'cs-1', 'price' => 99.999, 'name' => 'forged name'],
        ];

        $this->service->create($this->baseData);

        $this->assertCount(1, $this->pivotMock->created);
        $row = $this->pivotMock->created[0];
        $this->assertSame('res-uuid', $row['reservation_id']);
        $this->assertSame('cs-1', $row['custom_service_id']);
        $this->assertSame('Face Painting', $row['name']);
        $this->assertSame('1 hour', $row['detail']);
        $this->assertSame(150.0, $row['catalog_price']);
        $this->assertSame(100.0, $row['price_at_time']);
        $this->assertNotEmpty($row['created_by']);
    }

    public function testZeroPriceIsAllowed(): void
    {
        $this->baseData['customServices'] = [['custom_service_id' => 'cs-1', 'price' => 0]];

        $this->service->create($this->baseData);

        $this->assertSame(0.0, $this->repoMock->lastCreated['custom_services_total']);
        $this->assertEquals(200.0, $this->repoMock->lastCreated['total_amount']);
    }

    public function testPromoDiscountSentByClientIsNotAffectedByCustomServices(): void
    {
        $this->baseData['customServices'] = [['custom_service_id' => 'cs-1', 'price' => 100]];
        $this->baseData['promoCode'] = ['discount_amount' => 20.0];

        $this->service->create($this->baseData);

        // 200 + 100 - 20
        $this->assertEquals(280.0, $this->repoMock->lastCreated['total_amount']);
        $this->assertEquals(20.0, $this->repoMock->lastCreated['discount_amount']);
    }

    /**
     * @dataProvider invalidCustomServicesProvider
     */
    public function testInvalidCustomServicesAreRejectedBeforeSaving(array $items): void
    {
        $this->baseData['customServices'] = $items;

        try {
            $this->service->create($this->baseData);
            $this->fail('expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertSame(400, $e->getCode());
        }

        $this->assertSame([], $this->repoMock->lastCreated, 'no reservation is created');
        $this->assertSame([], $this->pivotMock->created);
    }

    public static function invalidCustomServicesProvider(): array
    {
        return [
            'duplicated'     => [[['custom_service_id' => 'cs-1', 'price' => 10], ['custom_service_id' => 'cs-1', 'price' => 20]]],
            'unknown'        => [[['custom_service_id' => 'nope', 'price' => 10]]],
            'inactive'       => [[['custom_service_id' => 'cs-off', 'price' => 10]]],
            'missing id'     => [[['price' => 10]]],
            'negative price' => [[['custom_service_id' => 'cs-1', 'price' => -5]]],
            'missing price'  => [[['custom_service_id' => 'cs-1']]],
            'text price'     => [[['custom_service_id' => 'cs-1', 'price' => 'abc']]],
        ];
    }
}
