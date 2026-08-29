<?php

namespace Tests\Unit;

use App\Services\ReservationService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * B2 — "Agregar opcion de CC en los emails desde la plataforma".
 *
 * Cubre la parte de ReservationService:
 *  - ccEmailsForHistory(): serializacion del CC efectivo para
 *    reservation_email_history.cc_emails (null si vacio, truncado a 500).
 *  - La firma de sendTemplateEmail() acepta el parametro $cc.
 *  - sendTemplateEmail() propaga el CC al envio y al historial (chequeo a nivel
 *    de fuente: el metodo NO es unit-testable de punta a punta porque
 *    recordEmailHistory() instancia el modelo y toca la BD, prohibido en esta suite).
 *
 * @internal
 */
final class ReservationServiceCcHistoryTest extends CIUnitTestCase
{
    private ReservationService $service;
    private \ReflectionMethod $ccForHistory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ReservationService();

        $this->ccForHistory = new \ReflectionMethod(ReservationService::class, 'ccEmailsForHistory');
        $this->ccForHistory->setAccessible(true);
    }

    /** @param string[] $cc */
    private function ccString(array $cc): ?string
    {
        return $this->ccForHistory->invoke($this->service, $cc);
    }

    // -------------------------------------------------------------------------
    // ccEmailsForHistory()
    // -------------------------------------------------------------------------

    public function testEmptyCcSerializesToNull(): void
    {
        $this->assertNull($this->ccString([]));
    }

    public function testCcListJoinsWithCommaSpace(): void
    {
        $this->assertSame(
            'a@x.com, b@x.com, c@x.com',
            $this->ccString(['a@x.com', 'b@x.com', 'c@x.com'])
        );
    }

    public function testSingleCcSerializesWithoutSeparator(): void
    {
        $this->assertSame('only@x.com', $this->ccString(['only@x.com']));
    }

    public function testLongCcListIsTruncatedTo500Characters(): void
    {
        $many = [];
        for ($i = 0; $i < 60; $i++) {
            $many[] = 'recipient-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT) . '@example.com';
        }

        $result = $this->ccString($many);

        $this->assertNotNull($result);
        $this->assertSame(500, strlen($result));
        $this->assertStringStartsWith('recipient-000@example.com, recipient-001@example.com', $result);
    }

    public function testTruncationBoundaryExactly500IsUnchanged(): void
    {
        // Una sola direccion de exactamente 500 chars: substr(...,0,500) la deja igual.
        $addr = str_repeat('a', 488) . '@example.com'; // 488 + 12 = 500
        $this->assertSame(500, strlen($addr));

        $this->assertSame($addr, $this->ccString([$addr]));
    }

    // -------------------------------------------------------------------------
    // Firma y propagacion en sendTemplateEmail()
    // -------------------------------------------------------------------------

    public function testSendTemplateEmailSignatureAcceptsOptionalCcArray(): void
    {
        $method = new \ReflectionMethod(ReservationService::class, 'sendTemplateEmail');
        $params = $method->getParameters();

        $cc = null;
        foreach ($params as $p) {
            if ($p->getName() === 'cc') {
                $cc = $p;
            }
        }

        $this->assertNotNull($cc, 'sendTemplateEmail debe declarar el parametro $cc');
        $this->assertTrue($cc->isOptional());
        $this->assertSame('array', (string) $cc->getType());
        $this->assertSame([], $cc->getDefaultValue());
    }

    public function testSendTemplateEmailSourcePropagatesCcToSendAndHistory(): void
    {
        $source = file_get_contents(APPPATH . 'Services/ReservationService.php');

        $start  = strpos($source, 'public function sendTemplateEmail(');
        $this->assertNotFalse($start);
        $end    = strpos($source, 'public function getEmailHistory(', $start);
        $body   = substr($source, $start, $end - $start);

        // El CC efectivo se resuelve con el servicio de email...
        $this->assertStringContainsString('resolveCc(', $body);
        // ...se pasa al envio real...
        $this->assertMatchesRegularExpression('/sendEmail\([^;]*\$cc[^;]*\)/s', $body);
        // ...y se persiste en cc_emails via el helper de truncado.
        $this->assertStringContainsString("'cc_emails'", $body);
        $this->assertStringContainsString('ccEmailsForHistory(', $body);
    }
}
