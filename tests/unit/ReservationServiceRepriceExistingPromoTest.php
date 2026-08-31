<?php

namespace Tests\Unit;

use App\Services\ReservationService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * B6 — ReservationService::repriceExistingPromo() (PRIVADO).
 *
 * Re-tarifica el promo code YA aplicado a la reserva usando la base recien
 * recalculada, con las MISMAS exclusiones que applyPromoCode() (commit 4169b7a:
 * "Custom Song" nunca descuenta; travel fee solo si applies_to_travel_fee;
 * expedite fee nunca). NO incrementa el contador de uso (criterio 3).
 *
 * Se invoca por Reflection. La unica dependencia con DB es
 * promoCodeRepository->findByCode(), que se dobla con una clase anonima
 * (propiedad protegida, sin type hint).
 *
 * Cubre criterio de aceptacion B6: 3.
 *
 * @internal
 */
final class ReservationServiceRepriceExistingPromoTest extends CIUnitTestCase
{
    private ReservationService $service;
    private object $promoRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->promoRepo = new class {
            public ?array $promo = null;
            /** @var array<int,string> */
            public array $findCalls = [];
            /** @var array<int,mixed> */
            public array $otherCalls = [];

            public function findByCode(string $code): ?array
            {
                $this->findCalls[] = $code;

                return $this->promo;
            }

            // Si el codigo llamara a algo de incremento, lo registrariamos.
            public function incrementUsage($id): void
            {
                $this->otherCalls[] = ['incrementUsage', $id];
            }
        };

        $this->service = new ReservationService();
        $ref = new \ReflectionProperty(ReservationService::class, 'promoCodeRepository');
        $ref->setAccessible(true);
        $ref->setValue($this->service, $this->promoRepo);
    }

    private function reprice(object $reservation, array $addonRows, array $provisional): float
    {
        $m = new \ReflectionMethod(ReservationService::class, 'repriceExistingPromo');
        $m->setAccessible(true);

        return $m->invoke($this->service, $reservation, $addonRows, $provisional);
    }

    private function provisional(array $override = []): array
    {
        return array_merge([
            'base_price'         => 200.0,
            'addons_total'       => 100.0,
            'extra_children_fee' => 0.0,
            'travel_fee'         => 40.0,
        ], $override);
    }

    // -------------------------------------------------------------------------

    public function testReturnsZeroWhenReservationHasNoPromoCode(): void
    {
        $discount = $this->reprice((object) ['promo_code' => null], [], $this->provisional());

        $this->assertSame(0.0, $discount);
        $this->assertSame([], $this->promoRepo->findCalls, 'sin promo no se consulta el repo');
    }

    public function testReturnsZeroWhenPromoCodeNoLongerExists(): void
    {
        $this->promoRepo->promo = null;

        $discount = $this->reprice((object) ['promo_code' => 'GONE'], [], $this->provisional());

        $this->assertSame(0.0, $discount);
        $this->assertSame(['GONE'], $this->promoRepo->findCalls);
    }

    public function testPercentageDiscountAppliesToBasePlusAddonsPlusExtraChildren(): void
    {
        $this->promoRepo->promo = [
            'id'                    => 'p1',
            'discount_type'         => 'percentage',
            'discount_value'        => 10,
            'applies_to_travel_fee' => 0,
        ];

        // base 200 + addons 100 + extra 30 = 330 ; 10% = 33
        $discount = $this->reprice(
            (object) ['promo_code' => 'SAVE10'],
            [],
            $this->provisional(['extra_children_fee' => 30.0])
        );

        $this->assertSame(33.0, $discount);
    }

    public function testFixedDiscountIsCappedAtTheDiscountBase(): void
    {
        $this->promoRepo->promo = [
            'id'                    => 'p2',
            'discount_type'         => 'fixed',
            'discount_value'        => 5000,
            'applies_to_travel_fee' => 0,
        ];

        // discountBase = 200 + 100 + 0 = 300 -> min(5000, 300) = 300
        $discount = $this->reprice((object) ['promo_code' => 'BIG'], [], $this->provisional());

        $this->assertSame(300.0, $discount);
    }

    public function testCustomSongIsExcludedFromTheDiscountableAddonsTotal(): void
    {
        $this->promoRepo->promo = [
            'id'                    => 'p3',
            'discount_type'         => 'percentage',
            'discount_value'        => 100, // 100% para aislar la base exacta
            'applies_to_travel_fee' => 0,
        ];

        $addonRows = [
            ['name' => 'Custom Song', 'price_at_time' => 60.0, 'quantity' => 1],
            ['name' => 'Face Paint', 'price_at_time' => 40.0, 'quantity' => 1],
        ];

        // addons_total provisional 100; Custom Song 60 excluido -> elegible 40
        // discountBase = 200 + 40 + 0 = 240
        $discount = $this->reprice((object) ['promo_code' => 'FULL'], $addonRows, $this->provisional());

        $this->assertSame(240.0, $discount);
    }

    public function testCustomSongExclusionHandlesObjectRowsAndQuantity(): void
    {
        $this->promoRepo->promo = [
            'id'                    => 'p4',
            'discount_type'         => 'percentage',
            'discount_value'        => 100,
            'applies_to_travel_fee' => 0,
        ];

        $addonRows = [
            (object) ['name' => 'Custom Song', 'price_at_time' => 25.0, 'quantity' => 2], // 50 excluido
        ];

        // provisional addons_total 100 -> elegible 50 ; base 200 -> 250
        $discount = $this->reprice((object) ['promo_code' => 'FULL'], $addonRows, $this->provisional());

        $this->assertSame(250.0, $discount);
    }

    public function testTravelFeeJoinsDiscountBaseOnlyWhenPromoAllowsIt(): void
    {
        $this->promoRepo->promo = [
            'id'                    => 'p5',
            'discount_type'         => 'percentage',
            'discount_value'        => 100,
            'applies_to_travel_fee' => 1,
        ];

        // base 200 + addons 100 + extra 0 + travel 40 = 340
        $discount = $this->reprice((object) ['promo_code' => 'TRAVEL'], [], $this->provisional());

        $this->assertSame(340.0, $discount);
    }

    public function testNeverIncrementsPromoUsageCounter(): void
    {
        $this->promoRepo->promo = [
            'id'                    => 'p6',
            'discount_type'         => 'percentage',
            'discount_value'        => 10,
            'applies_to_travel_fee' => 0,
        ];

        $this->reprice((object) ['promo_code' => 'SAVE10'], [], $this->provisional());

        $this->assertSame([], $this->promoRepo->otherCalls, 'el recalculo no incrementa el contador de uso');
    }

    public function testDiscountIsNeverNegativeAndRoundedToTwoDecimals(): void
    {
        $this->promoRepo->promo = [
            'id'                    => 'p7',
            'discount_type'         => 'percentage',
            'discount_value'        => 33.333,
            'applies_to_travel_fee' => 0,
        ];

        $discount = $this->reprice(
            (object) ['promo_code' => 'ODD'],
            [],
            $this->provisional(['base_price' => 100.0, 'addons_total' => 0.0])
        );

        // 100 * 33.333 / 100 = 33.333 -> round 33.33
        $this->assertSame(33.33, $discount);
        $this->assertGreaterThanOrEqual(0.0, $discount);
    }
}
