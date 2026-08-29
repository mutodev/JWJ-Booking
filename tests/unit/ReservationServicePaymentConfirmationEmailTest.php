<?php

namespace Tests\Unit;

use App\Models\ReservationEmailHistoryModel;
use App\Services\ReservationService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * B1 — "Clientes que no reciben el email despues del pago"
 * (mejora de codigo: visibilidad en el historial).
 *
 * Alcance certificable: sendPaymentConfirmationEmail() y sus helpers
 * (sanitizeThrowableReason, buildEmailFailureBody, paymentConfirmationSubject)
 * mas el hardening F2 de recordPaymentReceivedEvent(). Las acciones de
 * infraestructura (Stripe / Brevo / VPS) estan explicitamente fuera.
 *
 * Estrategia sin base de datos (regla del proyecto): CIUnitTestCase + Reflection.
 *  - historyModel() se dobla con una SUBCLASE de ReservationEmailHistoryModel
 *    (el type hint de retorno lo exige; un doble suelto dispara un TypeError que
 *    recordEmailHistory() se tragaria en su catch, ocultando el insert).
 *  - emailService (BrevoEmailService) se dobla para forzar exito / excepcion.
 *  - emailTemplateService se dobla para controlar subject/body renderizados y
 *    para forzar excepcion en render.
 *
 * @internal
 */
final class ReservationServicePaymentConfirmationEmailTest extends CIUnitTestCase
{
    private ReservationService $service;
    private object $history;
    private object $emailService;
    private object $templateService;
    private object $repo;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->emailService = new class {
            /** @var array<int,array{0:mixed,1:string,2:string}> */
            public array $sent = [];
            public ?\Throwable $throw = null;

            public function sendEmail($to, $subject, $body, array $cc = [])
            {
                $this->sent[] = [$to, $subject, $body];
                if ($this->throw !== null) {
                    throw $this->throw;
                }

                return true;
            }

            public function resolveCc(array $cc, string $to): array
            {
                return $cc;
            }
        };

        $this->templateService = new class {
            public array $return = [
                'subject' => 'Your payment is confirmed',
                'body'    => '<p>Thank you, your payment was received.</p>',
            ];
            public ?\Throwable $throw = null;

            public function render($slug, array $vars = [])
            {
                if ($this->throw !== null) {
                    throw $this->throw;
                }

                return $this->return;
            }
        };

        $this->repo = new class {
            public ?object $reservation = null;
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
            'id'                       => 'res-1',
            'email'                    => 'client@example.com',
            'full_name'                => 'Jamie Client',
            'service_name'             => 'Jukebox Live',
            'event_date'               => '2026-12-24',
            'event_time'               => '14:00',
            'entertainment_start_time' => '14:30',
            'event_address'            => '123 Main St',
            'children_count'           => 12,
            'children_age_range'       => '5-7',
            'performers_count'         => 2,
            'duration_hours'           => 2.0,
            'total_amount'             => 350.0,
            'gratuity_amount'          => 40.0,
            'promo_code'               => null,
            'discount_amount'          => 0,
            'birthday_child_name'      => null,
            'description'              => null,
            'is_paid'                  => false,
        ], $override);
    }

    /** @return array<int,array<string,mixed>> */
    private function paymentReceivedRows(): array
    {
        return array_values(array_filter(
            $this->history->inserts,
            static fn (array $row) => ($row['template_name'] ?? null) === 'Payment Received'
        ));
    }

    // -------------------------------------------------------------------------
    // Criterio 1 — camino feliz
    // -------------------------------------------------------------------------

    public function testHappyPathRecordsSentEmailRow(): void
    {
        $this->service->sendPaymentConfirmationEmail($this->reservation());

        $rows = $this->paymentReceivedRows();
        $this->assertCount(1, $rows);

        $row = $rows[0];
        $this->assertSame('email', $row['event_type']);
        $this->assertSame('Sent', $row['status']);
        $this->assertSame('System', $row['sent_by']);
        $this->assertSame('client@example.com', $row['recipient_email']);
        $this->assertNull($row['template_id']);
        $this->assertSame('Your payment is confirmed', $row['email_subject']);
        $this->assertSame('<p>Thank you, your payment was received.</p>', $row['email_body']);
        $this->assertNotSame('', $row['email_subject']);
        $this->assertNotSame('', $row['email_body']);

        // El email realmente se intento enviar al cliente.
        $this->assertCount(1, $this->emailService->sent);
        $this->assertSame('client@example.com', $this->emailService->sent[0][0]);
    }

    public function testHappyPathSubjectFallsBackWhenRenderedSubjectEmpty(): void
    {
        $this->templateService->return = ['subject' => '   ', 'body' => '<p>ok</p>'];

        $this->service->sendPaymentConfirmationEmail($this->reservation());

        $row = $this->paymentReceivedRows()[0];
        $this->assertSame('Sent', $row['status']);
        $this->assertSame('Payment confirmation email', $row['email_subject']);
    }

    // -------------------------------------------------------------------------
    // Criterio 2 — envio que lanza -> fila Failed, sin propagar, motivo escapado
    // -------------------------------------------------------------------------

    public function testSendExceptionRecordsFailedRowAndDoesNotPropagate(): void
    {
        $this->emailService->throw = new \RuntimeException('brevo 500');

        // No debe propagar (criterio 6 / webhook de Stripe).
        $this->service->sendPaymentConfirmationEmail($this->reservation());

        $rows = $this->paymentReceivedRows();
        $this->assertCount(1, $rows);

        $row = $rows[0];
        $this->assertSame('email', $row['event_type']);
        $this->assertSame('Failed', $row['status']);
        $this->assertSame('client@example.com', $row['recipient_email']);

        // El body empieza con el bloque rojo de motivo y conserva el body original.
        $this->assertStringStartsWith('<div', $row['email_body']);
        $this->assertStringContainsString('Payment confirmation email failed to send.', $row['email_body']);
        $this->assertStringContainsString('#DC2626', $row['email_body']);
        $this->assertStringContainsString('Reason: RuntimeException: brevo 500', $row['email_body']);
        $this->assertStringContainsString('<p>Thank you, your payment was received.</p>', $row['email_body']);

        $this->assertLogged('error', 'Failed to send payment confirmation email: brevo 500');
    }

    public function testFailureReasonIsHtmlEscaped(): void
    {
        $this->emailService->throw = new \RuntimeException('<script>alert("x")</script> api_key: sk_live_ABC');

        $this->service->sendPaymentConfirmationEmail($this->reservation());

        $body = $this->paymentReceivedRows()[0]['email_body'];

        // XSS almacenado: el motivo se renderiza en el iframe del admin.
        $this->assertStringNotContainsString('<script>alert', $body);
        $this->assertStringContainsString('&lt;script&gt;', $body);
        // La clase de la excepcion siempre se guarda.
        $this->assertStringContainsString('RuntimeException', $body);
    }

    public function testFailureReasonMessageTruncatedTo255Chars(): void
    {
        $marker = str_repeat('A', 400);
        $this->emailService->throw = new \RuntimeException($marker);

        $this->service->sendPaymentConfirmationEmail($this->reservation());

        $body = $this->paymentReceivedRows()[0]['email_body'];

        // 255 chars del mensaje si; 256 no (mb_substr(..., 0, 255)).
        $this->assertStringContainsString(str_repeat('A', 255), $body);
        $this->assertStringNotContainsString(str_repeat('A', 256), $body);
    }

    public function testSanitizeThrowableReasonNeverEmitsRawFullMessage(): void
    {
        $reason = $this->invoke(
            'sanitizeThrowableReason',
            new \RuntimeException(str_repeat('Z', 1000))
        );

        $this->assertStringStartsWith('RuntimeException: ', $reason);
        // 'RuntimeException: ' (18) + 255 chars.
        $this->assertSame(18 + 255, strlen($reason));
    }

    public function testSanitizeThrowableReasonWithoutMessageIsJustClass(): void
    {
        $reason = $this->invoke('sanitizeThrowableReason', new \RuntimeException(''));

        $this->assertSame('RuntimeException', $reason);
    }

    // -------------------------------------------------------------------------
    // Criterio 3 — email vacio -> fila Failed explicita, sin salir en silencio
    // -------------------------------------------------------------------------

    public function testEmptyEmailRecordsFailedRowWithoutThrowing(): void
    {
        $this->service->sendPaymentConfirmationEmail($this->reservation(['email' => '']));

        $rows = $this->paymentReceivedRows();
        $this->assertCount(1, $rows);

        $row = $rows[0];
        $this->assertSame('email', $row['event_type']);
        $this->assertSame('Failed', $row['status']);
        $this->assertSame('', $row['recipient_email']);
        $this->assertStringContainsString('Reason: Customer email is empty', $row['email_body']);

        // Nunca se intento enviar nada.
        $this->assertSame([], $this->emailService->sent);
        // Criterio 4: el log de error existente se conserva.
        $this->assertLogged(
            'error',
            'Failed to send payment confirmation email: customer email is empty for reservation res-1'
        );
    }

    public function testWhitespaceOnlyEmailIsTreatedAsPresentButSendPathHandlesIt(): void
    {
        // El guard es recipient === '' (sin trim). Un email con solo espacios
        // NO entra por el guard: sigue al try/catch. Documentamos el
        // comportamiento observado: intenta enviar y registra segun resultado.
        $this->service->sendPaymentConfirmationEmail($this->reservation(['email' => '   ']));

        $rows = $this->paymentReceivedRows();
        $this->assertCount(1, $rows);
        $this->assertContains($rows[0]['status'], ['Sent', 'Failed']);
    }

    // -------------------------------------------------------------------------
    // Notas para el Tester — montos nulos no rompen
    // -------------------------------------------------------------------------

    public function testNullAmountsDoNotBreakHappyPath(): void
    {
        $this->service->sendPaymentConfirmationEmail($this->reservation([
            'total_amount'    => null,
            'gratuity_amount' => null,
            'discount_amount' => null,
        ]));

        $rows = $this->paymentReceivedRows();
        $this->assertCount(1, $rows);
        $this->assertSame('Sent', $rows[0]['status']);
    }

    public function testMissingAmountPropertiesDoNotBreakHappyPath(): void
    {
        $reservation = $this->reservation();
        unset($reservation->total_amount, $reservation->gratuity_amount);

        $this->service->sendPaymentConfirmationEmail($reservation);

        $this->assertSame('Sent', $this->paymentReceivedRows()[0]['status']);
    }

    // -------------------------------------------------------------------------
    // Criterio 6 — render que lanza -> Failed, camino de pago intacto
    // -------------------------------------------------------------------------

    public function testRenderExceptionRecordsFailedRowWithoutOriginalBody(): void
    {
        $this->templateService->throw = new \RuntimeException('template render boom');

        $this->service->sendPaymentConfirmationEmail($this->reservation());

        $rows = $this->paymentReceivedRows();
        $this->assertCount(1, $rows);

        $row = $rows[0];
        $this->assertSame('Failed', $row['status']);
        $this->assertSame('Payment confirmation email', $row['email_subject']);
        // $rendered nunca se poblo -> body = solo el bloque de motivo (no vacio).
        $this->assertStringStartsWith('<div', $row['email_body']);
        $this->assertStringContainsString('Reason: RuntimeException: template render boom', $row['email_body']);
        $this->assertSame([], $this->emailService->sent);
    }

    public function testThrowableWhileBuildingBodyIsCaughtAndRecordedFailed(): void
    {
        // trim() sobre un objeto lanza TypeError (Throwable): debe caer en el
        // catch (\Throwable) del cuerpo, registrar Failed y no propagar.
        $this->service->sendPaymentConfirmationEmail($this->reservation([
            'full_name' => new \stdClass(),
        ]));

        $rows = $this->paymentReceivedRows();
        $this->assertCount(1, $rows);
        $this->assertSame('Failed', $rows[0]['status']);
        $this->assertStringContainsString('Reason: TypeError', $rows[0]['email_body']);
        $this->assertSame([], $this->emailService->sent);
    }

    // -------------------------------------------------------------------------
    // Contrato: sendPaymentConfirmationEmail() NUNCA lanza
    // -------------------------------------------------------------------------

    public function testNeverThrowsWhenHistoryInsertFailsOnHappyPath(): void
    {
        $this->history->throwOnInsert = true;

        $this->service->sendPaymentConfirmationEmail($this->reservation());

        $this->assertSame(1, $this->history->insertAttempts);
        $this->assertLogged('error', 'Failed to record reservation email history: history insert exploded');
    }

    public function testNeverThrowsWhenSendAndHistoryInsertBothFail(): void
    {
        $this->emailService->throw = new \RuntimeException('brevo down');
        $this->history->throwOnInsert = true;

        // Ni el envio ni el historial deben poder romper el webhook de Stripe.
        $this->service->sendPaymentConfirmationEmail($this->reservation());

        $this->assertSame(1, $this->history->insertAttempts);
    }

    public function testNeverThrowsWhenEmailEmptyAndHistoryInsertFails(): void
    {
        $this->history->throwOnInsert = true;

        $this->service->sendPaymentConfirmationEmail($this->reservation(['email' => '']));

        $this->assertSame(1, $this->history->insertAttempts);
    }

    // -------------------------------------------------------------------------
    // Criterio 7 — documentacion de acciones externas de infra
    // -------------------------------------------------------------------------

    public function testMethodDocCommentPointsToExternalInfraActions(): void
    {
        $doc = (new \ReflectionMethod(ReservationService::class, 'sendPaymentConfirmationEmail'))
            ->getDocComment();

        $this->assertIsString($doc);
        $this->assertStringContainsString('infra', $doc);
        $this->assertStringContainsString('Brevo', $doc);
        $this->assertStringContainsString('webhook', $doc);
        $this->assertStringContainsString('Cristian', $doc);
    }

    // -------------------------------------------------------------------------
    // helpers puros
    // -------------------------------------------------------------------------

    public function testBuildEmailFailureBodyAppendsOriginalBodyWhenPresent(): void
    {
        $out = $this->invoke('buildEmailFailureBody', 'RuntimeException: x', '<p>orig</p>');

        $this->assertStringStartsWith('<div', $out);
        $this->assertStringEndsWith('<p>orig</p>', $out);
    }

    public function testBuildEmailFailureBodyEscapesReason(): void
    {
        $out = $this->invoke('buildEmailFailureBody', 'Evil <img src=x onerror=alert(1)>', '');

        $this->assertStringNotContainsString('<img src=x', $out);
        $this->assertStringContainsString('&lt;img', $out);
    }

    public function testPaymentConfirmationSubjectUsesRenderedSubjectWhenPresent(): void
    {
        $this->assertSame(
            'Real subject',
            $this->invoke('paymentConfirmationSubject', '  Real subject  ')
        );
    }

    public function testPaymentConfirmationSubjectFallsBackWhenEmpty(): void
    {
        $this->assertSame(
            'Payment confirmation email',
            $this->invoke('paymentConfirmationSubject', '   ')
        );
    }

    // -------------------------------------------------------------------------
    // F2 (hardening heredado de B3) — recordPaymentReceivedEvent no propaga
    // -------------------------------------------------------------------------

    public function testRecordPaymentReceivedEventDoesNotPropagateWhenInsertThrows(): void
    {
        $this->history->throwOnInsert = true;

        // No propaga: el insert lo protege el catch interno de recordEmailHistory().
        $this->invoke('recordPaymentReceivedEvent', $this->reservation(), 'pi_123');

        $this->assertSame(1, $this->history->insertAttempts);
        $this->assertLogged('error', 'Failed to record reservation email history: history insert exploded');
    }

    public function testRecordPaymentReceivedEventDoesNotPropagateWhenBodyBuildThrows(): void
    {
        // (string) sobre un objeto lanza \Error. Antes del F2 solo el insert
        // estaba dentro del try; este \Error escapaba y rompia
        // handlePaymentCompleted() (el webhook devolveria 500).
        $reservation = $this->reservation(['id' => new \stdClass()]);

        $this->invoke('recordPaymentReceivedEvent', $reservation, 'pi_123');

        $this->assertSame(0, $this->history->insertAttempts);
        $this->assertLogged(
            'error',
            'Failed to record payment received event: Object of class stdClass could not be converted to string'
        );
    }

    public function testHandlePaymentCompletedStillMarksPaidWhenEmailAndEventRecordingFail(): void
    {
        // Criterio 6: nada del historial/email puede alterar el resultado.
        $this->repo->reservation = $this->reservation(['email' => '']);
        $this->history->throwOnInsert = true;

        $result = $this->service->handlePaymentCompleted('res-1', 'pi_123');

        $this->assertTrue($result);
        $this->assertNotEmpty($this->repo->updates);
        $this->assertTrue($this->repo->reservation->is_paid);
    }
}
