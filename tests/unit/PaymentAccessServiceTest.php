<?php

namespace Tests\Unit;

use App\Models\PaymentAccessTokenModel;
use App\Services\CustomPaymentLinkService;
use App\Services\PaymentAccessService;
use App\Services\ReservationService;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Payment gateway — turns a durable `/pay/{token}` link into a fresh, short
 * Stripe Checkout Session at redemption time. See PaymentAccessService.
 *
 * Estrategia sin base de datos (regla del proyecto): CIUnitTestCase + Reflection.
 *  - $tokenModel -> subclase de PaymentAccessTokenModel (estado en memoria).
 *  - $reservationService / $customLinkService -> subclases anonimas que solo
 *    implementan regeneratePaymentSession()/regenerateSession(), sin tocar DB.
 *
 * @internal
 */
final class PaymentAccessServiceTest extends CIUnitTestCase
{
    private PaymentAccessService $service;
    private object $tokenModel;
    private object $reservationService;
    private object $customLinkService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenModel = new class extends PaymentAccessTokenModel {
            /** @var array<string,object> */
            public array $tokens = [];
            /** @var array<int,array{0:string,1:string,2:int}> */
            public array $issueCalls = [];

            public function __construct()
            {
                // Skip parent (would build a real Model / DB handle).
            }

            public function issueFor(string $targetType, string $targetId, int $days = 6): object
            {
                $this->issueCalls[] = [$targetType, $targetId, $days];
                $token = 'tok-' . (count($this->issueCalls));
                $row = (object) [
                    'token' => $token,
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'expires_at' => 'valid',
                ];
                $this->tokens[$token] = $row;

                return $row;
            }

            public function findValid(string $token): ?object
            {
                return $this->tokens[$token] ?? null;
            }

            public function findActiveFor(string $targetType, string $targetId): ?object
            {
                foreach ($this->tokens as $row) {
                    if ($row->target_type === $targetType && $row->target_id === $targetId) {
                        return $row;
                    }
                }

                return null;
            }

            public function decodeToken(string $token): ?array
            {
                $row = $this->tokens[$token] ?? null;

                return $row ? ['target_type' => $row->target_type, 'target_id' => $row->target_id] : null;
            }
        };

        $this->reservationService = new class extends ReservationService {
            /** @var array<int,array{0:string,1:?int}> */
            public array $calls = [];
            public ?HTTPException $throw = null;

            public function __construct()
            {
                // Skip parent (would wire real repositories).
            }

            public function regeneratePaymentSession(string $reservationId, ?int $expiresInSeconds = null): array
            {
                $this->calls[] = [$reservationId, $expiresInSeconds];
                if ($this->throw !== null) {
                    throw $this->throw;
                }

                return ['session_id' => 'cs_res', 'payment_url' => 'https://checkout.stripe.com/reservation'];
            }
        };

        $this->customLinkService = new class extends CustomPaymentLinkService {
            /** @var array<int,array{0:string,1:int}> */
            public array $calls = [];
            public ?HTTPException $throw = null;

            public function __construct()
            {
                // Skip parent (would wire real repositories).
            }

            public function regenerateSession(string $id, int $expiresInSeconds = 7200): array
            {
                $this->calls[] = [$id, $expiresInSeconds];
                if ($this->throw !== null) {
                    throw $this->throw;
                }

                return ['session_id' => 'cs_link', 'payment_url' => 'https://checkout.stripe.com/link'];
            }
        };

        $this->service = new PaymentAccessService();
        $this->setProp('tokenModel', $this->tokenModel);
        $this->setProp('reservationService', $this->reservationService);
        $this->setProp('customLinkService', $this->customLinkService);
    }

    private function setProp(string $name, $value): void
    {
        $ref = new \ReflectionProperty(PaymentAccessService::class, $name);
        $ref->setAccessible(true);
        $ref->setValue($this->service, $value);
    }

    private function catchHttp(callable $fn): array
    {
        try {
            $fn();

            return [false, null];
        } catch (HTTPException $e) {
            return [true, $e];
        }
    }

    // -------------------------------------------------------------------------
    // buildLink() / ensureLink()
    // -------------------------------------------------------------------------

    public function testBuildLinkAlwaysIssuesAFreshToken(): void
    {
        $url = $this->service->buildLink('reservation', 'res-1');

        $this->assertSame([['reservation', 'res-1', 6]], $this->tokenModel->issueCalls);
        $this->assertStringContainsString('/pay/tok-1', $url);
    }

    public function testBuildLinkRenewsEvenWhenAnActiveTokenAlreadyExists(): void
    {
        $this->service->buildLink('reservation', 'res-1');
        $this->service->buildLink('reservation', 'res-1');

        $this->assertCount(2, $this->tokenModel->issueCalls);
    }

    public function testEnsureLinkReusesAnActiveTokenInsteadOfRenewing(): void
    {
        $first = $this->service->buildLink('custom_payment_link', 'link-1');
        $second = $this->service->ensureLink('custom_payment_link', 'link-1');

        $this->assertSame($first, $second);
        $this->assertCount(1, $this->tokenModel->issueCalls);
    }

    public function testEnsureLinkIssuesANewTokenWhenNoneIsActive(): void
    {
        $url = $this->service->ensureLink('custom_payment_link', 'link-2');

        $this->assertCount(1, $this->tokenModel->issueCalls);
        $this->assertStringContainsString('/pay/tok-1', $url);
    }

    // -------------------------------------------------------------------------
    // redeem() — routing by target_type
    // -------------------------------------------------------------------------

    public function testRedeemUnknownTokenThrows410(): void
    {
        [$threw, $e] = $this->catchHttp(fn () => $this->service->redeem('nope'));

        $this->assertTrue($threw);
        $this->assertSame(Response::HTTP_GONE, $e->getCode());
    }

    public function testRedeemReservationTokenDelegatesToReservationServiceWithShortLifetime(): void
    {
        $issued = $this->tokenModel->issueFor('reservation', 'res-42');

        $url = $this->service->redeem($issued->token);

        $this->assertSame('https://checkout.stripe.com/reservation', $url);
        $this->assertSame([['res-42', 7200]], $this->reservationService->calls);
        $this->assertSame([], $this->customLinkService->calls);
    }

    public function testRedeemCustomPaymentLinkTokenDelegatesToCustomLinkService(): void
    {
        $issued = $this->tokenModel->issueFor('custom_payment_link', 'link-42');

        $url = $this->service->redeem($issued->token);

        $this->assertSame('https://checkout.stripe.com/link', $url);
        $this->assertSame([['link-42', 7200]], $this->customLinkService->calls);
        $this->assertSame([], $this->reservationService->calls);
    }

    public function testRedeemPropagatesAlreadyPaidExceptionFromReservationService(): void
    {
        $issued = $this->tokenModel->issueFor('reservation', 'res-paid');
        $this->reservationService->throw = new HTTPException('Reservation is already paid', Response::HTTP_BAD_REQUEST);

        [$threw, $e] = $this->catchHttp(fn () => $this->service->redeem($issued->token));

        $this->assertTrue($threw);
        $this->assertSame(400, $e->getCode());
    }

    public function testRedeemPropagatesAlreadyPaidExceptionFromCustomLinkService(): void
    {
        $issued = $this->tokenModel->issueFor('custom_payment_link', 'link-paid');
        $this->customLinkService->throw = new HTTPException('This payment link has already been paid', Response::HTTP_CONFLICT);

        [$threw, $e] = $this->catchHttp(fn () => $this->service->redeem($issued->token));

        $this->assertTrue($threw);
        $this->assertSame(409, $e->getCode());
    }
}
