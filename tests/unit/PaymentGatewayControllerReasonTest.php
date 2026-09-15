<?php

namespace Tests\Unit;

use App\Controllers\PaymentGatewayController;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * PaymentGatewayController::reasonFor() — sniffs the exception message because
 * the two underlying regenerate calls (ReservationService, CustomPaymentLinkService)
 * don't share status codes 1:1 for "paid" vs "cancelled" (e.g. a paid reservation
 * and a cancelled one both throw 400). This must never resolve to a status
 * code that the frontend's axios interceptor treats as an auth failure.
 *
 * @internal
 */
final class PaymentGatewayControllerReasonTest extends CIUnitTestCase
{
    private function reasonFor(int $statusCode, string $message): string
    {
        $controller = (new \ReflectionClass(PaymentGatewayController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(PaymentGatewayController::class, 'reasonFor');
        $method->setAccessible(true);

        return $method->invoke($controller, $statusCode, $message);
    }

    public function testGoneStatusWithNoKeywordIsExpired(): void
    {
        $this->assertSame('expired', $this->reasonFor(410, 'This payment link has expired or is no longer valid.'));
    }

    public function testNotFoundStatusIsExpired(): void
    {
        $this->assertSame('expired', $this->reasonFor(404, 'Payment link not found'));
    }

    public function testPaidKeywordWinsRegardlessOfStatusCode(): void
    {
        // Reservation flow throws 400 for both paid and cancelled reservations.
        $this->assertSame('paid', $this->reasonFor(400, 'Reservation is already paid'));
        $this->assertSame('paid', $this->reasonFor(409, 'This payment link has already been paid'));
    }

    public function testCancelKeywordWinsRegardlessOfStatusCode(): void
    {
        $this->assertSame('cancelled', $this->reasonFor(400, 'Reservation is cancelled'));
        $this->assertSame('cancelled', $this->reasonFor(400, 'This payment link has been cancelled'));
    }

    public function testUnrecognizedServerErrorFallsBackToError(): void
    {
        $this->assertSame('error', $this->reasonFor(500, 'Something exploded'));
    }
}
