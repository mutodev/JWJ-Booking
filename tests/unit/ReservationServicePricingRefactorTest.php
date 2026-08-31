<?php

namespace Tests\Unit;

use App\Services\ReservationService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * B6 — No-regresion del nucleo de precios extraido: ReservationService::computeReservationPricing().
 *
 * computeReservationPricing() es PRIVADO y PURO (no toca DB ni request). Se
 * invoca por Reflection. Cada caso reconstruye a mano el importe que create() /
 * createFromForm() producian inline antes del refactor y lo compara al centavo
 * (criterio de aceptacion 1: "el refactor no cambio ni un centavo").
 *
 * Sin base de datos: `new ReservationService()` solo instancia repos (lazy, sin
 * conectar); computeReservationPricing() usa unicamente helpers puros
 * (calculateAddonsTotal, resolveTravelFee, calculateSurcharge,
 * calculateTotalDuration, determinePriceType).
 *
 * @internal
 */
final class ReservationServicePricingRefactorTest extends CIUnitTestCase
{
    private ReservationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReservationService();
    }

    /**
     * @param array<string,mixed> $ctx
     * @return array<string,mixed>
     */
    private function compute(array $ctx): array
    {
        $m = new \ReflectionMethod(ReservationService::class, 'computeReservationPricing');
        $m->setAccessible(true);

        return $m->invoke($this->service, $ctx);
    }

    /** Contexto minimo valido; el test sobreescribe lo que necesita. */
    private function ctx(array $override = []): array
    {
        return array_merge([
            'base_price'          => 200.0,
            'addons'              => [],
            'extra_children_fee'  => 0.0,
            'zipcode'             => [],
            'performers_count'    => 1,
            'service_travel_fee'  => 0.0,
            'base_duration_hours' => 1.0,
            'event_date'          => (new \DateTime('+30 days'))->format('Y-m-d'),
            'discount_amount'     => 0.0,
        ], $override);
    }

    /** Invariantes que deben cumplirse SIEMPRE, sea cual sea el contexto. */
    private function assertInvariants(array $p): void
    {
        $this->assertEqualsWithDelta(
            round($p['travel_fee'] + $p['expedite_fee'], 2),
            $p['expedition_fee'],
            0.001,
            'expedition_fee = travel_fee + expedite_fee'
        );
        $expectedTotal = round(
            $p['base_price'] + $p['addons_total'] + $p['extra_children_fee']
            + $p['travel_fee'] + $p['expedite_fee'] - $p['discount_amount'],
            2
        );
        $this->assertEqualsWithDelta($expectedTotal, $p['total_amount'], 0.001, 'total = base + addons + extra + travel + expedite - discount');
    }

    // -------------------------------------------------------------------------
    // Add-ons
    // -------------------------------------------------------------------------

    public function testBareReservationNoAddons(): void
    {
        $p = $this->compute($this->ctx());

        $this->assertSame(200.0, $p['base_price']);
        $this->assertSame(0.0, $p['addons_total']);
        $this->assertSame(0.0, $p['travel_fee']);
        $this->assertSame(0.0, $p['expedite_fee']);
        $this->assertSame(0.0, $p['expedition_fee']);
        $this->assertSame(200.0, $p['total_amount']);
        $this->assertSame(1.0, $p['duration_hours']);
        $this->assertSame(0, $p['addons_duration_minutes']);
        $this->assertSame('standard', $p['price_type']);
        $this->assertInvariants($p);
    }

    public function testAddonsQuantityGreaterThanOne(): void
    {
        $p = $this->compute($this->ctx([
            'addons' => [
                ['base_price' => 50.0, 'quantity' => 3, 'estimated_duration_minutes' => 15],
            ],
        ]));

        $this->assertSame(150.0, $p['addons_total']);       // 50 * 3
        $this->assertSame(45, $p['addons_duration_minutes']); // 15 * 3
        $this->assertSame(1.75, $p['duration_hours']);        // 1 + 45/60
        $this->assertSame(350.0, $p['total_amount']);         // 200 + 150
        $this->assertInvariants($p);
    }

    public function testAddonUsesSelectedPriceOverBasePriceAndDetectsJukebox(): void
    {
        $p = $this->compute($this->ctx([
            'addons' => [
                [
                    'base_price'    => 100.0,
                    'selectedPrice' => 120.0,
                    'quantity'      => 1,
                    'suboption'     => 'Deluxe package',
                    'price_type'    => 'jukebox',
                ],
            ],
        ]));

        $this->assertSame(120.0, $p['addons_total']); // selectedPrice gana
        $this->assertSame('jukebox', $p['price_type']);
        $this->assertSame(320.0, $p['total_amount']);
        $this->assertInvariants($p);
    }

    public function testAddonsTotalTakenFromContextWhenNonNull(): void
    {
        $ctx = $this->ctx([
            'addons'       => [['base_price' => 100.0, 'quantity' => 1]],
            'addons_total' => 999.0, // ruta `subtotal` de createFromForm
        ]);

        $p = $this->compute($ctx);

        $this->assertSame(999.0, $p['addons_total'], 'usa ctx[addons_total] cuando viene no-null');
        $this->assertSame(1199.0, $p['total_amount']);
        $this->assertInvariants($p);
    }

    public function testAddonsTotalDerivedFromAddonsWhenContextKeyMissing(): void
    {
        $ctx = $this->ctx(['addons' => [['base_price' => 100.0, 'quantity' => 2]]]);
        unset($ctx['addons_total']); // ruta create()

        $p = $this->compute($ctx);

        $this->assertSame(200.0, $p['addons_total']); // derivado 100 * 2
        $this->assertInvariants($p);
    }

    public function testAddonsTotalDerivedWhenContextKeyIsExplicitlyNull(): void
    {
        $p = $this->compute($this->ctx([
            'addons'       => [['base_price' => 30.0, 'quantity' => 2]],
            'addons_total' => null, // ruta legacy de createFromForm
        ]));

        $this->assertSame(60.0, $p['addons_total']);
        $this->assertInvariants($p);
    }

    // -------------------------------------------------------------------------
    // Travel fee — resolveTravelFee heredado
    // -------------------------------------------------------------------------

    public function testStandardZoneFallsBackToServiceTravelFee(): void
    {
        $p = $this->compute($this->ctx([
            'zipcode'            => ['zone_type' => 'standard'],
            'service_travel_fee' => 25.0,
        ]));

        $this->assertSame(25.0, $p['travel_fee']);
        $this->assertSame(25.0, $p['expedition_fee']);
        $this->assertSame(225.0, $p['total_amount']);
        $this->assertInvariants($p);
    }

    public function testTravelFeeZoneOverrideForOnePerformer(): void
    {
        $p = $this->compute($this->ctx([
            'zipcode' => [
                'zone_type'               => 'travel_fee',
                'travel_fee_1_performer'  => 40.0,
                'travel_fee_2_performers' => 60.0,
            ],
            'performers_count'   => 1,
            'service_travel_fee' => 999.0, // debe ignorarse en zona travel_fee
        ]));

        $this->assertSame(40.0, $p['travel_fee']);
        $this->assertSame(240.0, $p['total_amount']);
        $this->assertInvariants($p);
    }

    public function testTravelFeeZoneOverrideForTwoOrMorePerformers(): void
    {
        $p = $this->compute($this->ctx([
            'zipcode' => [
                'zone_type'               => 'travel_fee',
                'travel_fee_1_performer'  => 40.0,
                'travel_fee_2_performers' => 60.0,
            ],
            'performers_count' => 3,
        ]));

        $this->assertSame(60.0, $p['travel_fee']);
        $this->assertInvariants($p);
    }

    public function testTravelFeeZoneWithTwoPerformersButNoTwoPerformerRateFallsToOnePerformerRate(): void
    {
        $p = $this->compute($this->ctx([
            'zipcode' => [
                'zone_type'              => 'travel_fee',
                'travel_fee_1_performer' => 40.0,
            ],
            'performers_count' => 2,
        ]));

        $this->assertSame(40.0, $p['travel_fee']);
        $this->assertInvariants($p);
    }

    // -------------------------------------------------------------------------
    // Zona minimum_2h — la duracion base sube a 2h
    // -------------------------------------------------------------------------

    public function testMinimum2hZoneRaisesBaseDurationToTwoHours(): void
    {
        $p = $this->compute($this->ctx([
            'zipcode'             => ['zone_type' => 'minimum_2h'],
            'base_duration_hours' => 1.0,
        ]));

        $this->assertSame(2.0, $p['duration_hours']);
        $this->assertInvariants($p);
    }

    public function testMinimum2hZoneDoesNotLowerAlreadyLongerDuration(): void
    {
        $p = $this->compute($this->ctx([
            'zipcode'             => ['zone_type' => 'minimum_2h'],
            'base_duration_hours' => 3.0,
        ]));

        $this->assertSame(3.0, $p['duration_hours']);
    }

    public function testMinimum2hZoneAddsAddonMinutesOnTopOfTheTwoHourFloor(): void
    {
        $p = $this->compute($this->ctx([
            'zipcode'             => ['zone_type' => 'minimum_2h'],
            'base_duration_hours' => 1.0,
            'addons'              => [['base_price' => 0.0, 'quantity' => 1, 'estimated_duration_minutes' => 30]],
        ]));

        $this->assertSame(2.5, $p['duration_hours']); // max(1,2) + 30/60
    }

    // -------------------------------------------------------------------------
    // Expedite fee — plano por proximidad de la FECHA DEL EVENTO (no del monto)
    // -------------------------------------------------------------------------

    public function testExpediteFeeIsFiftyForEventOneDayAway(): void
    {
        $p = $this->compute($this->ctx(['event_date' => (new \DateTime('+1 day'))->format('Y-m-d')]));

        $this->assertSame(50.0, $p['expedite_fee']);
        $this->assertSame(250.0, $p['total_amount']);
        $this->assertInvariants($p);
    }

    public function testExpediteFeeIsFiftyForEventExactlyThreeDaysAway(): void
    {
        $p = $this->compute($this->ctx(['event_date' => (new \DateTime('+3 days'))->format('Y-m-d')]));

        $this->assertSame(50.0, $p['expedite_fee']);
        $this->assertInvariants($p);
    }

    public function testExpediteFeeIsZeroForEventFiveDaysAway(): void
    {
        $p = $this->compute($this->ctx(['event_date' => (new \DateTime('+5 days'))->format('Y-m-d')]));

        $this->assertSame(0.0, $p['expedite_fee']);
        $this->assertInvariants($p);
    }

    public function testExpediteFeeIsZeroForEventThirtyDaysAway(): void
    {
        $p = $this->compute($this->ctx(['event_date' => (new \DateTime('+30 days'))->format('Y-m-d')]));

        $this->assertSame(0.0, $p['expedite_fee']);
    }

    public function testExpediteFeeIsZeroWhenEventDateMissing(): void
    {
        $p = $this->compute($this->ctx(['event_date' => null]));

        $this->assertSame(0.0, $p['expedite_fee']);
    }

    public function testExpediteFeeDoesNotDependOnAmount(): void
    {
        $cheap = $this->compute($this->ctx([
            'base_price'  => 10.0,
            'event_date'  => (new \DateTime('+2 days'))->format('Y-m-d'),
        ]));
        $pricey = $this->compute($this->ctx([
            'base_price'  => 100000.0,
            'event_date'  => (new \DateTime('+2 days'))->format('Y-m-d'),
        ]));

        $this->assertSame(50.0, $cheap['expedite_fee']);
        $this->assertSame(50.0, $pricey['expedite_fee']);
    }

    // -------------------------------------------------------------------------
    // Promo / discount_amount y extra_children_fee
    // -------------------------------------------------------------------------

    public function testDiscountAmountIsSubtractedLast(): void
    {
        $p = $this->compute($this->ctx([
            'addons'          => [['base_price' => 50.0, 'quantity' => 1]],
            'discount_amount' => 30.0,
        ]));

        $this->assertSame(30.0, $p['discount_amount']);
        $this->assertSame(220.0, $p['total_amount']); // 200 + 50 - 30
        $this->assertInvariants($p);
    }

    public function testExtraChildrenFeeIsIncludedInTotal(): void
    {
        $p = $this->compute($this->ctx(['extra_children_fee' => 45.0]));

        $this->assertSame(45.0, $p['extra_children_fee']);
        $this->assertSame(245.0, $p['total_amount']);
        $this->assertInvariants($p);
    }

    // -------------------------------------------------------------------------
    // Caso combinado completo (replica un createFromForm real)
    // -------------------------------------------------------------------------

    public function testFullyCombinedScenarioMatchesHandComputedOracle(): void
    {
        $p = $this->compute([
            'base_price'          => 300.0,
            'addons'              => [['base_price' => 50.0, 'quantity' => 2, 'estimated_duration_minutes' => 30]],
            'addons_total'        => null,
            'extra_children_fee'  => 20.0,
            'zipcode'             => [
                'zone_type'               => 'travel_fee',
                'travel_fee_1_performer'  => 45.0,
                'travel_fee_2_performers' => 60.0,
            ],
            'performers_count'    => 2,
            'service_travel_fee'  => 15.0,
            'base_duration_hours' => 1.5,
            'event_date'          => (new \DateTime('+2 days'))->format('Y-m-d'),
            'discount_amount'     => 25.0,
        ]);

        // preSurcharge = 300 + 100 + 20 = 420
        // travel = 60 (2 performers), expedite = 50 (2 days)
        // total = 420 + 60 + 50 - 25 = 505
        $this->assertSame(100.0, $p['addons_total']);
        $this->assertSame(60.0, $p['travel_fee']);
        $this->assertSame(50.0, $p['expedite_fee']);
        $this->assertSame(110.0, $p['expedition_fee']);
        $this->assertSame(505.0, $p['total_amount']);
        $this->assertSame(2.5, $p['duration_hours']); // 1.5 + 60/60
        $this->assertSame(60, $p['addons_duration_minutes']);
        $this->assertInvariants($p);
    }

    // -------------------------------------------------------------------------
    // Precision decimal — round(_, 2) consistente
    // -------------------------------------------------------------------------

    public function testAllMoneyFieldsAreRoundedToTwoDecimals(): void
    {
        $p = $this->compute($this->ctx([
            'base_price' => 10.005,
            'addons'     => [['base_price' => 0.1, 'quantity' => 3]], // 0.1*3 = 0.30000000000000004
        ]));

        $this->assertSame(10.01, $p['base_price']);
        $this->assertSame(0.3, $p['addons_total']);
        $this->assertSame(round($p['total_amount'], 2), $p['total_amount']);
        $this->assertInvariants($p);
    }

    public function testDurationHoursIsNotRounded(): void
    {
        $p = $this->compute($this->ctx([
            'base_duration_hours' => 1.0,
            'addons'              => [['base_price' => 0.0, 'quantity' => 1, 'estimated_duration_minutes' => 10]],
        ]));

        // 1 + 10/60 = 1.1666... debe conservar los decimales
        $this->assertGreaterThan(1.16, $p['duration_hours']);
        $this->assertLessThan(1.17, $p['duration_hours']);
        $this->assertNotSame(round($p['duration_hours'], 2), $p['duration_hours']);
    }
}
