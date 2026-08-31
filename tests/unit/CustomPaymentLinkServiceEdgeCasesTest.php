<?php

namespace Tests\Unit;

use App\Repositories\CustomPaymentLinkRepository;
use App\Repositories\ReservationRepository;
use App\Services\CustomPaymentLinkService;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * B5 — casos limite de la validacion del servicio de links de pago
 * (criterio de aceptacion 2 y "Notas para el Tester").
 *
 * Ejercita directamente los helpers privados de CustomPaymentLinkService por
 * Reflection: assertValidAmount / assertValidDescription / assertValidEmail /
 * assertValidReservationId / normalizeName / normalizeCurrency.
 *
 * @internal
 */
final class CustomPaymentLinkServiceEdgeCasesTest extends CIUnitTestCase
{
    private CustomPaymentLinkService $service;
    private object $reservationRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $repo = new class extends CustomPaymentLinkRepository {
            public function __construct()
            {
            }
        };

        $this->reservationRepo = new class extends ReservationRepository {
            /** @var array<string,object> */
            public array $existing = [];

            public function __construct()
            {
            }

            public function getById(string $id)
            {
                return $this->existing[$id] ?? null;
            }
        };

        $this->service = new CustomPaymentLinkService();
        $this->setProp('repo', $repo);
        $this->setProp('reservationRepository', $this->reservationRepo);
    }

    private function setProp(string $name, $value): void
    {
        $ref = new \ReflectionProperty(CustomPaymentLinkService::class, $name);
        $ref->setAccessible(true);
        $ref->setValue($this->service, $value);
    }

    private function invoke(string $method, ...$args)
    {
        $ref = new \ReflectionMethod(CustomPaymentLinkService::class, $method);
        $ref->setAccessible(true);

        return $ref->invoke($this->service, ...$args);
    }

    /** @return array{0:bool,1:?HTTPException} */
    private function rejects(string $method, $value): array
    {
        try {
            $this->invoke($method, $value);
        } catch (HTTPException $e) {
            return [true, $e];
        }

        return [false, null];
    }

    // -------------------------------------------------------------------------
    // Monto (criterio 2 + tope duro MAX_AMOUNT)
    // -------------------------------------------------------------------------

    public function testMaxAmountConstantIs10000(): void
    {
        $this->assertSame(10000.0, CustomPaymentLinkService::MAX_AMOUNT);
    }

    /** @dataProvider rejectedAmounts */
    public function testRejectedAmounts($amount): void
    {
        [$threw, $e] = $this->rejects('assertValidAmount', $amount);

        $this->assertTrue($threw, 'se esperaba rechazo para: ' . var_export($amount, true));
        $this->assertSame(400, $e->getCode());
    }

    public static function rejectedAmounts(): array
    {
        return [
            'zero'                 => [0],
            'zero string'          => ['0'],
            'negative'             => [-5],
            'rounds down to zero'  => [0.004],
            'just over the cap'    => [10000.01],
            'null'                 => [null],
            'true'                 => [true],
            'false'               => [false],
            'array'                => [[]],
            'empty string'         => [''],
            'non-numeric string'   => ['abc'],
            'whitespace string'    => ['   '],
        ];
    }

    /** @dataProvider acceptedAmounts */
    public function testAcceptedAmounts($amount, float $expected): void
    {
        $this->assertSame($expected, $this->invoke('assertValidAmount', $amount));
    }

    public static function acceptedAmounts(): array
    {
        return [
            'float'                        => [75.0, 75.0],
            'numeric string'               => ['50', 50.0],
            'exactly the cap'              => [10000, 10000.0],
            'three decimals rounded up'    => [7.128, 7.13],
            'three decimals rounded down'  => [7.123, 7.12],
            'rounds up to one cent'        => [0.006, 0.01],
            'scientific notation string'   => ['1e3', 1000.0],
        ];
    }

    public function testAmountRejectionMessageMentionsTheCap(): void
    {
        [, $e] = $this->rejects('assertValidAmount', 20000);
        $this->assertStringContainsString('10,000.00', $e->getMessage());
    }

    // -------------------------------------------------------------------------
    // Descripcion
    // -------------------------------------------------------------------------

    public function testDescriptionExactly255CharsAccepted(): void
    {
        $desc = str_repeat('a', 255);
        $this->assertSame($desc, $this->invoke('assertValidDescription', $desc));
    }

    public function testDescription256CharsRejected(): void
    {
        [$threw, $e] = $this->rejects('assertValidDescription', str_repeat('a', 256));
        $this->assertTrue($threw);
        $this->assertSame(400, $e->getCode());
    }

    public function testDescriptionTrimmed(): void
    {
        $this->assertSame('Late fee', $this->invoke('assertValidDescription', '  Late fee  '));
    }

    public function testDescriptionHtmlKeptVerbatimAtValidationLayer(): void
    {
        // El escapado ocurre en el render del email, no en la validacion.
        $this->assertSame('<b>Late</b> fee', $this->invoke('assertValidDescription', '<b>Late</b> fee'));
    }

    public function testDescriptionWithEmojisAccepted(): void
    {
        $this->assertSame('Fiesta 🎉🎂', $this->invoke('assertValidDescription', 'Fiesta 🎉🎂'));
    }

    /** @dataProvider rejectedDescriptions */
    public function testRejectedDescriptions($value): void
    {
        [$threw, $e] = $this->rejects('assertValidDescription', $value);
        $this->assertTrue($threw);
        $this->assertSame(400, $e->getCode());
    }

    public static function rejectedDescriptions(): array
    {
        return [
            'empty'          => [''],
            'only spaces'    => ['     '],
            'integer'        => [123],
            'null'           => [null],
            'array'          => [['x']],
            'boolean'        => [true],
        ];
    }

    // -------------------------------------------------------------------------
    // Email
    // -------------------------------------------------------------------------

    public function testValidEmailPassesThrough(): void
    {
        $this->assertSame('client@example.com', $this->invoke('assertValidEmail', 'client@example.com'));
    }

    public function testEmailIsLowercasedAndTrimmed(): void
    {
        $this->assertSame('client@example.com', $this->invoke('assertValidEmail', '  Client@Example.COM  '));
    }

    /** @dataProvider rejectedEmails */
    public function testRejectedEmails($value): void
    {
        [$threw, $e] = $this->rejects('assertValidEmail', $value);
        $this->assertTrue($threw);
        $this->assertSame(400, $e->getCode());
    }

    public static function rejectedEmails(): array
    {
        return [
            'plain word'     => ['not-an-email'],
            'missing tld'    => ['a@b'],
            'empty'          => [''],
            'integer'        => [123],
            'null'           => [null],
            'array'          => [[]],
        ];
    }

    // -------------------------------------------------------------------------
    // reservation_id
    // -------------------------------------------------------------------------

    /** @dataProvider emptyReservationIds */
    public function testEmptyReservationIdBecomesNull($value): void
    {
        $this->assertNull($this->invoke('assertValidReservationId', $value));
    }

    public static function emptyReservationIds(): array
    {
        return [
            'null'  => [null],
            'empty' => [''],
            'false' => [false],
        ];
    }

    /** @dataProvider invalidReservationRefs */
    public function testNonStringReservationIdRejected($value): void
    {
        [$threw, $e] = $this->rejects('assertValidReservationId', $value);
        $this->assertTrue($threw);
        $this->assertSame(400, $e->getCode());
        $this->assertStringContainsString('Invalid reservation reference', $e->getMessage());
    }

    public static function invalidReservationRefs(): array
    {
        return [
            'integer' => [123],
            'true'    => [true],
            'array'   => [['res-1']],
        ];
    }

    public function testUnknownReservationIdRejected(): void
    {
        [$threw, $e] = $this->rejects('assertValidReservationId', 'does-not-exist');
        $this->assertTrue($threw);
        $this->assertSame(400, $e->getCode());
        $this->assertStringContainsString('does not exist', $e->getMessage());
    }

    public function testExistingReservationIdAccepted(): void
    {
        $this->reservationRepo->existing['res-1'] = (object) ['id' => 'res-1'];

        $this->assertSame('res-1', $this->invoke('assertValidReservationId', 'res-1'));
    }

    // -------------------------------------------------------------------------
    // normalizeName
    // -------------------------------------------------------------------------

    /** @dataProvider blankNames */
    public function testBlankNamesBecomeNull($value): void
    {
        $this->assertNull($this->invoke('normalizeName', $value));
    }

    public static function blankNames(): array
    {
        return [
            'null'        => [null],
            'integer'     => [123],
            'array'       => [[]],
            'only spaces' => ['   '],
        ];
    }

    public function testNameTrimmed(): void
    {
        $this->assertSame('Jane Doe', $this->invoke('normalizeName', '  Jane Doe  '));
    }

    public function testNameTruncatedTo150Chars(): void
    {
        $this->assertSame(150, mb_strlen($this->invoke('normalizeName', str_repeat('n', 300))));
    }

    // -------------------------------------------------------------------------
    // normalizeCurrency
    // -------------------------------------------------------------------------

    /** @dataProvider defaultCurrencies */
    public function testCurrencyDefaultsToUsd($value): void
    {
        $this->assertSame('usd', $this->invoke('normalizeCurrency', $value));
    }

    public static function defaultCurrencies(): array
    {
        return [
            'null'        => [null],
            'empty'       => [''],
            'only spaces' => ['   '],
            'integer'     => [123],
        ];
    }

    public function testCurrencyLowercasedAndTrimmed(): void
    {
        $this->assertSame('eur', $this->invoke('normalizeCurrency', '  EUR '));
    }

    public function testCurrencyTruncatedToTenChars(): void
    {
        $this->assertSame(10, strlen($this->invoke('normalizeCurrency', str_repeat('x', 25))));
    }
}
