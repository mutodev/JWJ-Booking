<?php

namespace Tests\Unit;

use App\Models\ReservationEmailHistoryModel;
use App\Services\ReservationService;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * B3 — "Registrar en el historial el envio del link de pago y el pago recibido".
 *
 * El historial (reservation_email_history) pasa a ser un timeline de la reserva:
 * emails automaticos del sistema (Payment Link Sent, Reservation Received, Week
 * Reminder) y eventos que no son email (Payment Received, event_type = payment).
 *
 * Estrategia sin base de datos (regla del proyecto):
 *  - historyModel() se dobla via ReflectionProperty $historyModel con un fake que
 *    captura cada insert() (y sirve where()/orderBy()/findAll() para
 *    getEmailHistory()).
 *  - emailService (BrevoEmailService) se dobla via ReflectionProperty para forzar
 *    exito o excepcion.
 *  - emailTemplateService / repository / reservationAddonRepository se doblan
 *    igual.
 *  - Metodos privados (recordEmailHistory, recordSystemEmail,
 *    recordPaymentReceivedEvent) se invocan via ReflectionMethod.
 *
 * @internal
 */
final class ReservationServicePaymentHistoryTest extends CIUnitTestCase
{
    private ReservationService $service;
    private object $history;
    private object $emailService;
    private object $templateService;
    private object $repo;

    protected function setUp(): void
    {
        parent::setUp();

        // El fake DEBE extender el Model real: historyModel() declara el tipo de
        // retorno App\Models\ReservationEmailHistoryModel y un doble suelto
        // dispararia un TypeError que recordEmailHistory() se tragaria en su
        // catch, ocultando el insert.
        $this->history = new class extends ReservationEmailHistoryModel {
            /** @var array<int,array<string,mixed>> */
            public array $inserts = [];
            /** @var array<int,object> */
            public array $rows = [];
            public ?array $ordered = null;
            public bool $throwOnInsert = false;
            public bool $throwOnFind = false;
            public int $insertAttempts = 0;

            public function insert($row = null, bool $returnID = true)
            {
                $this->insertAttempts++;
                if ($this->throwOnInsert) {
                    throw new \RuntimeException('history insert exploded');
                }
                $this->inserts[] = (array) $row;

                return 'fake-history-id';
            }

            public function where($key, $value = null, ?bool $escape = null)
            {
                return $this;
            }

            public function orderBy(?string $orderBy, string $direction = '', ?bool $escape = null)
            {
                $this->ordered = [$orderBy, strtoupper($direction)];

                return $this;
            }

            public function findAll(?int $limit = null, int $offset = 0)
            {
                if ($this->throwOnFind) {
                    throw new \RuntimeException('history find exploded');
                }

                return $this->rows;
            }
        };

        $this->emailService = new class {
            /** @var callable|null */
            public $onSend = null;
            /** @var array<int,array{0:string,1:string,2:string,3:array}> */
            public array $sent = [];

            public function sendEmail($to, $subject, $body, array $cc = [])
            {
                $this->sent[] = [$to, $subject, $body, $cc];
                if ($this->onSend !== null) {
                    ($this->onSend)($to, $subject, $body);
                }

                return true;
            }

            public function resolveCc(array $cc, string $to): array
            {
                return $cc;
            }
        };

        $this->templateService = new class {
            /** @var callable|null */
            public $onRender = null;

            public function render($slug, array $vars = [])
            {
                if ($this->onRender !== null) {
                    ($this->onRender)($slug, $vars);
                }

                return ['subject' => 'Subject for ' . $slug, 'body' => '<p>Body for ' . $slug . '</p>'];
            }
        };

        $this->repo = new class {
            public ?object $reservation = null;
            /** @var array<int,object> */
            public array $upcoming = [];
            /** @var array<int,array{0:string,1:array}> */
            public array $updates = [];

            public function getById(string $id)
            {
                return $this->reservation;
            }

            public function update(string $id, array $data)
            {
                $this->updates[] = [$id, $data];
                if ($this->reservation !== null) {
                    foreach ($data as $k => $v) {
                        $this->reservation->{$k} = $v;
                    }
                }

                return $this->reservation;
            }

            public function getUpcomingForReminder(): array
            {
                return $this->upcoming;
            }
        };

        $addonRepo = new class {
            public function getDetailedByReservation($id)
            {
                return [];
            }
        };

        $this->service = new ReservationService();
        $this->setProp('historyModel', $this->history);
        $this->setProp('emailService', $this->emailService);
        $this->setProp('emailTemplateService', $this->templateService);
        $this->setProp('repository', $this->repo);
        $this->setProp('reservationAddonRepository', $addonRepo);
        $this->setProp('brevoContactService', null);
    }

    private function setProp(string $name, $value): void
    {
        $ref = new \ReflectionProperty(ReservationService::class, $name);
        $ref->setAccessible(true);
        $ref->setValue($this->service, $value);
    }

    private function invoke(string $method, ...$args)
    {
        $ref = new \ReflectionMethod(ReservationService::class, $method);
        $ref->setAccessible(true);

        return $ref->invoke($this->service, ...$args);
    }

    private function reservation(array $override = []): object
    {
        return (object) array_merge([
            'id'                     => 'res-1',
            'email'                  => 'client@example.com',
            'full_name'              => 'Jamie Client',
            'service_name'           => 'Jukebox Live',
            'event_date'             => '2026-12-24',
            'event_time'             => '14:00',
            'entertainment_start_time' => '14:30',
            'event_address'          => '123 Main St',
            'children_count'         => 12,
            'children_age_range'     => '5-7',
            'performers_count'       => 2,
            'duration_hours'         => 2.0,
            'total_amount'           => 350.0,
            'gratuity_amount'        => 40.0,
            'promo_code'             => null,
            'discount_amount'        => 0,
            'birthday_child_name'    => null,
            'description'            => null,
            'is_paid'                => false,
        ], $override);
    }

    /** @return array<int,array<string,mixed>> */
    private function insertsNamed(string $templateName): array
    {
        return array_values(array_filter(
            $this->history->inserts,
            static fn (array $row) => ($row['template_name'] ?? null) === $templateName
        ));
    }

    // -------------------------------------------------------------------------
    // Criterio 2 — allowedFields
    // -------------------------------------------------------------------------

    public function testModelAllowsEventTypeField(): void
    {
        $model = new ReservationEmailHistoryModel();
        $allowed = (new \ReflectionProperty($model, 'allowedFields'))->getValue($model);

        $this->assertContains('event_type', $allowed);
    }

    // -------------------------------------------------------------------------
    // Criterio 1 — migracion
    // -------------------------------------------------------------------------

    public function testMigrationMakesTemplateIdNullableAndAddsEventType(): void
    {
        $src = file_get_contents(
            APPPATH . 'Database/Migrations/2026-08-28-030000_AlterReservationEmailHistoryForSystemEvents.php'
        );

        $this->assertStringContainsString('DROP FOREIGN KEY', $src);
        $this->assertStringContainsString('MODIFY `template_id` CHAR(36) NULL', $src);
        $this->assertMatchesRegularExpression('/ADD COLUMN `event_type` VARCHAR\(30\) NOT NULL DEFAULT \'email\'/', $src);
        $this->assertStringContainsString('ADD INDEX `reservation_email_history_event_type`', $src);
        $this->assertStringContainsString('ON DELETE SET NULL', $src);
        // down() revierte
        $this->assertStringContainsString('MODIFY `template_id` CHAR(36) NOT NULL', $src);
        $this->assertStringContainsString('DROP COLUMN `event_type`', $src);
        // FK resuelto dinamicamente
        $this->assertStringContainsString('information_schema', $src);
    }

    // -------------------------------------------------------------------------
    // Criterio 3 — recordEmailHistory nunca propaga
    // -------------------------------------------------------------------------

    public function testRecordEmailHistorySwallowsInsertException(): void
    {
        $this->history->throwOnInsert = true;

        $this->invoke('recordEmailHistory', [
            'reservation_id' => 'res-1',
            'template_name'  => 'Whatever',
            'email_body'     => '<p>x</p>',
            'status'         => 'Sent',
        ]);

        $this->assertSame(1, $this->history->insertAttempts);
        $this->assertLogged('error', 'Failed to record reservation email history: history insert exploded');
    }

    public function testRecordEmailHistoryDefaultsEventTypeAndBodyPlaceholder(): void
    {
        $this->invoke('recordEmailHistory', [
            'reservation_id' => 'res-1',
            'template_name'  => 'Whatever',
            'status'         => 'Sent',
        ]);

        $row = $this->history->inserts[0];
        $this->assertSame('email', $row['event_type']);
        $this->assertSame('—', $row['email_body']);
    }

    public function testRecordEmailHistoryNormalizesEmptyEventTypeAndEmptyBody(): void
    {
        $this->invoke('recordEmailHistory', [
            'reservation_id' => 'res-1',
            'template_name'  => 'Whatever',
            'event_type'     => '',
            'email_body'     => '',
            'status'         => 'Sent',
        ]);

        $row = $this->history->inserts[0];
        $this->assertSame('email', $row['event_type']);
        $this->assertSame('—', $row['email_body']);
    }

    // -------------------------------------------------------------------------
    // Criterio 4 — sendPaymentEmail
    // -------------------------------------------------------------------------

    public function testSendPaymentEmailRecordsSentRowOnSuccess(): void
    {
        $this->repo->reservation = $this->reservation();

        $this->service->sendPaymentEmail('res-1');

        $rows = $this->insertsNamed('Payment Link Sent');
        $this->assertCount(1, $rows);
        $this->assertSame('Sent', $rows[0]['status']);
        $this->assertSame('email', $rows[0]['event_type']);
        $this->assertSame('System', $rows[0]['sent_by']);
        $this->assertNull($rows[0]['template_id']);
        $this->assertSame('client@example.com', $rows[0]['recipient_email']);
    }

    public function testSendPaymentEmailRecordsFailedRowAndRethrowsOnSendError(): void
    {
        $this->repo->reservation = $this->reservation();
        $this->emailService->onSend = static function (): void {
            throw new \RuntimeException('brevo 500');
        };

        $threw = false;
        try {
            $this->service->sendPaymentEmail('res-1');
        } catch (HTTPException $e) {
            $threw = true;
            $this->assertStringContainsString('Failed to send payment email', $e->getMessage());
        }

        $this->assertTrue($threw, 'sendPaymentEmail debe relanzar la HTTPException');

        $rows = $this->insertsNamed('Payment Link Sent');
        $this->assertCount(1, $rows);
        $this->assertSame('Failed', $rows[0]['status']);
    }

    // -------------------------------------------------------------------------
    // Criterio 5 — sendConfirmationEmail
    // -------------------------------------------------------------------------

    public function testSendConfirmationEmailRecordsSentRowOnSuccess(): void
    {
        $this->service->sendConfirmationEmail($this->reservation());

        $rows = $this->insertsNamed('Reservation Received');
        $this->assertCount(1, $rows);
        $this->assertSame('Sent', $rows[0]['status']);
        $this->assertSame('email', $rows[0]['event_type']);
    }

    public function testSendConfirmationEmailRecordsFailedRowAndDoesNotPropagate(): void
    {
        $this->emailService->onSend = static function (): void {
            throw new \RuntimeException('render/send boom');
        };

        // No debe propagar.
        $this->service->sendConfirmationEmail($this->reservation());

        $rows = $this->insertsNamed('Reservation Received');
        $this->assertCount(1, $rows);
        $this->assertSame('Failed', $rows[0]['status']);
        $this->assertLogged('error', 'Failed to send confirmation email: render/send boom');
    }

    public function testSendConfirmationEmailRecordsFailedRowWhenRenderThrows(): void
    {
        $this->templateService->onRender = static function (): void {
            throw new \RuntimeException('template render boom');
        };

        $this->service->sendConfirmationEmail($this->reservation());

        $rows = $this->insertsNamed('Reservation Received');
        $this->assertCount(1, $rows);
        $this->assertSame('Failed', $rows[0]['status']);
        // Sin render, subject/body vacios -> placeholder no vacio en email_body.
        $this->assertSame('—', $rows[0]['email_body']);
    }

    public function testSendConfirmationEmailSkipsWhenNoRecipient(): void
    {
        $this->service->sendConfirmationEmail($this->reservation(['email' => '']));

        $this->assertSame([], $this->history->inserts);
    }

    // -------------------------------------------------------------------------
    // Criterio 6 — sendWeekReminders
    // -------------------------------------------------------------------------

    public function testSendWeekRemindersRecordsPerReservationAndCountsOnlySuccess(): void
    {
        $this->repo->upcoming = [
            $this->reservation(['id' => 'ok-1', 'email' => 'ok@example.com']),
            $this->reservation(['id' => 'bad-1', 'email' => 'fail@example.com']),
        ];
        $this->emailService->onSend = static function (string $to): void {
            if ($to === 'fail@example.com') {
                throw new \RuntimeException('bounce');
            }
        };

        $sent = $this->service->sendWeekReminders();

        $this->assertSame(1, $sent);

        $rows = $this->insertsNamed('Week Reminder');
        $this->assertCount(2, $rows);

        $byStatus = [];
        foreach ($rows as $row) {
            $byStatus[$row['status']] = $row['reservation_id'];
        }
        $this->assertSame('ok-1', $byStatus['Sent'] ?? null);
        $this->assertSame('bad-1', $byStatus['Failed'] ?? null);
    }

    // -------------------------------------------------------------------------
    // Criterio 7 — handlePaymentCompleted idempotente
    // -------------------------------------------------------------------------

    public function testHandlePaymentCompletedInsertsPaymentEventExactlyOnce(): void
    {
        // B1 criterio 3 cambio el contrato de sendPaymentConfirmationEmail():
        // un email de cliente vacio ya NO sale en silencio, ahora registra
        // legitimamente una fila 'Payment Received' con event_type='email' y
        // status='Failed'. Por eso este test ya no puede afirmar "exactamente
        // una fila Payment Received": ahora hay dos (la del email fallido +
        // la del evento de pago). Lo que este test realmente verifica es que
        // handlePaymentCompleted() no DUPLICA el EVENTO de pago cuando se lo
        // invoca dos veces (idempotencia por is_paid), asi que la asercion se
        // ajusta para filtrar solo event_type === 'payment'.
        $this->repo->reservation = $this->reservation(['email' => '']);

        $this->service->handlePaymentCompleted('res-1', 'pi_123');
        $this->service->handlePaymentCompleted('res-1', 'pi_123');

        $paymentRows = array_values(array_filter(
            $this->insertsNamed('Payment Received'),
            static fn (array $row) => ($row['event_type'] ?? null) === 'payment'
        ));
        $this->assertCount(1, $paymentRows);
        $this->assertSame('payment', $paymentRows[0]['event_type']);
        $this->assertSame('Sent', $paymentRows[0]['status']);
    }

    // -------------------------------------------------------------------------
    // recordPaymentReceivedEvent — escapado y forma de la fila
    // -------------------------------------------------------------------------

    public function testRecordPaymentReceivedEventShapeAndEscaping(): void
    {
        $reservation = $this->reservation([
            'service_name'    => '<script>alert("x")</script>',
            'total_amount'    => 100.0,
            'gratuity_amount' => 25.0,
        ]);

        $this->invoke('recordPaymentReceivedEvent', $reservation, 'pi_"><b>evil');

        $row = $this->history->inserts[0];
        $this->assertSame('payment', $row['event_type']);
        $this->assertSame('Payment Received', $row['template_name']);
        $this->assertSame('Sent', $row['status']);
        $this->assertSame('System', $row['sent_by']);
        $this->assertNull($row['template_id']);

        $body = $row['email_body'];
        $this->assertStringNotContainsString('<script>', $body);
        $this->assertStringNotContainsString('<b>evil', $body);
        $this->assertStringContainsString('&lt;script&gt;', $body);
        // total pagado = 100 + 25
        $this->assertStringContainsString('125.00', $body);
    }

    public function testRecordPaymentReceivedEventHandlesMissingPaymentIntent(): void
    {
        $this->invoke('recordPaymentReceivedEvent', $this->reservation(), '');

        $body = $this->history->inserts[0]['email_body'];
        $this->assertStringContainsString('Payment intent: N/A', $body);
    }

    // -------------------------------------------------------------------------
    // recordSystemEmail — defaults
    // -------------------------------------------------------------------------

    public function testRecordSystemEmailDefaultsEventTypeToEmail(): void
    {
        $this->invoke('recordSystemEmail', 'res-1', 'Reservation Received', 'a@b.com', 'Subj', '<p>b</p>', 'Sent');

        $row = $this->history->inserts[0];
        $this->assertSame('email', $row['event_type']);
        $this->assertSame('System', $row['sent_by']);
        $this->assertNull($row['template_id']);
        $this->assertNull($row['cc_emails']);
    }

    public function testRecordSystemEmailEmptyBodyBecomesPlaceholder(): void
    {
        $this->invoke('recordSystemEmail', 'res-1', 'Week Reminder', 'a@b.com', 'Subj', '', 'Failed');

        $this->assertSame('—', $this->history->inserts[0]['email_body']);
    }

    // -------------------------------------------------------------------------
    // Criterio 8 / 10 — getEmailHistory
    // -------------------------------------------------------------------------

    public function testGetEmailHistoryOrdersBySentAtDesc(): void
    {
        $this->repo->reservation = $this->reservation();
        $this->history->rows = [(object) ['id' => 'h1'], (object) ['id' => 'h2']];

        $result = $this->service->getEmailHistory('res-1');

        $this->assertSame(['sent_at', 'DESC'], $this->history->ordered);
        $this->assertCount(2, $result);
    }

    public function testGetEmailHistoryReturnsEmptyArrayWhenNoRows(): void
    {
        $this->repo->reservation = $this->reservation();
        $this->history->rows = [];

        $this->assertSame([], $this->service->getEmailHistory('res-1'));
    }

    public function testGetEmailHistoryThrowsWhenReservationMissing(): void
    {
        $this->repo->reservation = null;

        $this->expectException(HTTPException::class);
        $this->service->getEmailHistory('nope');
    }
}
