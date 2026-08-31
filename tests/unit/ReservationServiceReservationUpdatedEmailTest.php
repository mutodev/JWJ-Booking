<?php

namespace Tests\Unit;

use App\Models\ReservationEmailHistoryModel;
use App\Repositories\ReservationRepository;
use App\Services\BrevoEmailService;
use App\Services\EmailTemplateService;
use App\Services\ReservationService;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * B6 — ReservationService::sendReservationUpdatedEmail().
 *
 * Envio MANUAL del email `reservation_updated` con el desglose actualizado.
 * 404 sin reserva; 400 sin email; 500 si el envio falla (y registra `Failed` en
 * el historial); exito -> registra `Sent` y devuelve ['sent'=>1,'email'=>...].
 * Las filas HTML balance_due_row / refund_row / discount_row aparecen solo por
 * encima de la tolerancia de 0.009 y con cada valor dinamico escapado por esc().
 *
 * Sin base de datos: se doblan repository, emailTemplateService, emailService y
 * historyModel (subclase de ReservationEmailHistoryModel, exigida por el type
 * hint de historyModel()).
 *
 * Cubre criterio de aceptacion B6: 10.
 *
 * @internal
 */
final class ReservationServiceReservationUpdatedEmailTest extends CIUnitTestCase
{
    private ReservationService $service;
    private object $repo;
    private object $templates;
    private object $emails;
    private object $history;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new class extends ReservationRepository {
            /** @var array<string,?object> */
            public array $rows = [];

            public function __construct()
            {
            }

            public function getById(string $id)
            {
                return $this->rows[$id] ?? null;
            }
        };

        $this->templates = new class extends EmailTemplateService {
            /** @var array<int,array{0:string,1:array}> */
            public array $renderCalls = [];
            public array $return = ['subject' => 'Your reservation has been updated', 'body' => '<p>body</p>'];

            public function __construct()
            {
            }

            public function render(string $slug, array $variables): array
            {
                $this->renderCalls[] = [$slug, $variables];

                return $this->return;
            }
        };

        $this->emails = new class extends BrevoEmailService {
            /** @var array<int,array> */
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

        $this->history = new class extends ReservationEmailHistoryModel {
            /** @var array<int,array<string,mixed>> */
            public array $inserts = [];

            public function insert($row = null, bool $returnID = true)
            {
                $this->inserts[] = (array) $row;

                return 'fake-history-id';
            }
        };

        $this->service = new ReservationService();
        $this->inject('repository', $this->repo);
        $this->inject('emailTemplateService', $this->templates);
        $this->inject('emailService', $this->emails);
        $this->inject('historyModel', $this->history);
    }

    private function inject(string $prop, $value): void
    {
        $ref = new \ReflectionProperty(ReservationService::class, $prop);
        $ref->setAccessible(true);
        $ref->setValue($this->service, $value);
    }

    private function seed(array $override = []): object
    {
        $row = (object) array_merge([
            'id'                 => 'res-1',
            'email'              => 'client@example.com',
            'full_name'          => 'Jamie Client',
            'service_name'       => 'Birthday Bash',
            'event_date'         => '2026-12-01',
            'base_price'         => 200.0,
            'addons_total'       => 50.0,
            'extra_children_fee' => 0.0,
            'travel_fee'         => 25.0,
            'expedite_fee'       => 0.0,
            'discount_amount'    => 0.0,
            'total_amount'       => 275.0,
            'amount_paid'        => null,
            'balance_due'        => 0.0,
        ], $override);
        $this->repo->rows[$row->id] = $row;

        return $row;
    }

    private function vars(): array
    {
        return $this->templates->renderCalls[0][1];
    }

    // -------------------------------------------------------------------------
    // Guards
    // -------------------------------------------------------------------------

    public function testThrows404WhenReservationMissing(): void
    {
        try {
            $this->service->sendReservationUpdatedEmail('nope');
            $this->fail('expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertSame(404, $e->getCode());
        }
        $this->assertSame([], $this->emails->sent);
    }

    public function testThrows400WhenReservationHasNoEmail(): void
    {
        $this->seed(['id' => 'res-x', 'email' => '']);

        try {
            $this->service->sendReservationUpdatedEmail('res-x');
            $this->fail('expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertSame(400, $e->getCode());
        }
        $this->assertSame([], $this->emails->sent);
        $this->assertSame([], $this->history->inserts);
    }

    // -------------------------------------------------------------------------
    // Exito
    // -------------------------------------------------------------------------

    public function testSuccessSendsEmailAndReturnsSentPayload(): void
    {
        $this->seed();

        $result = $this->service->sendReservationUpdatedEmail('res-1');

        $this->assertSame(['sent' => 1, 'email' => 'client@example.com'], $result);
        $this->assertCount(1, $this->emails->sent);
        $this->assertSame('client@example.com', $this->emails->sent[0][0]);
        $this->assertSame('reservation_updated', $this->templates->renderCalls[0][0]);
    }

    public function testSuccessRecordsSentRowInHistory(): void
    {
        $this->seed();

        $this->service->sendReservationUpdatedEmail('res-1');

        $this->assertCount(1, $this->history->inserts);
        $row = $this->history->inserts[0];
        $this->assertSame('Reservation Updated', $row['template_name']);
        $this->assertSame('Sent', $row['status']);
        $this->assertSame('System', $row['sent_by']);
        $this->assertSame('res-1', $row['reservation_id']);
        $this->assertSame('client@example.com', $row['recipient_email']);
        $this->assertNull($row['template_id']);
    }

    // -------------------------------------------------------------------------
    // Fallo de envio -> 500 + fila Failed
    // -------------------------------------------------------------------------

    public function testSendFailureThrows500AndRecordsFailedRow(): void
    {
        $this->seed();
        $this->emails->throw = new \RuntimeException('brevo bounce');

        try {
            $this->service->sendReservationUpdatedEmail('res-1');
            $this->fail('expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertSame(500, $e->getCode());
        }

        $this->assertCount(1, $this->history->inserts);
        $this->assertSame('Failed', $this->history->inserts[0]['status']);
        $this->assertSame('Reservation Updated', $this->history->inserts[0]['template_name']);
    }

    // -------------------------------------------------------------------------
    // balance_due_row / refund_row / discount_row — tolerancia 0.009
    // -------------------------------------------------------------------------

    public function testBalanceDueRowPresentOnlyWhenBalanceExceedsTolerance(): void
    {
        $this->seed(['balance_due' => 40.0]);
        $this->service->sendReservationUpdatedEmail('res-1');

        $vars = $this->vars();
        $this->assertNotSame('', $vars['balance_due_row']);
        $this->assertStringContainsString('Balance Due', $vars['balance_due_row']);
        $this->assertStringContainsString('40.00', $vars['balance_due_row']);
    }

    public function testBalanceDueRowAbsentWhenBalanceIsZero(): void
    {
        $this->seed(['balance_due' => 0.0]);
        $this->service->sendReservationUpdatedEmail('res-1');

        $this->assertSame('', $this->vars()['balance_due_row']);
    }

    public function testBalanceDueRowAbsentWhenBalanceBelowTolerance(): void
    {
        // El codigo redondea balance_due a 2 decimales ANTES de comparar contra
        // 0.009, asi que 0.004 -> 0.00 -> fila ausente.
        $this->seed(['balance_due' => 0.004]);
        $this->service->sendReservationUpdatedEmail('res-1');

        $this->assertSame('', $this->vars()['balance_due_row']);
    }

    public function testRefundRowPresentOnlyWhenOverpaymentExceedsTolerance(): void
    {
        // amount_paid 300, total 275 -> overpaid 25
        $this->seed(['amount_paid' => 300.0, 'total_amount' => 275.0]);
        $this->service->sendReservationUpdatedEmail('res-1');

        $vars = $this->vars();
        $this->assertNotSame('', $vars['refund_row']);
        $this->assertStringContainsString('Refund Due', $vars['refund_row']);
        $this->assertStringContainsString('25.00', $vars['refund_row']);
    }

    public function testRefundRowAbsentWhenPaidExactlyTheTotal(): void
    {
        $this->seed(['amount_paid' => 275.0, 'total_amount' => 275.0]);
        $this->service->sendReservationUpdatedEmail('res-1');

        $this->assertSame('', $this->vars()['refund_row']);
    }

    public function testRefundRowAbsentWhenAmountPaidIsNull(): void
    {
        $this->seed(['amount_paid' => null, 'total_amount' => 275.0]);
        $this->service->sendReservationUpdatedEmail('res-1');

        $this->assertSame('', $this->vars()['refund_row']);
    }

    public function testDiscountRowPresentOnlyWhenDiscountExceedsTolerance(): void
    {
        $this->seed(['discount_amount' => 15.0]);
        $this->service->sendReservationUpdatedEmail('res-1');

        $this->assertStringContainsString('Discount', $this->vars()['discount_row']);
        $this->assertStringContainsString('15.00', $this->vars()['discount_row']);
    }

    public function testDiscountRowAbsentWhenNoDiscount(): void
    {
        $this->seed(['discount_amount' => 0.0]);
        $this->service->sendReservationUpdatedEmail('res-1');

        $this->assertSame('', $this->vars()['discount_row']);
    }

    // -------------------------------------------------------------------------
    // gratuity_row — fila "Gratuity / Tip" (fix del certifier, tolerancia 0.009)
    // -------------------------------------------------------------------------

    public function testGratuityRowPresentWhenGratuityExceedsTolerance(): void
    {
        $this->seed(['gratuity_amount' => 50.0]);
        $this->service->sendReservationUpdatedEmail('res-1');

        $row = $this->vars()['gratuity_row'];
        $this->assertNotSame('', $row);
        $this->assertStringContainsString('Gratuity / Tip', $row);
        $this->assertStringContainsString('50.00', $row);
    }

    public function testGratuityRowAbsentWhenGratuityIsZero(): void
    {
        $this->seed(['gratuity_amount' => 0.0]);
        $this->service->sendReservationUpdatedEmail('res-1');

        $this->assertSame('', $this->vars()['gratuity_row']);
    }

    public function testGratuityRowAbsentWhenGratuityIsNull(): void
    {
        // La reserva base no trae la propiedad gratuity_amount (equivale a null).
        $this->seed();
        $this->service->sendReservationUpdatedEmail('res-1');

        $this->assertSame('', $this->vars()['gratuity_row']);
    }

    // -------------------------------------------------------------------------
    // overpaid / refund con propina — bug #1 del certifier
    // -------------------------------------------------------------------------

    public function testRefundRowAbsentWhenAmountPaidEqualsTotalPlusGratuity(): void
    {
        // amount_paid 600 = total 500 + propina 100 -> overpaid = 0.
        // Antes del fix mostraba "Refund Due $100" (bug #1).
        $this->seed([
            'amount_paid'     => 600.0,
            'total_amount'    => 500.0,
            'gratuity_amount' => 100.0,
        ]);
        $this->service->sendReservationUpdatedEmail('res-1');

        $this->assertSame('', $this->vars()['refund_row'], 'la propina no es un reembolso fantasma');
    }

    public function testRefundRowPresentForRealOverpaymentAboveTotalPlusGratuity(): void
    {
        // amount_paid 600 ; total 400 + propina 100 = 500 -> overpaid = 100.
        $this->seed([
            'amount_paid'     => 600.0,
            'total_amount'    => 400.0,
            'gratuity_amount' => 100.0,
        ]);
        $this->service->sendReservationUpdatedEmail('res-1');

        $row = $this->vars()['refund_row'];
        $this->assertNotSame('', $row);
        $this->assertStringContainsString('Refund Due', $row);
        $this->assertStringContainsString('100.00', $row);
    }

    // -------------------------------------------------------------------------
    // XSS — cada valor dinamico escapado con esc()
    // -------------------------------------------------------------------------

    public function testDynamicValuesAreHtmlEscaped(): void
    {
        $this->seed(['service_name' => '<script>alert(1)</script>']);
        $this->service->sendReservationUpdatedEmail('res-1');

        $vars = $this->vars();
        $this->assertStringNotContainsString('<script>', $vars['service_name']);
        $this->assertStringContainsString('&lt;script&gt;', $vars['service_name']);
    }
}
