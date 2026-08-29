<?php

namespace Tests\Unit;

use App\Services\BrevoEmailService;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * B2 — "Agregar opcion de CC en los emails desde la plataforma".
 *
 * Cubre la logica de CC de BrevoEmailService sin tocar la base de datos ni la red:
 *  - normalizeRecipients(): normalizacion pura de una lista de destinatarios.
 *  - resolveCc(): merge del CC por defecto global con el CC por envio.
 *  - assertValidClientCc(): validacion estricta del CC explicito del cliente (400).
 *  - parseConfiguredCc(): via constructor + getenv('email.defaultCc').
 *  - sendEmail(): forma real del payload (SendSmtpEmail) capturado en un doble de
 *    $apiInstance inyectado por ReflectionProperty.
 *
 * El CC por defecto se controla con putenv('email.defaultCc=...') ANTES de
 * construir el servicio (parseConfiguredCc es privado y solo se lee en __construct).
 *
 * @internal
 */
final class BrevoEmailServiceCcTest extends CIUnitTestCase
{
    /** @var string|false */
    private $prevApiKey;

    /** @var string|false */
    private $prevDefaultCc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prevApiKey    = getenv('brevo.apiKey');
        $this->prevDefaultCc = getenv('email.defaultCc');

        putenv('brevo.apiKey=test-key');
        putenv('email.defaultCc');
    }

    protected function tearDown(): void
    {
        putenv($this->prevApiKey === false ? 'brevo.apiKey' : 'brevo.apiKey=' . $this->prevApiKey);
        putenv($this->prevDefaultCc === false ? 'email.defaultCc' : 'email.defaultCc=' . $this->prevDefaultCc);

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a BrevoEmailService whose $apiInstance is replaced by a capturing
     * double. The optional $defaultCc is written to the environment first so the
     * constructor picks it up.
     *
     * @return array{0:BrevoEmailService,1:object} [service, apiDouble]
     */
    private function makeService(?string $defaultCc = null): array
    {
        if ($defaultCc === null) {
            putenv('email.defaultCc');
        } else {
            putenv('email.defaultCc=' . $defaultCc);
        }

        $service = new BrevoEmailService();

        $apiDouble = new class extends TransactionalEmailsApi {
            /** @var SendSmtpEmail|null */
            public $captured;

            public int $calls = 0;

            public function sendTransacEmail($sendSmtpEmail)
            {
                $this->captured = $sendSmtpEmail;
                $this->calls++;

                return (object) ['messageId' => '<stub@brevo>'];
            }
        };

        $ref = new \ReflectionProperty(BrevoEmailService::class, 'apiInstance');
        $ref->setAccessible(true);
        $ref->setValue($service, $apiDouble);

        return [$service, $apiDouble];
    }

    private function assertHttpException(int $expectedCode, callable $fn): void
    {
        try {
            $fn();
            $this->fail('Expected HTTPException with code ' . $expectedCode);
        } catch (HTTPException $e) {
            $this->assertSame($expectedCode, $e->getCode());
        }
    }

    // -------------------------------------------------------------------------
    // normalizeRecipients()
    // -------------------------------------------------------------------------

    public function testEmptyCcNormalizesToEmptyArray(): void
    {
        [$service] = $this->makeService();

        $this->assertSame([], $service->normalizeRecipients([], 'client@example.com'));
    }

    public function testRecipientEqualToPrimaryIsExcluded(): void
    {
        [$service] = $this->makeService();

        $this->assertSame(
            ['other@example.com'],
            $service->normalizeRecipients(['client@example.com', 'other@example.com'], 'client@example.com')
        );
    }

    public function testPrimaryExclusionIsCaseInsensitive(): void
    {
        [$service] = $this->makeService();

        $this->assertSame(
            [],
            $service->normalizeRecipients(['CLIENT@Example.com'], 'client@example.com')
        );
    }

    public function testCaseInsensitiveDuplicatesCollapseToOne(): void
    {
        [$service] = $this->makeService();

        $this->assertSame(
            ['a@x.com'],
            $service->normalizeRecipients(['A@X.com', 'a@x.com', 'a@X.COM'], 'to@x.com')
        );
    }

    public function testAddressesAreTrimmedAndLowercased(): void
    {
        [$service] = $this->makeService();

        $this->assertSame(
            ['spaced@x.com'],
            $service->normalizeRecipients(['   Spaced@X.com   '], 'to@x.com')
        );
    }

    public function testNonStringEntriesAreDropped(): void
    {
        [$service] = $this->makeService();

        $this->assertSame(
            ['keep@x.com'],
            $service->normalizeRecipients([123, ['nested@x.com'], true, null, 4.5, 'keep@x.com'], 'to@x.com')
        );
    }

    public function testEmbeddedLineBreakAddressesAreRejectedAndLogged(): void
    {
        [$service] = $this->makeService();

        $result = $service->normalizeRecipients(
            ["evil@x.com\r\nbcc: victim@x.com", "wrap@x.com\ninjected", 'clean@x.com'],
            'to@x.com'
        );

        $this->assertSame(['clean@x.com'], $result);
        $this->assertLogged('warning', 'BrevoEmailService: rejected CC address with line breaks');
    }

    public function testInvalidEmailsAreDiscardedAndLogged(): void
    {
        [$service] = $this->makeService();

        $result = $service->normalizeRecipients(['not-an-email', 'also bad @x', 'ok@x.com'], 'to@x.com');

        $this->assertSame(['ok@x.com'], $result);
        $this->assertLogged('warning', 'BrevoEmailService: discarded invalid CC address "not-an-email"');
    }

    public function testTrailingNewlineIsTrimmedAwayAndAddressAccepted(): void
    {
        // Documenta el comportamiento actual: trim() elimina \r\n al borde ANTES
        // de la deteccion de saltos de linea, por lo que una nueva linea final
        // (inofensiva) no bloquea la direccion. Solo los saltos EMBEBIDOS se rechazan.
        [$service] = $this->makeService();

        $this->assertSame(
            ['trail@x.com'],
            $service->normalizeRecipients(["trail@x.com\n"], 'to@x.com')
        );
    }

    public function testListIsCappedAtMaxCc(): void
    {
        [$service] = $this->makeService();

        $input = [];
        for ($i = 1; $i <= 15; $i++) {
            $input[] = "cc{$i}@x.com";
        }

        $result = $service->normalizeRecipients($input, 'to@x.com');

        $this->assertCount(BrevoEmailService::MAX_CC, $result);
        $this->assertSame(10, BrevoEmailService::MAX_CC);
        $this->assertSame('cc1@x.com', $result[0]);
        $this->assertSame('cc10@x.com', $result[9]);
    }

    // -------------------------------------------------------------------------
    // resolveCc() + CC por defecto (parseConfiguredCc via constructor)
    // -------------------------------------------------------------------------

    public function testResolveCcWithoutDefaultCcEqualsNormalizedPerSendList(): void
    {
        [$service] = $this->makeService();

        $this->assertSame(
            ['a@x.com', 'b@x.com'],
            $service->resolveCc(['A@X.com', 'b@x.com', 'a@x.com'], 'to@x.com')
        );
    }

    public function testDefaultCcParsesCommaSemicolonDoubleSeparatorsAndSpaces(): void
    {
        [$service] = $this->makeService(' first@x.com ,, second@x.com ;;; third@x.com , ');

        $this->assertSame(
            ['first@x.com', 'second@x.com', 'third@x.com'],
            $service->resolveCc([], 'to@x.com')
        );
    }

    public function testDefaultCcAndPerSendCombineWithoutDuplicates(): void
    {
        [$service] = $this->makeService('default@x.com, shared@x.com');

        $this->assertSame(
            ['default@x.com', 'shared@x.com', 'extra@x.com'],
            $service->resolveCc(['Shared@X.com', 'extra@x.com'], 'to@x.com')
        );
    }

    public function testDefaultCcExcludesPrimaryRecipient(): void
    {
        [$service] = $this->makeService('to@x.com, real-cc@x.com');

        $this->assertSame(['real-cc@x.com'], $service->resolveCc([], 'to@x.com'));
    }

    public function testEmptyDefaultCcLeavesResolveCcEmptyWhenNoPerSend(): void
    {
        [$service] = $this->makeService('');

        $this->assertSame([], $service->resolveCc([], 'to@x.com'));
    }

    public function testDefaultCcIsReadOnceAtConstructionNotPerCall(): void
    {
        [$service] = $this->makeService('first@x.com');

        putenv('email.defaultCc=changed@x.com');

        $this->assertSame(['first@x.com'], $service->resolveCc([], 'to@x.com'));
    }

    public function testMisconfiguredDefaultCcEntryIsDiscardedNotThrown(): void
    {
        [$service] = $this->makeService('garbage-not-email, good@x.com');

        $this->assertSame(['good@x.com'], $service->resolveCc([], 'to@x.com'));
        $this->assertLogged('warning', 'BrevoEmailService: discarded invalid CC address "garbage-not-email"');
    }

    // -------------------------------------------------------------------------
    // assertValidClientCc() — path del cliente, errores 400
    // -------------------------------------------------------------------------

    public function testAssertValidClientCcEmptyInputsReturnEmptyArray(): void
    {
        $this->assertSame([], BrevoEmailService::assertValidClientCc([]));
        $this->assertSame([], BrevoEmailService::assertValidClientCc(null));
        $this->assertSame([], BrevoEmailService::assertValidClientCc(''));
    }

    public function testAssertValidClientCcNonArrayThrows400(): void
    {
        $this->assertHttpException(400, static fn () => BrevoEmailService::assertValidClientCc('a@x.com'));
        $this->assertHttpException(400, static fn () => BrevoEmailService::assertValidClientCc(42));
        $this->assertHttpException(400, static fn () => BrevoEmailService::assertValidClientCc(true));
    }

    public function testAssertValidClientCcNonStringEntryThrows400(): void
    {
        $this->assertHttpException(400, static fn () => BrevoEmailService::assertValidClientCc(['ok@x.com', 5]));
        $this->assertHttpException(400, static fn () => BrevoEmailService::assertValidClientCc(['ok@x.com', ['nested@x.com']]));
    }

    public function testAssertValidClientCcLineBreakEntryThrows400(): void
    {
        $this->assertHttpException(
            400,
            static fn () => BrevoEmailService::assertValidClientCc(["ok@x.com", "evil@x.com\r\nbcc: v@x.com"])
        );
    }

    public function testAssertValidClientCcInvalidEmailThrows400(): void
    {
        $this->assertHttpException(400, static fn () => BrevoEmailService::assertValidClientCc(['not-an-email']));
    }

    public function testAssertValidClientCcMoreThanTenThrows400(): void
    {
        $eleven = [];
        for ($i = 1; $i <= 11; $i++) {
            $eleven[] = "cc{$i}@x.com";
        }

        $this->assertHttpException(400, static fn () => BrevoEmailService::assertValidClientCc($eleven));
    }

    public function testAssertValidClientCcExactlyTenIsAccepted(): void
    {
        $ten = [];
        for ($i = 1; $i <= 10; $i++) {
            $ten[] = "cc{$i}@x.com";
        }

        $this->assertSame($ten, BrevoEmailService::assertValidClientCc($ten));
    }

    public function testAssertValidClientCcTrimsAndSkipsBlankEntries(): void
    {
        $this->assertSame(
            ['valid@x.com', 'other@y.com'],
            BrevoEmailService::assertValidClientCc(['  valid@x.com  ', '   ', 'other@y.com'])
        );
    }

    // -------------------------------------------------------------------------
    // sendEmail() — forma real del payload
    // -------------------------------------------------------------------------

    public function testSendEmailWithoutCcOmitsCcAndBccFromPayload(): void
    {
        [$service, $api] = $this->makeService();

        $service->sendEmail('client@example.com', 'Hello', '<p>Body</p>');

        /** @var SendSmtpEmail $sent */
        $sent = $api->captured;
        $this->assertInstanceOf(SendSmtpEmail::class, $sent);
        $this->assertNull($sent->getCc());
        $this->assertNull($sent->getBcc());
        $this->assertFalse($sent->offsetExists('cc'), 'la clave cc no debe estar en el payload');
        $this->assertFalse($sent->offsetExists('bcc'), 'la clave bcc no debe estar en el payload');
    }

    public function testSendEmailWithoutCcKeepsAllOtherFieldsUnchanged(): void
    {
        // Criterio 9: sin cc, el payload es identico al comportamiento previo.
        [$service, $api] = $this->makeService();

        $service->sendEmail('client@example.com', 'My Subject', '<h1>Rich HTML</h1>');

        /** @var SendSmtpEmail $sent */
        $sent = $api->captured;
        $this->assertSame('My Subject', $sent->getSubject());
        $this->assertSame('<h1>Rich HTML</h1>', $sent->getHtmlContent());
        $this->assertSame([['email' => 'client@example.com']], $sent->getTo());
        $this->assertSame(
            ['name' => getenv('brevo.fromName'), 'email' => getenv('brevo.fromEmail')],
            $sent->getSender()
        );
        $this->assertSame(1, $api->calls);
    }

    public function testSendEmailAddsNormalizedCcArrayToPayload(): void
    {
        [$service, $api] = $this->makeService();

        $service->sendEmail(
            'client@example.com',
            'Hello',
            '<p>Body</p>',
            ['CC-One@X.com', 'cc-one@x.com', 'client@example.com', 'cc-two@x.com']
        );

        /** @var SendSmtpEmail $sent */
        $sent = $api->captured;
        $this->assertSame(
            [['email' => 'cc-one@x.com'], ['email' => 'cc-two@x.com']],
            $sent->getCc()
        );
        $this->assertTrue($sent->offsetExists('cc'));
    }

    public function testSendEmailWithDefaultCcOnlyStillPopulatesCc(): void
    {
        [$service, $api] = $this->makeService('audit@x.com');

        $service->sendEmail('client@example.com', 'Hello', '<p>Body</p>');

        /** @var SendSmtpEmail $sent */
        $sent = $api->captured;
        $this->assertSame([['email' => 'audit@x.com']], $sent->getCc());
    }

    public function testSendEmailMergesDefaultCcWithPerSendCc(): void
    {
        [$service, $api] = $this->makeService('audit@x.com');

        $service->sendEmail('client@example.com', 'Hello', '<p>Body</p>', ['manager@x.com', 'audit@x.com']);

        /** @var SendSmtpEmail $sent */
        $sent = $api->captured;
        $this->assertSame(
            [['email' => 'audit@x.com'], ['email' => 'manager@x.com']],
            $sent->getCc()
        );
    }

    public function testSendEmailReturnsApiResponse(): void
    {
        [$service, $api] = $this->makeService();

        $response = $service->sendEmail('client@example.com', 'Hello', '<p>Body</p>');

        $this->assertSame('<stub@brevo>', $response->messageId);
        $this->assertSame(1, $api->calls);
    }
}
