<?php

namespace Tests\Unit;

use App\Repositories\ReservationAddonRepository;
use App\Services\ReservationAddonService;
use App\Services\ReservationService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * B6 — ReservationAddonService::create/update/delete disparan el recalculo de
 * totales de la reserva y devuelven los totales nuevos en la respuesta
 * (criterio de aceptacion 5).
 *
 * Reglas verificadas:
 *  - forma de la respuesta: create -> {id, totals}; update -> {updated, totals};
 *    delete -> {deleted, totals}. `totals` = lo que devolvio recalculateTotals().
 *  - si recalculateTotals() lanza, `totals` es null y la operacion de add-on
 *    NO se revierte (el metodo no propaga).
 *  - reservation_id ausente / no-UUID -> `totals` null SIN llamar al servicio.
 *
 * Sin base de datos: se doblan el repositorio de add-ons y la propiedad lazy
 * `reservationService` (subclase anonima de ReservationService con __construct
 * vacio).
 *
 * @internal
 */
final class ReservationAddonServiceRecalcTest extends CIUnitTestCase
{
    private const UUID = '550e8400-e29b-41d4-a716-446655440000';
    private const ADDON_UUID = '6ba7b810-9dad-41d1-80b4-00c04fd430c8';

    private ReservationAddonService $service;
    private object $repo;
    private object $reservationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new class extends ReservationAddonRepository {
            public $createReturn = 'new-addon-id';
            public $updateReturn = true;
            public $deleteReturn = true;
            /** @var object|array|null */
            public $existing;
            /** @var array<int,mixed> */
            public array $calls = [];

            public function __construct()
            {
            }

            public function create(array $data)
            {
                $this->calls[] = ['create', $data];

                return $this->createReturn;
            }

            public function update(string $id, array $data): bool
            {
                $this->calls[] = ['update', $id, $data];

                return (bool) $this->updateReturn;
            }

            public function delete(string $id): bool
            {
                $this->calls[] = ['delete', $id];

                return (bool) $this->deleteReturn;
            }

            public function getById(string $id)
            {
                return $this->existing;
            }
        };

        $this->reservationService = new class extends ReservationService {
            public array $recalcCalls = [];
            public ?\Throwable $throw = null;
            public object $result;

            public function __construct()
            {
                $this->result = (object) ['id' => 'res', 'total_amount' => 321.0, 'balance_due' => 0.0];
            }

            public function recalculateTotals(string $reservationId): object
            {
                $this->recalcCalls[] = $reservationId;
                if ($this->throw !== null) {
                    throw $this->throw;
                }

                return $this->result;
            }
        };

        $this->service = new ReservationAddonService();
        $this->inject('repository', $this->repo);
        $this->inject('reservationService', $this->reservationService);
    }

    private function inject(string $prop, $value): void
    {
        $ref = new \ReflectionProperty(ReservationAddonService::class, $prop);
        $ref->setAccessible(true);
        $ref->setValue($this->service, $value);
    }

    private function validCreateData(array $override = []): array
    {
        return array_merge([
            'reservation_id' => self::UUID,
            'addon_id'       => self::ADDON_UUID,
            'quantity'       => 2,
            'price_at_time'  => 50.0,
        ], $override);
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function testCreateReturnsIdAndRecalculatedTotals(): void
    {
        $out = $this->service->create($this->validCreateData());

        $this->assertSame('new-addon-id', $out['id']);
        $this->assertSame($this->reservationService->result, $out['totals']);
        $this->assertSame([self::UUID], $this->reservationService->recalcCalls);
    }

    public function testCreateWithFailingRecalcStillReturnsIdAndNullTotals(): void
    {
        $this->reservationService->throw = new \RuntimeException('recalc boom');

        $out = $this->service->create($this->validCreateData());

        $this->assertSame('new-addon-id', $out['id'], 'el add-on ya persistido no se revierte');
        $this->assertNull($out['totals']);
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function testUpdateReturnsUpdatedFlagAndTotals(): void
    {
        $this->repo->existing = (object) ['id' => 'addon-1', 'reservation_id' => self::UUID];

        $out = $this->service->update('addon-1', ['quantity' => 3]);

        $this->assertTrue($out['updated']);
        $this->assertSame($this->reservationService->result, $out['totals']);
        $this->assertSame([self::UUID], $this->reservationService->recalcCalls);
    }

    public function testUpdateWithFailingRecalcReturnsNullTotals(): void
    {
        $this->repo->existing = (object) ['id' => 'addon-1', 'reservation_id' => self::UUID];
        $this->reservationService->throw = new \RuntimeException('boom');

        $out = $this->service->update('addon-1', ['quantity' => 3]);

        $this->assertTrue($out['updated']);
        $this->assertNull($out['totals']);
    }

    // -------------------------------------------------------------------------
    // delete
    // -------------------------------------------------------------------------

    public function testDeleteReturnsDeletedFlagAndTotals(): void
    {
        $this->repo->existing = (object) ['id' => 'addon-1', 'reservation_id' => self::UUID];

        $out = $this->service->delete('addon-1');

        $this->assertTrue($out['deleted']);
        $this->assertSame($this->reservationService->result, $out['totals']);
        $this->assertSame([self::UUID], $this->reservationService->recalcCalls);
    }

    public function testDeleteWithNullReservationIdSkipsRecalcAndReturnsNullTotals(): void
    {
        $this->repo->existing = (object) ['id' => 'addon-1', 'reservation_id' => null];

        $out = $this->service->delete('addon-1');

        $this->assertTrue($out['deleted']);
        $this->assertNull($out['totals']);
        $this->assertSame([], $this->reservationService->recalcCalls, 'sin reservation_id no se llama al servicio');
    }

    public function testDeleteWithNonUuidReservationIdSkipsRecalc(): void
    {
        $this->repo->existing = (object) ['id' => 'addon-1', 'reservation_id' => 'not-a-uuid'];

        $out = $this->service->delete('addon-1');

        $this->assertTrue($out['deleted']);
        $this->assertNull($out['totals']);
        $this->assertSame([], $this->reservationService->recalcCalls);
    }
}
