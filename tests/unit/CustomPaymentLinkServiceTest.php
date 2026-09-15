<?php

namespace Tests\Unit;

use App\Models\ReservationEmailHistoryModel;
use App\Repositories\CustomPaymentLinkRepository;
use App\Repositories\ReservationRepository;
use App\Services\BrevoEmailService;
use App\Services\CustomPaymentLinkService;
use App\Services\EmailTemplateService;
use App\Services\PaymentAccessService;
use App\Services\StripeService;
use Stripe\Checkout\Session;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * B5 — Reservas / links de pago personalizados (monto libre y descripcion).
 *
 * Foco: App\Services\CustomPaymentLinkService — creacion del link (validacion +
 * sesion Stripe + persistencia post-Stripe), envio de email, ciclo de vida y
 * marcado de pago desde el webhook (handlePaidSession).
 *
 * Estrategia sin base de datos (regla del proyecto): CIUnitTestCase + Reflection.
 *  - $repo               -> subclase de CustomPaymentLinkRepository (fake con estado en memoria).
 *  - $reservationRepository -> subclase de ReservationRepository (getById controlado).
 *  - $emailTemplateService -> subclase de EmailTemplateService (render controlado / lanza).
 *  - $emailService       -> subclase de BrevoEmailService (sendEmail controlado / lanza).
 *  - $stripeService      -> stub anonimo (propiedad sin type hint, getStripeService() lazy).
 *  - $historyModel       -> SUBCLASE de ReservationEmailHistoryModel (el type hint de
 *                           historyModel() lo exige).
 *  - $access             -> subclase de PaymentAccessService (URLs deterministas,
 *                           sin tocar payment_access_tokens).
 *
 * Cubre criterios de aceptacion B5: 2 (validacion), 3 (sin fila huerfana si
 * Stripe falla), 4 (nunca toca una reserva), 7 (idempotencia del marcado),
 * 8 (variables de la plantilla), 9 (registro en reservation_email_history).
 *
 * @internal
 */
final class CustomPaymentLinkServiceTest extends CIUnitTestCase
{
    private CustomPaymentLinkService $service;
    private object $repo;
    private object $reservationRepo;
    private object $templateService;
    private object $emailService;
    private object $stripe;
    private object $history;
    private object $access;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new class extends CustomPaymentLinkRepository {
            /** @var array<string,object> */
            public array $store = [];
            /** @var array<int,array{0:array,1:string}> */
            public array $createCalls = [];
            /** @var array<int,array> */
            public array $attachCalls = [];
            /** @var array<int,array> */
            public array $markPaidCalls = [];
            /** @var array<int,array{0:string,1:string}> */
            public array $statusCalls = [];
            /** @var array<int,string> */
            public array $deleteCalls = [];
            public bool $throwOnCreate = false;

            public function __construct()
            {
                // Skip parent (would build a real Model / DB handle).
            }

            public function seed(object $link): void
            {
                $this->store[$link->id] = $link;
            }

            public function getAll(): array
            {
                return array_values($this->store);
            }

            public function findById(string $id): ?object
            {
                return isset($this->store[$id]) ? clone $this->store[$id] : null;
            }

            public function create(array $data, string $id): string
            {
                $this->createCalls[] = [$data, $id];
                if ($this->throwOnCreate) {
                    throw new \RuntimeException('create boom');
                }
                $this->store[$id] = (object) array_merge([
                    'id'                       => $id,
                    'status'                   => 'pending',
                    'paid_at'                  => null,
                    'reservation_id'           => null,
                    'customer_name'            => null,
                    'customer_email'           => null,
                    'description'              => null,
                    'amount'                   => null,
                    'currency'                 => 'usd',
                    'payment_url'              => null,
                    'stripe_session_id'        => null,
                    'stripe_payment_intent_id' => null,
                    'expires_at'               => null,
                ], $data);

                return $id;
            }

            public function attachSession(string $id, string $sessionId, string $paymentUrl, ?string $expiresAt): bool
            {
                $this->attachCalls[] = [$id, $sessionId, $paymentUrl, $expiresAt];
                if (isset($this->store[$id])) {
                    $this->store[$id]->stripe_session_id = $sessionId;
                    $this->store[$id]->payment_url       = $paymentUrl;
                    $this->store[$id]->expires_at        = $expiresAt;
                    $this->store[$id]->status            = 'pending';
                }

                return true;
            }

            public function markPaid(string $id, string $paymentIntentId, string $paidAt): bool
            {
                $this->markPaidCalls[] = [$id, $paymentIntentId, $paidAt];
                if (isset($this->store[$id])) {
                    $this->store[$id]->status                   = 'paid';
                    $this->store[$id]->stripe_payment_intent_id = $paymentIntentId;
                    $this->store[$id]->paid_at                  = $paidAt;
                }

                return true;
            }

            public function updateStatus(string $id, string $status): bool
            {
                $this->statusCalls[] = [$id, $status];
                if (isset($this->store[$id])) {
                    $this->store[$id]->status = $status;
                }

                return true;
            }

            public function delete(string $id): bool
            {
                $this->deleteCalls[] = $id;
                unset($this->store[$id]);

                return true;
            }
        };

        $this->reservationRepo = new class extends ReservationRepository {
            /** @var array<string,object> */
            public array $existing = [];
            /** @var array<int,string> */
            public array $getByIdCalls = [];

            public function __construct()
            {
            }

            public function getById(string $id)
            {
                $this->getByIdCalls[] = $id;

                return $this->existing[$id] ?? null;
            }
        };

        $this->templateService = new class extends EmailTemplateService {
            /** @var array{subject:string,body:string} */
            public array $return = [
                'subject' => 'Your payment link',
                'body'    => '<p>Please pay {amount} for {description}</p>',
            ];
            public ?\Throwable $throw = null;
            /** @var array<int,array{0:string,1:array}> */
            public array $renderCalls = [];

            public function __construct()
            {
            }

            public function render(string $slug, array $variables): array
            {
                $this->renderCalls[] = [$slug, $variables];
                if ($this->throw !== null) {
                    throw $this->throw;
                }

                return $this->return;
            }
        };

        $this->emailService = new class extends BrevoEmailService {
            /** @var array<int,array{0:mixed,1:string,2:string}> */
            public array $sent = [];
            public ?\Throwable $throw = null;

            public function __construct()
            {
            }

            public function sendEmail($to, $subject, $htmlContent, array $cc = [], array $bcc = [])
            {
                $this->sent[] = [$to, $subject, $htmlContent];
                if ($this->throw !== null) {
                    throw $this->throw;
                }

                return true;
            }
        };

        $this->stripe = new class extends StripeService {
            /** @var array<int,array<string,mixed>> */
            public array $calls = [];
            public ?\Throwable $throw = null;
            public Session $session;

            public function __construct()
            {
                // Skip parent (Stripe API key wiring); build an offline Session.
                $this->session = Session::constructFrom([
                    'id'             => 'cs_test_123',
                    'url'            => 'https://checkout.stripe.com/pay/cs_test_123',
                    'expires_at'     => 1735689600,
                    'payment_status' => 'unpaid',
                    'payment_intent' => 'pi_test_1',
                    'metadata'       => [],
                ]);
            }

            public function createCheckoutSession(
                float $amount,
                string $customerEmail,
                string $reservationId,
                string $description = 'Event Reservation',
                float $gratuity = 0.0,
                array $metadata = [],
                ?int $expiresInSeconds = null
            ): Session {
                $this->calls[] = compact('amount', 'customerEmail', 'reservationId', 'description', 'gratuity', 'metadata', 'expiresInSeconds');
                if ($this->throw !== null) {
                    throw $this->throw;
                }

                return $this->session;
            }
        };

        $this->history = new class extends ReservationEmailHistoryModel {
            /** @var array<int,array<string,mixed>> */
            public array $inserts = [];
            public int $insertAttempts = 0;
            public bool $throwOnInsert = false;

            public function insert($row = null, bool $returnID = true)
            {
                $this->insertAttempts++;
                if ($this->throwOnInsert) {
                    throw new \RuntimeException('history insert exploded');
                }
                $this->inserts[] = (array) $row;

                return 'fake-history-id';
            }
        };

        $this->access = new class extends PaymentAccessService {
            /** @var array<int,array{0:string,1:string,2:string}> */
            public array $calls = [];

            public function __construct()
            {
                // Skip parent (would build a real Model / DB handle).
            }

            public function buildLink(string $targetType, string $targetId): string
            {
                $this->calls[] = [$targetType, $targetId, 'build'];

                return 'https://front.test/pay/fake-token';
            }

            public function ensureLink(string $targetType, string $targetId): string
            {
                $this->calls[] = [$targetType, $targetId, 'ensure'];

                return 'https://front.test/pay/fake-token';
            }
        };

        $this->service = new CustomPaymentLinkService();
        $this->setProp('repo', $this->repo);
        $this->setProp('reservationRepository', $this->reservationRepo);
        $this->setProp('emailTemplateService', $this->templateService);
        $this->setProp('emailService', $this->emailService);
        $this->setProp('stripeService', $this->stripe);
        $this->setProp('historyModel', $this->history);
        $this->setProp('accessService', $this->access);
    }

    private function setProp(string $name, $value): void
    {
        $ref = new \ReflectionProperty(CustomPaymentLinkService::class, $name);
        $ref->setAccessible(true);
        $ref->setValue($this->service, $value);
    }

    /** @return array{0:bool,1:?HTTPException} */
    private function catchHttp(callable $fn): array
    {
        try {
            $fn();
        } catch (HTTPException $e) {
            return [true, $e];
        }

        return [false, null];
    }

    private function validData(array $override = []): array
    {
        return array_merge([
            'customer_name'  => 'Jamie Client',
            'customer_email' => 'client@example.com',
            'description'    => 'Late fee for the event',
            'amount'         => 75.0,
        ], $override);
    }

    private function seededLink(array $override = []): object
    {
        $link = (object) array_merge([
            'id'                       => 'link-1',
            'status'                   => 'pending',
            'paid_at'                  => null,
            'reservation_id'           => null,
            'customer_name'            => 'Jamie Client',
            'customer_email'           => 'client@example.com',
            'description'              => 'Late fee',
            'amount'                   => 75.0,
            'currency'                 => 'usd',
            'payment_url'              => 'https://checkout.stripe.com/pay/cs_test_123',
            'stripe_session_id'        => 'cs_test_123',
            'stripe_payment_intent_id' => null,
            'expires_at'               => null,
        ], $override);
        $this->repo->seed($link);

        return $link;
    }

    private function session(array $override = []): object
    {
        return (object) array_merge([
            'id'             => 'cs_test_123',
            'payment_status' => 'paid',
            'payment_intent' => 'pi_test_9',
            'metadata'       => ['type' => 'custom_payment_link', 'payment_link_id' => 'link-1'],
        ], $override);
    }

    // -------------------------------------------------------------------------
    // createLink — camino feliz + criterio 3 (persistencia despues de Stripe)
    // -------------------------------------------------------------------------

    public function testCreateLinkHappyPathPersistsAfterStripeSucceeds(): void
    {
        $link = $this->service->createLink($this->validData(), 'admin@studio.com');

        // La sesion Stripe se creo antes de persistir.
        $this->assertCount(1, $this->stripe->calls);
        $this->assertCount(1, $this->repo->createCalls);
        $this->assertCount(1, $this->repo->attachCalls);

        $this->assertSame('pending', $link->status);
        $this->assertSame('cs_test_123', $link->stripe_session_id);
        $this->assertSame('https://checkout.stripe.com/pay/cs_test_123', $link->payment_url);
        $this->assertSame(75.0, $link->amount);
        $this->assertSame('usd', $link->currency);
        $this->assertSame('client@example.com', $link->customer_email);
    }

    public function testCreateLinkPassesCustomMetadataAndEmptyReservationIdToStripe(): void
    {
        $this->service->createLink($this->validData(), 'admin');

        $call = $this->stripe->calls[0];
        $this->assertSame('', $call['reservationId'], 'B5 pasa reservation_id vacio a Stripe');
        $this->assertSame(0.0, $call['gratuity']);
        $this->assertSame('Late fee for the event', $call['description']);
        $this->assertSame('custom_payment_link', $call['metadata']['type']);
        $this->assertArrayHasKey('payment_link_id', $call['metadata']);
        $this->assertArrayNotHasKey('reservation_id', $call['metadata']);

        // El id de la metadata coincide con el id persistido.
        $this->assertSame($this->repo->createCalls[0][1], $call['metadata']['payment_link_id']);
    }

    public function testCreateLinkWhitelistsFieldsAndTruncatesCreatedBy(): void
    {
        $this->service->createLink($this->validData([
            'reservation_id' => null,
            'currency'       => 'EUR',
        ]), str_repeat('x', 400));

        $persisted = $this->repo->createCalls[0][0];
        $this->assertSame(255, strlen($persisted['created_by']));
        $this->assertSame('eur', $persisted['currency']);
        $this->assertSame(75.0, $persisted['amount']);
        $this->assertNull($persisted['reservation_id']);
    }

    public function testCreateLinkDerivesExpiresAtFromSession(): void
    {
        $this->service->createLink($this->validData(), 'admin');

        $this->assertSame(
            date('Y-m-d H:i:s', 1735689600),
            $this->repo->attachCalls[0][3]
        );
    }

    public function testCreateLinkExpiresAtNullWhenSessionHasNoExpiry(): void
    {
        $this->stripe->session->expires_at = 0;

        $this->service->createLink($this->validData(), 'admin');

        $this->assertNull($this->repo->attachCalls[0][3]);
    }

    public function testCreateLinkResolvesExistingReservation(): void
    {
        $this->reservationRepo->existing['res-42'] = (object) ['id' => 'res-42'];

        $this->service->createLink($this->validData(['reservation_id' => 'res-42']), 'admin');

        $this->assertSame('res-42', $this->repo->createCalls[0][0]['reservation_id']);
    }

    // -------------------------------------------------------------------------
    // createLink — criterio 3: Stripe falla -> NO queda fila huerfana
    // -------------------------------------------------------------------------

    public function testCreateLinkStripeFailureLeavesNoOrphanRowAndThrows502(): void
    {
        $this->stripe->throw = new \RuntimeException('stripe api down');

        [$threw, $e] = $this->catchHttp(fn () => $this->service->createLink($this->validData(), 'admin'));

        $this->assertTrue($threw);
        $this->assertSame(502, $e->getCode());
        $this->assertStringContainsString('Could not create the payment session', $e->getMessage());

        // Criterio 3: NUNCA se intento persistir.
        $this->assertSame([], $this->repo->createCalls);
        $this->assertSame([], $this->repo->attachCalls);
        $this->assertSame([], $this->repo->store);
    }

    // -------------------------------------------------------------------------
    // createLink — el email es best-effort: un fallo no rompe la creacion
    // -------------------------------------------------------------------------

    public function testCreateLinkStillSucceedsWhenInitialEmailSendThrows(): void
    {
        $this->emailService->throw = new \RuntimeException('brevo 500');

        $link = $this->service->createLink($this->validData(), 'admin');

        $this->assertSame('pending', $link->status);
        $this->assertCount(1, $this->repo->createCalls);
    }

    public function testCreateLinkStillSucceedsWhenTemplateMissing(): void
    {
        $this->templateService->throw = new \RuntimeException('unknown slug');

        $link = $this->service->createLink($this->validData(), 'admin');

        $this->assertSame('pending', $link->status);
    }

    public function testCreateLinkSendsEmailOnCreation(): void
    {
        $this->service->createLink($this->validData(), 'admin');

        $this->assertCount(1, $this->emailService->sent);
        $this->assertSame('client@example.com', $this->emailService->sent[0][0]);
    }

    // -------------------------------------------------------------------------
    // Email — criterio 8 (variables) + escapado XSS + errores claros
    // -------------------------------------------------------------------------

    public function testDispatchEmailUsesTemplateSlugAndCriterion8Variables(): void
    {
        $this->service->createLink($this->validData(), 'admin');

        [$slug, $vars] = $this->templateService->renderCalls[0];
        $this->assertSame('custom_payment_link', $slug);
        $this->assertArrayHasKey('customer_name', $vars);
        $this->assertArrayHasKey('description', $vars);
        $this->assertArrayHasKey('amount', $vars);
        $this->assertArrayHasKey('payment_url', $vars);
        $this->assertSame('75.00', $vars['amount']);
    }

    public function testDispatchEmailEscapesFreeAdminText(): void
    {
        $this->seededLink([
            'id'            => 'link-x',
            'customer_name' => '<script>alert(1)</script>',
            'description'   => '<b>Late</b> fee',
        ]);

        $this->service->sendLinkEmail('link-x');

        $vars = $this->templateService->renderCalls[0][1];
        $this->assertStringNotContainsString('<script>', $vars['customer_name']);
        $this->assertStringContainsString('&lt;script&gt;', $vars['customer_name']);
        $this->assertStringContainsString('&lt;b&gt;', $vars['description']);
    }

    public function testDispatchEmailFallsBackToThereWhenNameEmpty(): void
    {
        $this->seededLink(['id' => 'link-n', 'customer_name' => '']);

        $this->service->sendLinkEmail('link-n');

        $this->assertSame('there', $this->templateService->renderCalls[0][1]['customer_name']);
    }

    public function testDispatchEmailGreetsWithFirstNameOnly(): void
    {
        $this->seededLink(['id' => 'link-fn', 'customer_name' => 'Vilma Garcia Lopez']);

        $this->service->sendLinkEmail('link-fn');

        $this->assertSame('Vilma', $this->templateService->renderCalls[0][1]['customer_name']);
    }

    public function testSendLinkEmailThrows404WhenLinkMissing(): void
    {
        [$threw, $e] = $this->catchHttp(fn () => $this->service->sendLinkEmail('nope'));

        $this->assertTrue($threw);
        $this->assertSame(404, $e->getCode());
    }

    public function testSendLinkEmailRejectsCancelledLink(): void
    {
        $this->seededLink(['id' => 'link-c', 'status' => 'cancelled']);

        [$threw, $e] = $this->catchHttp(fn () => $this->service->sendLinkEmail('link-c'));

        $this->assertTrue($threw);
        $this->assertSame(400, $e->getCode());
        $this->assertStringContainsString('cancelled', $e->getMessage());
    }

    public function testSendLinkEmailRejectsLinkWithoutEmail(): void
    {
        $this->seededLink(['id' => 'link-e', 'customer_email' => '']);

        [$threw, $e] = $this->catchHttp(fn () => $this->service->sendLinkEmail('link-e'));

        $this->assertTrue($threw);
        $this->assertSame(400, $e->getCode());
        $this->assertStringContainsString('no customer email', $e->getMessage());
        $this->assertSame([], $this->emailService->sent);
    }

    public function testMissingTemplateProducesClearActionable500(): void
    {
        $this->seededLink(['id' => 'link-t']);
        $this->templateService->throw = new \RuntimeException('No template and no PHP view for slug');

        [$threw, $e] = $this->catchHttp(fn () => $this->service->sendLinkEmail('link-t'));

        $this->assertTrue($threw);
        $this->assertSame(500, $e->getCode());
        $this->assertStringContainsString('CustomPaymentLinkEmailSeeder', $e->getMessage());
        $this->assertStringNotContainsString('No template and no PHP view', $e->getMessage());
    }

    public function testEmptyRenderedBodyProducesSameClear500(): void
    {
        $this->seededLink(['id' => 'link-b']);
        $this->templateService->return = ['subject' => 'Hi', 'body' => '   '];

        [$threw, $e] = $this->catchHttp(fn () => $this->service->sendLinkEmail('link-b'));

        $this->assertTrue($threw);
        $this->assertSame(500, $e->getCode());
        $this->assertStringContainsString('CustomPaymentLinkEmailSeeder', $e->getMessage());
        $this->assertSame([], $this->emailService->sent);
    }

    public function testSendFailureYields502AndFailedTimelineRow(): void
    {
        $this->seededLink(['id' => 'link-f', 'reservation_id' => 'res-1']);
        $this->emailService->throw = new \RuntimeException('brevo bounce');

        [$threw, $e] = $this->catchHttp(fn () => $this->service->sendLinkEmail('link-f'));

        $this->assertTrue($threw);
        $this->assertSame(502, $e->getCode());

        $rows = $this->timelineRows('Custom Payment Link Sent');
        $this->assertCount(1, $rows);
        $this->assertSame('Failed', $rows[0]['status']);
        $this->assertSame('email', $rows[0]['event_type']);
    }

    public function testSendOkRecordsSentTimelineRowWhenLinkHasReservation(): void
    {
        $this->seededLink(['id' => 'link-r', 'reservation_id' => 'res-1']);

        $this->service->sendLinkEmail('link-r');

        $rows = $this->timelineRows('Custom Payment Link Sent');
        $this->assertCount(1, $rows);
        $this->assertSame('Sent', $rows[0]['status']);
        $this->assertSame('email', $rows[0]['event_type']);
        $this->assertSame('System', $rows[0]['sent_by']);
        $this->assertNull($rows[0]['template_id']);
        $this->assertSame('res-1', $rows[0]['reservation_id']);
    }

    public function testSendOkDoesNotRecordTimelineWhenNoReservation(): void
    {
        $this->seededLink(['id' => 'link-nr', 'reservation_id' => null]);

        $this->service->sendLinkEmail('link-nr');

        $this->assertSame([], $this->history->inserts);
    }

    // -------------------------------------------------------------------------
    // handlePaidSession — criterios 4, 7, 9 + metadata no confiable
    // -------------------------------------------------------------------------

    public function testHandlePaidSessionMarksPendingLinkPaid(): void
    {
        $this->seededLink(['id' => 'link-1']);

        $result = $this->service->handlePaidSession($this->session());

        $this->assertTrue($result);
        $this->assertCount(1, $this->repo->markPaidCalls);
        $this->assertSame('link-1', $this->repo->markPaidCalls[0][0]);
        $this->assertSame('pi_test_9', $this->repo->markPaidCalls[0][1]);
        $this->assertSame('paid', $this->repo->store['link-1']->status);
    }

    public function testHandlePaidSessionAcceptsMetadataAsObject(): void
    {
        $this->seededLink(['id' => 'link-1']);

        $result = $this->service->handlePaidSession($this->session([
            'metadata' => (object) ['type' => 'custom_payment_link', 'payment_link_id' => 'link-1'],
        ]));

        $this->assertTrue($result);
        $this->assertCount(1, $this->repo->markPaidCalls);
    }

    public function testHandlePaidSessionReturnsFalseWhenNoMetadata(): void
    {
        $this->seededLink(['id' => 'link-1']);
        $session = (object) ['id' => 'cs_x', 'payment_intent' => 'pi_x'];

        $this->assertFalse($this->service->handlePaidSession($session));
        $this->assertSame([], $this->repo->markPaidCalls);
    }

    public function testHandlePaidSessionReturnsFalseWhenPaymentLinkIdMissing(): void
    {
        $this->seededLink(['id' => 'link-1']);

        $this->assertFalse($this->service->handlePaidSession($this->session([
            'metadata' => ['type' => 'custom_payment_link'],
        ])));
        $this->assertSame([], $this->repo->markPaidCalls);
    }

    public function testHandlePaidSessionReturnsFalseForUnknownLinkId(): void
    {
        $this->assertFalse($this->service->handlePaidSession($this->session([
            'metadata' => ['type' => 'custom_payment_link', 'payment_link_id' => 'ghost'],
        ])));
        $this->assertSame([], $this->repo->markPaidCalls);
    }

    public function testHandlePaidSessionIgnoresPlainReservationMetadata(): void
    {
        // Criterio 5: un evento de reserva normal no debe entrar al branch custom.
        $this->seededLink(['id' => 'link-1']);

        $this->assertFalse($this->service->handlePaidSession($this->session([
            'metadata' => ['reservation_id' => 'res-1'],
        ])));
        $this->assertSame([], $this->repo->markPaidCalls);
    }

    public function testHandlePaidSessionIsIdempotentOnAlreadyPaidLink(): void
    {
        // Criterio 7: segunda entrega mantiene el paid_at de la primera.
        $this->seededLink(['id' => 'link-1', 'status' => 'paid', 'paid_at' => '2026-01-01 00:00:00']);

        $result = $this->service->handlePaidSession($this->session());

        $this->assertTrue($result);
        $this->assertSame([], $this->repo->markPaidCalls, 'no vuelve a llamar markPaid');
        $this->assertSame('2026-01-01 00:00:00', $this->repo->store['link-1']->paid_at);
    }

    public function testHandlePaidSessionDoubleDeliveryMarksOnce(): void
    {
        $this->seededLink(['id' => 'link-1']);

        $this->assertTrue($this->service->handlePaidSession($this->session()));
        $firstPaidAt = $this->repo->store['link-1']->paid_at;
        $this->assertTrue($this->service->handlePaidSession($this->session()));

        $this->assertCount(1, $this->repo->markPaidCalls);
        $this->assertSame($firstPaidAt, $this->repo->store['link-1']->paid_at);
    }

    public function testHandlePaidSessionNeverTouchesAReservation(): void
    {
        // Criterio 4: aun con reservation_id, no se consulta ni se toca la reserva.
        $this->seededLink(['id' => 'link-1', 'reservation_id' => 'res-1']);

        $this->service->handlePaidSession($this->session());

        $this->assertSame([], $this->reservationRepo->getByIdCalls);
    }

    public function testHandlePaidSessionRecordsTimelineWhenLinkHasReservation(): void
    {
        // Criterio 9.
        $this->seededLink(['id' => 'link-1', 'reservation_id' => 'res-1']);

        $this->service->handlePaidSession($this->session());

        $rows = $this->timelineRows('Custom Payment Received');
        $this->assertCount(1, $rows);
        $this->assertSame('payment', $rows[0]['event_type']);
        $this->assertSame('Sent', $rows[0]['status']);
        $this->assertSame('res-1', $rows[0]['reservation_id']);
        $this->assertNull($rows[0]['template_id']);
        $this->assertStringContainsString('75.00', $rows[0]['email_body']);
    }

    public function testHandlePaidSessionDoesNotRecordTimelineWithoutReservation(): void
    {
        $this->seededLink(['id' => 'link-1', 'reservation_id' => null]);

        $this->service->handlePaidSession($this->session());

        $this->assertSame([], $this->history->inserts);
    }

    public function testHandlePaidSessionStillSucceedsWhenTimelineInsertThrows(): void
    {
        $this->seededLink(['id' => 'link-1', 'reservation_id' => 'res-1']);
        $this->history->throwOnInsert = true;

        $this->assertTrue($this->service->handlePaidSession($this->session()));
        $this->assertSame('paid', $this->repo->store['link-1']->status);
    }

    public function testHandlePaidSessionHandlesMissingPaymentIntent(): void
    {
        $this->seededLink(['id' => 'link-1', 'reservation_id' => 'res-1']);

        $this->service->handlePaidSession($this->session(['payment_intent' => null]));

        $this->assertSame('', $this->repo->markPaidCalls[0][1]);
        $body = $this->timelineRows('Custom Payment Received')[0]['email_body'];
        $this->assertStringContainsString('N/A', $body);
    }

    // -------------------------------------------------------------------------
    // Ciclo de vida
    // -------------------------------------------------------------------------

    public function testGetLinkThrows404WhenMissing(): void
    {
        [$threw, $e] = $this->catchHttp(fn () => $this->service->getLink('nope'));

        $this->assertTrue($threw);
        $this->assertSame(404, $e->getCode());
    }

    public function testGetLinkReturnsTheLink(): void
    {
        $this->seededLink(['id' => 'link-1']);

        $this->assertSame('link-1', $this->service->getLink('link-1')->id);
    }

    public function testListLinksReturnsAll(): void
    {
        $this->seededLink(['id' => 'link-1']);
        $this->seededLink(['id' => 'link-2']);

        $this->assertCount(2, $this->service->listLinks());
    }

    public function testCancelLinkRejectsPaidLink(): void
    {
        $this->seededLink(['id' => 'link-1', 'status' => 'paid']);

        [$threw, $e] = $this->catchHttp(fn () => $this->service->cancelLink('link-1'));

        $this->assertTrue($threw);
        $this->assertSame(400, $e->getCode());
        $this->assertSame([], $this->repo->statusCalls);
    }

    public function testCancelLinkSetsCancelledStatus(): void
    {
        $this->seededLink(['id' => 'link-1', 'status' => 'pending']);

        $link = $this->service->cancelLink('link-1');

        $this->assertSame([['link-1', 'cancelled']], $this->repo->statusCalls);
        $this->assertSame('cancelled', $link->status);
    }

    public function testDeleteLinkThrows404WhenMissing(): void
    {
        [$threw, $e] = $this->catchHttp(fn () => $this->service->deleteLink('nope'));

        $this->assertTrue($threw);
        $this->assertSame(404, $e->getCode());
        $this->assertSame([], $this->repo->deleteCalls);
    }

    public function testDeleteLinkRemovesExistingLink(): void
    {
        $this->seededLink(['id' => 'link-1']);

        $this->service->deleteLink('link-1');

        $this->assertSame(['link-1'], $this->repo->deleteCalls);
    }

    // -------------------------------------------------------------------------
    // helpers
    // -------------------------------------------------------------------------

    /** @return array<int,array<string,mixed>> */
    private function timelineRows(string $templateName): array
    {
        return array_values(array_filter(
            $this->history->inserts,
            static fn (array $row) => ($row['template_name'] ?? null) === $templateName
        ));
    }
}
