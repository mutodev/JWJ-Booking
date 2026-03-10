<?php

namespace Tests\Unit;

use App\Services\ReservationService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Tests for ReservationService::create() — admin reservation creation flow.
 * Uses reflection to inject a mock repository so no DB is required.
 *
 * @internal
 */
final class ReservationServiceCreateTest extends CIUnitTestCase
{
    private ReservationService $service;
    private object $repoMock;
    private array $baseData;

    protected function setUp(): void
    {
        parent::setUp();

        // Build a minimal stub for the repository
        $this->repoMock = new class {
            public array $lastCreated = [];
            public function create(array $data) {
                $this->lastCreated = $data;
                return (object) array_merge(['id' => 'test-uuid'], $data);
            }
        };

        $this->service = new ReservationService();

        // Inject mock repository via reflection
        $ref = new \ReflectionProperty(ReservationService::class, 'repository');
        $ref->setAccessible(true);
        $ref->setValue($this->service, $this->repoMock);

        // Base admin form data — all required fields present
        $futureDate = (new \DateTime('+30 days'))->format('Y-m-d');
        $this->baseData = [
            'customer' => ['id' => 'cust-1'],
            'price'    => [
                'id'                  => 'price-1',
                'amount'              => 200.00,
                'extra_child_fee'     => 10.00,
                'performers_count'    => 2,
                'min_duration_hours'  => 2.0,
            ],
            'areas' => [
                'zipcode' => ['id' => 'zip-1'],
            ],
            'addons' => [],
            'form'   => [
                'date'                     => $futureDate,
                'startTime'                => '14:00',
                'entertainmentStartTime'   => '14:30',
                'extraChildren'            => 0,
                'eventAddress'             => '123 Main St',
                'arrivalParkingInstructions' => 'Front door',
                'childrenAgeRange'         => '5-10',
                'birthdayChildName'        => 'Ana',
                'childAge'                 => '7',
                'happyBirthdayRequest'     => 'yes',
                'songRequests'             => 'Let it go',
                'customerNotes'            => null,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Extra children — admin form path
    // -------------------------------------------------------------------------

    public function testExtraChildrenFromAdminFormAreChargedDirectly(): void
    {
        $this->baseData['form']['extraChildren'] = 3;

        $this->service->create($this->baseData);

        $saved = $this->repoMock->lastCreated;
        // 3 extra children × $10 = $30
        $this->assertEquals(30.0, $saved['extra_children_fee']);
        $this->assertEquals(230.0, $saved['total_amount']); // 200 + 30
    }

    public function testZeroExtraChildrenProducesNoFee(): void
    {
        $this->baseData['form']['extraChildren'] = 0;

        $this->service->create($this->baseData);

        $saved = $this->repoMock->lastCreated;
        $this->assertEquals(0.0, $saved['extra_children_fee']);
        $this->assertEquals(200.0, $saved['total_amount']);
    }

    public function testNegativeExtraChildrenClampedToZero(): void
    {
        $this->baseData['form']['extraChildren'] = -5;

        $this->service->create($this->baseData);

        $saved = $this->repoMock->lastCreated;
        $this->assertEquals(0.0, $saved['extra_children_fee']);
    }

    // -------------------------------------------------------------------------
    // Extra children — customer form fallback path (selectedKids)
    // -------------------------------------------------------------------------

    public function testCustomerFormUsesSelectedKidsMinus40(): void
    {
        // Remove extraChildren so the legacy path is taken
        unset($this->baseData['form']['extraChildren']);
        $this->baseData['form']['selectedKids'] = 45; // 5 extras above the 40 limit

        $this->service->create($this->baseData);

        $saved = $this->repoMock->lastCreated;
        // 5 extra × $10 = $50
        $this->assertEquals(50.0, $saved['extra_children_fee']);
    }

    public function testCustomerFormSelectedKidsBelowLimitNoFee(): void
    {
        unset($this->baseData['form']['extraChildren']);
        $this->baseData['form']['selectedKids'] = 20; // under 40

        $this->service->create($this->baseData);

        $saved = $this->repoMock->lastCreated;
        $this->assertEquals(0.0, $saved['extra_children_fee']);
    }

    // -------------------------------------------------------------------------
    // Promo code
    // -------------------------------------------------------------------------

    public function testPromoCodeDiscountReducesTotal(): void
    {
        $this->baseData['promoCode'] = [
            'code'            => 'SAVE20',
            'discount_amount' => 40.00,
        ];

        $this->service->create($this->baseData);

        $saved = $this->repoMock->lastCreated;
        $this->assertEquals(40.0, $saved['discount_amount']);
        $this->assertEquals('SAVE20', $saved['promo_code']);
        $this->assertEquals(160.0, $saved['total_amount']); // 200 - 40
    }

    public function testNoPromoCodeMeansZeroDiscount(): void
    {
        // No promoCode key
        $this->service->create($this->baseData);

        $saved = $this->repoMock->lastCreated;
        $this->assertEquals(0.0, $saved['discount_amount']);
        $this->assertNull($saved['promo_code']);
    }

    public function testPromoCodeAndExtraChildrenCombined(): void
    {
        $this->baseData['form']['extraChildren'] = 2;     // +$20
        $this->baseData['promoCode'] = [
            'code'            => 'COMBO',
            'discount_amount' => 15.00,
        ];

        $this->service->create($this->baseData);

        $saved = $this->repoMock->lastCreated;
        // base 200 + extra 20 - discount 15 = 205
        $this->assertEquals(205.0, $saved['total_amount']);
    }

    // -------------------------------------------------------------------------
    // Field name mappings — form keys vs DB columns
    // -------------------------------------------------------------------------

    public function testChildAgeIsMappedCorrectly(): void
    {
        $this->baseData['form']['childAge'] = '8';

        $this->service->create($this->baseData);

        $saved = $this->repoMock->lastCreated;
        $this->assertEquals('8', $saved['birthday_child_age']);
    }

    public function testHappyBirthdayYesSavesTrue(): void
    {
        $this->baseData['form']['happyBirthdayRequest'] = 'yes';

        $this->service->create($this->baseData);

        $saved = $this->repoMock->lastCreated;
        $this->assertTrue($saved['sing_happy_birthday']);
    }

    public function testHappyBirthdayNoSavesFalse(): void
    {
        $this->baseData['form']['happyBirthdayRequest'] = 'no';

        $this->service->create($this->baseData);

        $saved = $this->repoMock->lastCreated;
        $this->assertFalse($saved['sing_happy_birthday']);
    }

    public function testEntertainmentStartTimeSavedSeparately(): void
    {
        $this->baseData['form']['startTime']               = '14:00';
        $this->baseData['form']['entertainmentStartTime']  = '14:30';

        $this->service->create($this->baseData);

        $saved = $this->repoMock->lastCreated;
        $this->assertEquals('14:00', $saved['event_time']);
        $this->assertEquals('14:30', $saved['entertainment_start_time']);
    }

    public function testEntertainmentStartTimeFallsBackToStartTime(): void
    {
        // If entertainmentStartTime is not provided, use startTime as fallback
        unset($this->baseData['form']['entertainmentStartTime']);
        $this->baseData['form']['startTime'] = '15:00';

        $this->service->create($this->baseData);

        $saved = $this->repoMock->lastCreated;
        $this->assertEquals('15:00', $saved['entertainment_start_time']);
    }

    // -------------------------------------------------------------------------
    // Addons total
    // -------------------------------------------------------------------------

    public function testAddonsTotalIsIncludedInGrandTotal(): void
    {
        $this->baseData['addons'] = [
            ['id' => 'a1', 'base_price' => 50.0, 'estimated_duration_minutes' => 30],
            ['id' => 'a2', 'base_price' => 25.0, 'estimated_duration_minutes' => 15],
        ];

        $this->service->create($this->baseData);

        $saved = $this->repoMock->lastCreated;
        // 200 + 50 + 25 = 275
        $this->assertEquals(75.0, $saved['addons_total']);
        $this->assertEquals(275.0, $saved['total_amount']);
    }

    // -------------------------------------------------------------------------
    // Surcharge (via calculateSurcharge private method)
    // -------------------------------------------------------------------------

    public function testCalculateSurchargeViaReflection(): void
    {
        $ref = new \ReflectionMethod(ReservationService::class, 'calculateSurcharge');
        $ref->setAccessible(true);

        // > 7 days → no surcharge
        $farDate = (new \DateTime('+30 days'))->format('Y-m-d');
        $this->assertEquals(0.0, $ref->invoke($this->service, 100.0, $farDate));

        // 2-7 days → 10%
        $soonDate = (new \DateTime('+5 days'))->format('Y-m-d');
        $this->assertEquals(10.0, $ref->invoke($this->service, 100.0, $soonDate));

        // < 2 days → 20%
        $urgentDate = (new \DateTime('+1 day'))->format('Y-m-d');
        $this->assertEquals(20.0, $ref->invoke($this->service, 100.0, $urgentDate));

        // null date → 0
        $this->assertEquals(0.0, $ref->invoke($this->service, 100.0, null));
    }

    public function testSurchargeIsAddedToTotal(): void
    {
        // Use a date 5 days from now → 10% surcharge
        $this->baseData['form']['date'] = (new \DateTime('+5 days'))->format('Y-m-d');

        $this->service->create($this->baseData);

        $saved = $this->repoMock->lastCreated;
        // base 200 × 10% = 20 surcharge → total 220
        $this->assertEquals(220.0, $saved['total_amount']);
    }
}
