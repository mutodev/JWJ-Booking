<?php

namespace Tests\Unit;

use App\Services\CustomPaymentLinkService;
use App\Services\ReservationService;
use App\Services\StripeService;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\Test\CIUnitTestCase;
use Stripe\Checkout\Session;

/**
 * B5 — ramificacion de ReservationService::verifyPayment() por metadata.type
 * (criterios de aceptacion 5 y 6).
 *
 *  - metadata.type = 'custom_payment_link'  -> delega en
 *    CustomPaymentLinkService::handlePaidSession(), NO llama handlePaymentCompleted()
 *    y NO lanza 404 aunque falte reservation_id (criterio 6).
 *  - sin type (flujo de reserva normal) -> comportamiento identico al anterior:
 *    handlePaymentCompleted() con el reservation_id, o 404 si falta (criterio 5).
 *
 * Sin base de datos: se dobla getStripeService() (retrieveSession) y
 * getCustomPaymentLinkService(); handlePaymentCompleted() se intercepta con una
 * subclase anonima de ReservationService.
 *
 * @internal
 */
final class ReservationServiceVerifyPaymentBranchTest extends CIUnitTestCase
{
    private object $service;
    private object $stripe;
    private object $linkService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stripe = new class extends StripeService {
            public Session $session;

            public function __construct()
            {
            }

            public function retrieveSession(string $sessionId): Session
            {
                return $this->session;
            }
        };

        $this->linkService = new class extends CustomPaymentLinkService {
            /** @var array<int,object> */
            public array $paidCalls = [];

            public function __construct()
            {
            }

            public function handlePaidSession(object $session): bool
            {
                $this->paidCalls[] = $session;

                return true;
            }
        };

        $this->service = new class extends ReservationService {
            /** @var array<int,array{0:string,1:string}> */
            public array $hpcCalls = [];

            public function handlePaymentCompleted(string $reservationId, string $paymentIntentId): bool
            {
                $this->hpcCalls[] = [$reservationId, $paymentIntentId];

                return true;
            }
        };

        $this->setProp('stripeService', $this->stripe);
        $this->setProp('customPaymentLinkService', $this->linkService);
    }

    private function setProp(string $name, $value): void
    {
        $ref = new \ReflectionProperty(ReservationService::class, $name);
        $ref->setAccessible(true);
        $ref->setValue($this->service, $value);
    }

    private function setSession(array $values): void
    {
        $this->stripe->session = Session::constructFrom($values);
    }

    // -------------------------------------------------------------------------
    // Criterio 6 — link custom: reconocido, sin 404, delega en handlePaidSession
    // -------------------------------------------------------------------------

    public function testCustomLinkPaidDelegatesAndReturnsCustomShape(): void
    {
        $this->setSession([
            'payment_status' => 'paid',
            'payment_intent' => 'pi_link_1',
            'metadata'       => ['type' => 'custom_payment_link', 'payment_link_id' => 'link-1'],
        ]);

        $result = $this->service->verifyPayment('cs_1');

        $this->assertSame('custom_payment_link', $result['type']);
        $this->assertTrue($result['is_paid']);
        $this->assertSame('paid', $result['payment_status']);
        $this->assertArrayNotHasKey('reservation_id', $result);

        $this->assertCount(1, $this->linkService->paidCalls);
        $this->assertSame([], $this->service->hpcCalls, 'no debe tocar el flujo de reservas');
    }

    public function testCustomLinkUnpaidDoesNotDelegateAndDoesNotThrow(): void
    {
        $this->setSession([
            'payment_status' => 'unpaid',
            'metadata'       => ['type' => 'custom_payment_link', 'payment_link_id' => 'link-1'],
        ]);

        $result = $this->service->verifyPayment('cs_1');

        $this->assertSame('custom_payment_link', $result['type']);
        $this->assertFalse($result['is_paid']);
        $this->assertSame([], $this->linkService->paidCalls);
        $this->assertSame([], $this->service->hpcCalls);
    }

    public function testCustomLinkNeverThrows404EvenWithoutReservationId(): void
    {
        $this->setSession([
            'payment_status' => 'paid',
            'metadata'       => ['type' => 'custom_payment_link', 'payment_link_id' => 'link-1'],
        ]);

        // No excepcion.
        $result = $this->service->verifyPayment('cs_1');
        $this->assertSame('custom_payment_link', $result['type']);
    }

    // -------------------------------------------------------------------------
    // Criterio 5 — flujo de reserva normal: identico al anterior
    // -------------------------------------------------------------------------

    public function testNormalReservationPaidCallsHandlePaymentCompleted(): void
    {
        $this->setSession([
            'payment_status' => 'paid',
            'payment_intent' => 'pi_res_1',
            'metadata'       => ['reservation_id' => 'res-1'],
        ]);

        $result = $this->service->verifyPayment('cs_2');

        $this->assertSame('res-1', $result['reservation_id']);
        $this->assertTrue($result['is_paid']);
        $this->assertArrayNotHasKey('type', $result);
        $this->assertSame([['res-1', 'pi_res_1']], $this->service->hpcCalls);
        $this->assertSame([], $this->linkService->paidCalls);
    }

    public function testNormalReservationUnpaidDoesNotMarkPaid(): void
    {
        $this->setSession([
            'payment_status' => 'unpaid',
            'metadata'       => ['reservation_id' => 'res-1'],
        ]);

        $result = $this->service->verifyPayment('cs_2');

        $this->assertSame('res-1', $result['reservation_id']);
        $this->assertFalse($result['is_paid']);
        $this->assertSame([], $this->service->hpcCalls);
    }

    public function testMissingReservationIdWithoutCustomTypeStillThrows404(): void
    {
        $this->setSession([
            'payment_status' => 'paid',
            'metadata'       => [],
        ]);

        $this->expectException(HTTPException::class);
        $this->service->verifyPayment('cs_3');
    }

    public function testUnknownMetadataTypeFallsBackToReservationPathAnd404(): void
    {
        $this->setSession([
            'payment_status' => 'paid',
            'metadata'       => ['type' => 'something_else'],
        ]);

        $this->expectException(HTTPException::class);
        $this->service->verifyPayment('cs_4');
    }
}
