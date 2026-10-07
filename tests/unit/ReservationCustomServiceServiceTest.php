<?php

namespace Tests\Unit;

use App\Entities\CustomService;
use App\Entities\ReservationCustomService;
use App\Services\ReservationCustomServiceService;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Tests for ReservationCustomServiceService — add / change price / remove a
 * custom service on an existing reservation, each one triggering a recalc.
 *
 * @internal
 */
final class ReservationCustomServiceServiceTest extends CIUnitTestCase
{
    private const RES_ID = '11111111-1111-4111-8111-111111111111';
    private const CS_ID  = '22222222-2222-4222-8222-222222222222';
    private const ROW_ID = '33333333-3333-4333-8333-333333333333';

    private ReservationCustomServiceService $service;
    private object $pivot;
    private object $catalog;
    private object $reservations;
    private object $reservationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pivot = new class {
            public array $rows = [];
            public ?array $lastCreate = null;
            public ?array $lastUpdate = null;
            public ?string $deleted = null;

            public function getById(string $id): ?ReservationCustomService
            {
                return $this->rows[$id] ?? null;
            }

            public function findInReservation(string $reservationId, string $customServiceId): ?ReservationCustomService
            {
                foreach ($this->rows as $row) {
                    if ($row->reservation_id === $reservationId && $row->custom_service_id === $customServiceId) {
                        return $row;
                    }
                }
                return null;
            }

            public function create(array $data)
            {
                $this->lastCreate = $data;
                return 'new-row';
            }

            public function update(string $id, array $data): bool
            {
                $this->lastUpdate = $data;
                return true;
            }

            public function delete(string $id): bool
            {
                $this->deleted = $id;
                return true;
            }
        };

        $this->catalog = new class {
            public array $items = [];
            public function getById(string $id): ?CustomService
            {
                return $this->items[$id] ?? null;
            }
        };
        $this->catalog->items[self::CS_ID] = new CustomService([
            'id' => self::CS_ID, 'name' => 'Face Painting', 'detail' => '1 hour', 'price' => 150.0, 'is_active' => 1,
        ]);

        $this->reservations = new class {
            public array $existing = [];
            public function getById(string $id)
            {
                return in_array($id, $this->existing, true) ? (object) ['id' => $id] : null;
            }
        };
        $this->reservations->existing = [self::RES_ID];

        $this->reservationService = new class {
            public array $recalculated = [];
            public function recalculateTotals(string $id): object
            {
                $this->recalculated[] = $id;
                return (object) ['id' => $id, 'total_amount' => 999.0];
            }
        };

        $this->service = new ReservationCustomServiceService();
        $this->inject('repository', $this->pivot);
        $this->inject('catalogRepository', $this->catalog);
        $this->inject('reservationRepository', $this->reservations);
        $this->inject('reservationService', $this->reservationService);
    }

    private function inject(string $prop, $value): void
    {
        $ref = new \ReflectionProperty(ReservationCustomServiceService::class, $prop);
        $ref->setAccessible(true);
        $ref->setValue($this->service, $value);
    }

    private function seedRow(): void
    {
        $this->pivot->rows[self::ROW_ID] = new ReservationCustomService([
            'id' => self::ROW_ID, 'reservation_id' => self::RES_ID, 'custom_service_id' => self::CS_ID,
            'name' => 'Face Painting', 'catalog_price' => 150.0, 'price_at_time' => 150.0,
        ]);
    }

    public function testCreateUsesCatalogPriceByDefaultAndRecalculates(): void
    {
        $result = $this->service->create(['reservation_id' => self::RES_ID, 'custom_service_id' => self::CS_ID], 'Admin');

        $this->assertSame(150.0, $this->pivot->lastCreate['price_at_time']);
        $this->assertSame(150.0, $this->pivot->lastCreate['catalog_price']);
        $this->assertSame('Face Painting', $this->pivot->lastCreate['name']);
        $this->assertSame('Admin', $this->pivot->lastCreate['created_by']);
        $this->assertSame([self::RES_ID], $this->reservationService->recalculated);
        $this->assertSame(999.0, $result['totals']->total_amount);
    }

    public function testCreateAcceptsReservationSpecificPrice(): void
    {
        $this->service->create(['reservation_id' => self::RES_ID, 'custom_service_id' => self::CS_ID, 'price_at_time' => '99.5'], 'Admin');

        $this->assertSame(99.5, $this->pivot->lastCreate['price_at_time']);
    }

    public function testCreateRejectsDuplicate(): void
    {
        $this->seedRow();

        $this->expectException(HTTPException::class);
        $this->expectExceptionCode(409);

        $this->service->create(['reservation_id' => self::RES_ID, 'custom_service_id' => self::CS_ID], 'Admin');
    }

    public function testCreateRejectsUnknownReservation(): void
    {
        $this->reservations->existing = [];

        $this->expectException(HTTPException::class);
        $this->expectExceptionCode(404);

        $this->service->create(['reservation_id' => self::RES_ID, 'custom_service_id' => self::CS_ID], 'Admin');
    }

    public function testCreateRejectsInactiveCatalogItem(): void
    {
        $this->catalog->items[self::CS_ID]->is_active = 0;

        $this->expectException(HTTPException::class);
        $this->expectExceptionCode(400);

        $this->service->create(['reservation_id' => self::RES_ID, 'custom_service_id' => self::CS_ID], 'Admin');
    }

    public function testCreateRejectsInvalidUuidAndNegativePrice(): void
    {
        try {
            $this->service->create(['reservation_id' => 'bad', 'custom_service_id' => self::CS_ID], 'Admin');
            $this->fail('expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertSame(400, $e->getCode());
        }

        $this->expectException(HTTPException::class);
        $this->expectExceptionCode(400);
        $this->service->create(['reservation_id' => self::RES_ID, 'custom_service_id' => self::CS_ID, 'price_at_time' => -1], 'Admin');
    }

    public function testUpdateChangesOnlyPriceAndRecalculates(): void
    {
        $this->seedRow();

        $this->service->update(self::ROW_ID, ['price_at_time' => 175, 'name' => 'forged'], 'Editor');

        $this->assertSame(['price_at_time' => 175.0, 'updated_by' => 'Editor'], $this->pivot->lastUpdate);
        $this->assertSame([self::RES_ID], $this->reservationService->recalculated);
    }

    public function testUpdateRequiresPrice(): void
    {
        $this->seedRow();

        $this->expectException(HTTPException::class);
        $this->expectExceptionCode(400);

        $this->service->update(self::ROW_ID, [], 'Editor');
    }

    public function testDeleteRemovesAndRecalculates(): void
    {
        $this->seedRow();

        $result = $this->service->delete(self::ROW_ID);

        $this->assertTrue($result['deleted']);
        $this->assertSame(self::ROW_ID, $this->pivot->deleted);
        $this->assertSame([self::RES_ID], $this->reservationService->recalculated);
    }

    public function testDeleteUnknownRowIs404(): void
    {
        $this->expectException(HTTPException::class);
        $this->expectExceptionCode(404);

        $this->service->delete(self::ROW_ID);
    }

    public function testRecalculationFailureDoesNotUndoTheChange(): void
    {
        $this->seedRow();
        $this->inject('reservationService', new class {
            public function recalculateTotals(string $id): object
            {
                throw new \RuntimeException('boom');
            }
        });

        $result = $this->service->update(self::ROW_ID, ['price_at_time' => 10], 'Editor');

        $this->assertTrue($result['updated']);
        $this->assertNull($result['totals']);
    }
}
