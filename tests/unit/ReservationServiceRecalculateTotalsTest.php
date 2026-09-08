<?php

namespace Tests\Unit;

use App\Models\ReservationEmailHistoryModel;
use App\Repositories\ReservationAddonRepository;
use App\Repositories\ReservationRepository;
use App\Repositories\ServicePriceRepository;
use App\Repositories\ZipCodeRepository;
use App\Services\ReservationService;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * B6 — ReservationService::recalculateTotals().
 *
 * COSTURA DE BD (fix del certifier): recalculateTotals() ahora obtiene la
 * conexion via el metodo protegido `db()` (ReservationService.php:1165). Este
 * test lo dobla con una subclase anonima que devuelve un fake de conexion no-op
 * (transStart / transComplete / transStatus / transRollback), lo que permite
 * ejecutar TODO el cuerpo de recalculateTotals() sin base de datos real.
 *
 * Todos los repositorios que toca el metodo estan doblados:
 *  - repository (ReservationRepository): getById + update (spy del payload)
 *  - servicePriceRepository: getByIdWithService
 *  - zipCodeRepository: getById
 *  - reservationAddonRepository: getForRecalculation
 *  - promoCodeRepository: findByCode (+ catch-all para detectar incrementos)
 *  - historyModel: insert (timeline B3)
 *
 * Cubre criterios de aceptacion B6: 2 (balance_due), 3 (promo sin incremento de
 * contador), 4 (expedite fee congelado), 7 (snapshot de amount_paid) y los dos
 * defectos de propina del certifier (bug #2: la propina ya no absorbe cargos ni
 * genera saldo fantasma en el balance).
 *
 * @internal
 */
final class ReservationServiceRecalculateTotalsTest extends CIUnitTestCase
{
    private ReservationService $service;
    private object $repo;
    private object $servicePriceRepo;
    private object $zipRepo;
    private object $addonRepo;
    private object $promoRepo;
    private object $history;
    private object $dbFake;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new class extends ReservationRepository {
            /** @var array<string,?object> */
            public array $rows = [];
            /** @var array<int,string> */
            public array $getByIdCalls = [];
            /** @var array<int,array{0:string,1:array}> */
            public array $updateCalls = [];
            public bool $updateReturnsFalsy = false;
            public ?\Throwable $updateThrow = null;

            public function __construct()
            {
            }

            public function getById(string $id)
            {
                $this->getByIdCalls[] = $id;

                return $this->rows[$id] ?? null;
            }

            public function update(string $id, array $data)
            {
                $this->updateCalls[] = [$id, $data];

                if ($this->updateThrow !== null) {
                    throw $this->updateThrow;
                }
                if ($this->updateReturnsFalsy) {
                    return false;
                }

                return (object) array_merge((array) ($this->rows[$id] ?? []), $data);
            }
        };

        $this->servicePriceRepo = new class extends ServicePriceRepository {
            /** @var array<string,mixed>|null */
            public ?array $row = null;
            /** @var array<int,string> */
            public array $calls = [];

            public function __construct()
            {
            }

            public function getByIdWithService(string $id): ?array
            {
                $this->calls[] = $id;

                return $this->row;
            }
        };

        $this->zipRepo = new class extends ZipCodeRepository {
            /** @var \App\Entities\Zipcode|null */
            public $zip = null;
            /** @var array<int,string> */
            public array $calls = [];

            public function __construct()
            {
            }

            public function getById(string $id): ?\App\Entities\Zipcode
            {
                $this->calls[] = $id;

                return $this->zip;
            }
        };

        $this->addonRepo = new class extends ReservationAddonRepository {
            /** @var array<int,array<string,mixed>> */
            public array $addonRows = [];
            /** @var array<int,string> */
            public array $calls = [];

            public function __construct()
            {
            }

            public function getForRecalculation(string $reservationId): array
            {
                $this->calls[] = $reservationId;

                return $this->addonRows;
            }
        };

        $this->promoRepo = new class {
            /** @var array<string,mixed>|null */
            public ?array $promo = null;
            /** @var array<int,array{0:string,1:array}> */
            public array $calls = [];

            public function findByCode(string $code): ?array
            {
                $this->calls[] = ['findByCode', [$code]];

                return $this->promo;
            }

            public function __call($name, $args)
            {
                $this->calls[] = [$name, $args];

                return null;
            }
        };

        $this->history = new class extends ReservationEmailHistoryModel {
            /** @var array<int,array<string,mixed>> */
            public array $inserts = [];

            public function insert($row = null, bool $returnID = true)
            {
                $this->inserts[] = (array) $row;

                return 'fake-history-id';
            }
        };

        $this->dbFake = new class {
            /** @var array<int,string> */
            public array $calls = [];
            public bool $status = true;

            public function transStart(): void
            {
                $this->calls[] = 'transStart';
            }

            public function transComplete(): void
            {
                $this->calls[] = 'transComplete';
            }

            public function transStatus(): bool
            {
                $this->calls[] = 'transStatus';

                return $this->status;
            }

            public function transRollback(): void
            {
                $this->calls[] = 'transRollback';
            }
        };

        $fakeDb = $this->dbFake;
        $this->service = new class ($fakeDb) extends ReservationService {
            private $fakeDb;

            public function __construct($fakeDb)
            {
                $this->fakeDb = $fakeDb;
            }

            protected function db()
            {
                return $this->fakeDb;
            }
        };

        $this->inject('repository', $this->repo);
        $this->inject('servicePriceRepository', $this->servicePriceRepo);
        $this->inject('zipCodeRepository', $this->zipRepo);
        $this->inject('reservationAddonRepository', $this->addonRepo);
        $this->inject('promoCodeRepository', $this->promoRepo);
        $this->inject('historyModel', $this->history);
    }

    private function inject(string $prop, $value): void
    {
        $ref = new \ReflectionProperty(ReservationService::class, $prop);
        $ref->setAccessible(true);
        $ref->setValue($this->service, $value);
    }

    /**
     * Reserva base: servicio $500, sin zipcode, sin add-ons, sin niños extra,
     * evento lejano (expedite recalculado = 0), expedite congelado = 0.
     */
    private function seed(array $override = []): object
    {
        $row = (object) array_merge([
            'id'               => 'res-1',
            'service_price_id' => 'sp-1',
            'zipcode_id'       => null,
            'children_count'   => 0,
            'performers_count' => 1,
            'event_date'       => date('Y-m-d', strtotime('+30 days')),
            'duration_hours'   => 2.0,
            'base_price'       => 500.0,
            'addons_total'     => 0.0,
            'extra_children_fee' => 0.0,
            'travel_fee'       => 0.0,
            'expedite_fee'     => 0.0,
            'expedition_fee'   => 0.0,
            'discount_amount'  => 0.0,
            'total_amount'     => 500.0,
            'promo_code'       => null,
            'gratuity_amount'  => 0.0,
            'is_paid'          => false,
            'amount_paid'      => null,
            'email'            => 'client@example.com',
        ], $override);

        $this->repo->rows[$row->id] = $row;

        $this->servicePriceRepo->row = array_merge([
            'amount'                 => 500.0,
            'travel_fee'             => 0.0,
            'extra_child_fee'        => 25.0,
            'service_duration_hours' => 2.0,
        ], $override['_servicePrice'] ?? []);

        return $row;
    }

    /** Payload que recibió repository->update() en la última llamada. */
    private function lastUpdate(): array
    {
        $this->assertNotEmpty($this->repo->updateCalls, 'se esperaba una llamada a repository->update()');

        return $this->repo->updateCalls[array_key_last($this->repo->updateCalls)][1];
    }

    // -------------------------------------------------------------------------
    // Guard 404 (antes del transStart) — preexistente
    // -------------------------------------------------------------------------

    public function testThrows404WhenReservationDoesNotExist(): void
    {
        try {
            $this->service->recalculateTotals('missing-id');
            $this->fail('expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertSame(404, $e->getCode());
            $this->assertStringContainsString('not found', strtolower($e->getMessage()));
        }

        $this->assertSame(['missing-id'], $this->repo->getByIdCalls);
        $this->assertSame([], $this->dbFake->calls, 'el 404 ocurre antes de abrir la transacción');
    }

    public function testDoesNotTouchPromoRepositoryWhenReservationMissing(): void
    {
        try {
            $this->service->recalculateTotals('missing-id');
        } catch (HTTPException $e) {
            // esperado
        }

        $this->assertSame([], $this->promoRepo->calls, 'el 404 no consulta ni incrementa el promo');
    }

    // -------------------------------------------------------------------------
    // C6 — balance_due nunca negativo
    // -------------------------------------------------------------------------

    public function testBalanceDueIsClampedToZeroWhenNewTotalIsBelowAmountPaid(): void
    {
        // Pagó $500; el servicio bajó a $300 -> delta negativo -> balance 0.
        $this->seed([
            'is_paid'        => true,
            'amount_paid'    => 500.0,
            'total_amount'   => 500.0,
            '_servicePrice'  => ['amount' => 300.0],
        ]);

        $this->service->recalculateTotals('res-1');
        $update = $this->lastUpdate();

        $this->assertSame(300.0, $update['total_amount']);
        $this->assertSame(0.0, $update['balance_due']);
        $this->assertGreaterThanOrEqual(0.0, $update['balance_due']);
    }

    // -------------------------------------------------------------------------
    // C7 — snapshot de amount_paid NULL + is_paid
    // -------------------------------------------------------------------------

    public function testSnapshotsAmountPaidFromPreviousTotalPlusGratuityWhenNull(): void
    {
        // Reserva histórica: amount_paid NULL, is_paid true, total viejo 400,
        // propina 50 -> snapshot amount_paid = 450. Nuevo total == viejo -> bal 0.
        $this->seed([
            'is_paid'        => true,
            'amount_paid'    => null,
            'total_amount'   => 400.0,
            'gratuity_amount' => 50.0,
            '_servicePrice'  => ['amount' => 400.0],
        ]);

        $this->service->recalculateTotals('res-1');
        $update = $this->lastUpdate();

        $this->assertArrayHasKey('amount_paid', $update);
        $this->assertSame(450.0, $update['amount_paid'], 'snapshot = total previo + propina, NO solo total');
        $this->assertSame(400.0, $update['total_amount']);
        $this->assertSame(0.0, $update['balance_due']);
    }

    public function testSnapshotAmountPaidIsUsedAsBaselineForBalanceWhenTotalGrows(): void
    {
        // total viejo 400 + propina 50 -> snapshot 450. Add-on nuevo de 100 sube
        // el total a 500 -> owed = 500 + 50 = 550 -> balance = 550 - 450 = 100.
        $this->seed([
            'is_paid'         => true,
            'amount_paid'     => null,
            'total_amount'    => 400.0,
            'gratuity_amount' => 50.0,
            '_servicePrice'   => ['amount' => 400.0],
        ]);
        $this->addonRepo->addonRows = [
            ['price_at_time' => 100.0, 'quantity' => 1, 'estimated_duration_minutes' => 0, 'type_name' => 'standard', 'name' => 'Face Paint'],
        ];

        $this->service->recalculateTotals('res-1');
        $update = $this->lastUpdate();

        $this->assertSame(450.0, $update['amount_paid']);
        $this->assertSame(500.0, $update['total_amount']);
        $this->assertSame(100.0, $update['balance_due']);
    }

    // -------------------------------------------------------------------------
    // Bug #2 del certifier — la propina ya no contamina el balance
    // -------------------------------------------------------------------------

    public function testPaidReservationWithGratuityAndNoChangesHasZeroBalance(): void
    {
        // amount_paid = total + propina = 600. Nada cambia -> nuevo total 500 ->
        // owed = 600 -> balance 0 (NO aparece la propina como saldo fantasma).
        $this->seed([
            'is_paid'         => true,
            'amount_paid'     => 600.0,
            'total_amount'    => 500.0,
            'gratuity_amount' => 100.0,
            '_servicePrice'   => ['amount' => 500.0],
        ]);

        $this->service->recalculateTotals('res-1');
        $update = $this->lastUpdate();

        $this->assertSame(500.0, $update['total_amount']);
        $this->assertSame(0.0, $update['balance_due'], 'bug #2: la propina NO debe generar balance');
        $this->assertArrayNotHasKey('amount_paid', $update, 'amount_paid no nulo: no se re-snapshotea');
    }

    public function testGratuityDoesNotAbsorbNewAddonCharge(): void
    {
        // amount_paid = 600 (total 500 + propina 100). Add-on nuevo de 50 sube el
        // total a 550 -> owed = 550 + 100 = 650 -> balance = 650 - 600 = 50.
        // Antes del fix: max(0, 550 - 600) = 0 (la propina absorbía el cargo).
        $this->seed([
            'is_paid'         => true,
            'amount_paid'     => 600.0,
            'total_amount'    => 500.0,
            'gratuity_amount' => 100.0,
            '_servicePrice'   => ['amount' => 500.0],
        ]);
        $this->addonRepo->addonRows = [
            ['price_at_time' => 50.0, 'quantity' => 1, 'estimated_duration_minutes' => 0, 'type_name' => 'standard', 'name' => 'Balloons'],
        ];

        $this->service->recalculateTotals('res-1');
        $update = $this->lastUpdate();

        $this->assertSame(550.0, $update['total_amount']);
        $this->assertSame(50.0, $update['balance_due'], 'bug #2: la propina no absorbe el add-on nuevo');
    }

    // -------------------------------------------------------------------------
    // El repositorio real devuelve entidades, no arrays
    // -------------------------------------------------------------------------

    /**
     * getForRecalculation() devuelve objetos App\Entities\ReservationAddon. Un
     * `(array)` crudo sobre una Entity produce claves con el prefijo de propiedad
     * protegida (no los datos) -> los add-ons se tarificaban a 0. El precio debe
     * salir de la entidad igual que de un array.
     */
    public function testAddonsTotalIsComputedWhenRepositoryReturnsEntities(): void
    {
        $this->seed(['_servicePrice' => ['amount' => 500.0]]);

        $this->addonRepo->addonRows = [
            new \App\Entities\ReservationAddon([
                'price_at_time'              => 75.0,
                'quantity'                   => 3,
                'name'                       => 'Additional Time (1 Performer)',
                'type_name'                  => 'Additional Time',
                'estimated_duration_minutes' => 15,
            ]),
        ];

        $this->service->recalculateTotals('res-1');
        $update = $this->lastUpdate();

        $this->assertSame(225.0, $update['addons_total'], 'add-on entity: 75 * 3');
        $this->assertSame(725.0, $update['total_amount'], '500 base + 225 add-ons');
    }

    /**
     * El mismo cálculo debe funcionar con add-ons pasados como stdClass
     * (defensa del normalizeRow contra objetos que no sean Entity).
     */
    public function testAddonsTotalIsComputedWhenRepositoryReturnsStdClass(): void
    {
        $this->seed(['_servicePrice' => ['amount' => 500.0]]);

        $this->addonRepo->addonRows = [
            (object) [
                'price_at_time'              => 40.0,
                'quantity'                   => 2,
                'name'                       => 'Balloons',
                'type_name'                  => 'Decor',
                'estimated_duration_minutes' => 0,
            ],
        ];

        $this->service->recalculateTotals('res-1');
        $update = $this->lastUpdate();

        $this->assertSame(80.0, $update['addons_total']);
        $this->assertSame(580.0, $update['total_amount']);
    }

    // -------------------------------------------------------------------------
    // Balance exactamente 0.01
    // -------------------------------------------------------------------------

    public function testBalanceDueOfExactlyOneCent(): void
    {
        $this->seed([
            'is_paid'        => true,
            'amount_paid'    => 500.0,
            'total_amount'   => 500.0,
            '_servicePrice'  => ['amount' => 500.01],
        ]);

        $this->service->recalculateTotals('res-1');
        $update = $this->lastUpdate();

        $this->assertSame(500.01, $update['total_amount']);
        $this->assertSame(0.01, $update['balance_due']);
    }

    // -------------------------------------------------------------------------
    // C4 — expedite fee congelado
    // -------------------------------------------------------------------------

    public function testExpediteFeeStaysZeroEvenWhenEventDateNowWouldTriggerIt(): void
    {
        // expedite_fee = 0 en la reserva; evento mañana (calculateSurcharge daría
        // $50). El $update persiste expedite 0 y el total NO incluye los $50.
        $this->seed([
            'expedite_fee'  => 0.0,
            'event_date'    => date('Y-m-d', strtotime('+1 day')),
            'total_amount'  => 500.0,
            '_servicePrice' => ['amount' => 500.0],
        ]);

        $this->service->recalculateTotals('res-1');
        $update = $this->lastUpdate();

        $this->assertSame(0.0, $update['expedite_fee'], 'expedite congelado en 0, no se recalcula contra hoy');
        $this->assertSame(0.0, $update['expedition_fee']);
        $this->assertSame(500.0, $update['total_amount'], 'el total NO incluye $50 de expedite');
    }

    public function testFrozenExpediteFeeIsKeptEvenWhenEventDateIsFarAway(): void
    {
        // expedite_fee = 50 en la reserva; evento lejano (calculateSurcharge daría
        // 0). El $update conserva expedite 50 y lo suma al total.
        $this->seed([
            'expedite_fee'  => 50.0,
            'event_date'    => date('Y-m-d', strtotime('+30 days')),
            'total_amount'  => 550.0,
            '_servicePrice' => ['amount' => 500.0],
        ]);

        $this->service->recalculateTotals('res-1');
        $update = $this->lastUpdate();

        $this->assertSame(50.0, $update['expedite_fee'], 'expedite congelado: no baja a 0 por el paso del tiempo');
        $this->assertSame(50.0, $update['expedition_fee']);
        $this->assertSame(550.0, $update['total_amount']);
    }

    // -------------------------------------------------------------------------
    // C3 — recalcular con promo NO incrementa el contador de uso
    // -------------------------------------------------------------------------

    public function testRecalculateWithPromoNeverIncrementsUsageCounter(): void
    {
        $this->seed([
            'promo_code'     => 'SAVE10',
            'total_amount'   => 450.0,
            'discount_amount' => 50.0,
            '_servicePrice'  => ['amount' => 500.0],
        ]);
        $this->promoRepo->promo = [
            'id'                    => 'promo-1',
            'discount_type'         => 'percentage',
            'discount_value'        => 10,
            'applies_to_travel_fee' => 0,
        ];

        $this->service->recalculateTotals('res-1');

        $nonFind = array_values(array_filter(
            $this->promoRepo->calls,
            static fn (array $c) => $c[0] !== 'findByCode'
        ));
        $this->assertSame([], $nonFind, 'recalcular no debe incrementar times_used / markUsed / update del promo');
        $this->assertSame([['findByCode', ['SAVE10']]], $this->promoRepo->calls);

        // El descuento se re-tarifica contra la base fresca: 10% de 500 = 50.
        $update = $this->lastUpdate();
        $this->assertSame(50.0, $update['discount_amount']);
        $this->assertSame(450.0, $update['total_amount']);
    }

    // -------------------------------------------------------------------------
    // Transacción — rollback + HTTPException (nunca excepción cruda)
    // -------------------------------------------------------------------------

    public function testRawExceptionInsideTransactionIsRolledBackAndWrappedAs500(): void
    {
        $this->seed(['_servicePrice' => ['amount' => 500.0]]);
        $this->repo->updateThrow = new \RuntimeException('db write blew up');

        try {
            $this->service->recalculateTotals('res-1');
            $this->fail('expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertSame(500, $e->getCode());
            $this->assertStringContainsString('recalculate', strtolower($e->getMessage()));
        }

        $this->assertContains('transRollback', $this->dbFake->calls);
        $this->assertNotContains('transComplete', $this->dbFake->calls);
    }

    public function testHttpExceptionInsideTransactionIsRolledBackAndRethrownUnchanged(): void
    {
        $this->seed(['_servicePrice' => ['amount' => 500.0]]);
        $this->repo->updateThrow = new HTTPException('conflict', 422);

        try {
            $this->service->recalculateTotals('res-1');
            $this->fail('expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertSame(422, $e->getCode(), 'la HTTPException se re-lanza tal cual, sin envolver');
        }

        $this->assertContains('transRollback', $this->dbFake->calls);
    }

    public function testFailedTransStatusRollsBackAndThrows500(): void
    {
        $this->seed(['_servicePrice' => ['amount' => 500.0]]);
        $this->dbFake->status = false;

        try {
            $this->service->recalculateTotals('res-1');
            $this->fail('expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertSame(500, $e->getCode());
        }

        $this->assertContains('transComplete', $this->dbFake->calls);
        $this->assertContains('transRollback', $this->dbFake->calls);
    }

    // -------------------------------------------------------------------------
    // Valor de retorno + transacción feliz
    // -------------------------------------------------------------------------

    public function testReturnsUpdatedReservationObjectWithRecomposedFields(): void
    {
        $this->seed([
            'total_amount'  => 999.0,
            '_servicePrice' => ['amount' => 500.0],
        ]);

        $result = $this->service->recalculateTotals('res-1');

        $this->assertIsObject($result);
        $this->assertSame(500.0, (float) $result->total_amount);
        $this->assertSame(500.0, (float) $result->base_price);
        $this->assertSame('transComplete', $this->dbFake->calls[array_search('transComplete', $this->dbFake->calls, true)]);
        $this->assertContains('transStart', $this->dbFake->calls);
    }

    public function testFallsBackToGetByIdWhenUpdateReturnsFalsy(): void
    {
        $this->seed(['_servicePrice' => ['amount' => 500.0]]);
        $this->repo->updateReturnsFalsy = true;

        $result = $this->service->recalculateTotals('res-1');

        $this->assertIsObject($result);
        // getById: una vez al entrar + una vez en el fallback.
        $this->assertGreaterThanOrEqual(2, count($this->repo->getByIdCalls));
    }

    public function testRecordsRecalculationEventInTimeline(): void
    {
        $this->seed([
            'total_amount'  => 400.0,
            '_servicePrice' => ['amount' => 500.0],
        ]);

        $this->service->recalculateTotals('res-1');

        $this->assertCount(1, $this->history->inserts);
        $row = $this->history->inserts[0];
        $this->assertSame('Reservation Updated', $row['template_name']);
        $this->assertSame('update', $row['event_type']);
        $this->assertSame('res-1', $row['reservation_id']);
    }
}
