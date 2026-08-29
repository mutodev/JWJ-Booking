<?php

namespace Tests\Unit;

use App\Services\ReservationService;
use CodeIgniter\Database\Config as DatabaseConfig;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\Test\CIUnitTestCase;
use Faker\Factory as FakerFactory;

/**
 * A1 — "Número de niños y edades del segundo formulario" (casos límite QA).
 *
 * Complementa ReservationServiceChildrenCountTest cubriendo los casos límite
 * exigidos en las "Notas para el Tester" del PLAN_MAESTRO:
 *   - Clamp del número exacto: 0, -3, 10, 11, 30, 31, 999, valores fuera de rango.
 *   - Entradas no enteras: float 7.9, string "7.9", string numérico "7", espacios.
 *   - Type juggling / seguridad: "1e5", float 1e5, "10abc" no deben escapar del rango.
 *   - Rango desconocido ("99 kids") => $bounds === null: no debe romper.
 *   - children_age_range nunca recibe el rango de CANTIDAD.
 *
 * Misma estrategia sin base de datos que ReservationServiceChildrenCountTest:
 * dobles anónimos vía Reflection y neutralización de \Config\Database::connect().
 *
 * @internal
 */
final class ReservationServiceChildrenCountEdgeCasesTest extends CIUnitTestCase
{
    private ReservationService $service;
    private object $repoMock;
    private \Faker\Generator $faker;

    /** @var array<string,mixed>|null */
    private $originalDbInstances;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = FakerFactory::create();

        $this->repoMock = new class {
            public array $lastCreated = [];

            public function create(array $data)
            {
                $this->lastCreated = $data;

                return (object) array_merge(['id' => 'res-uuid', 'email' => null], $data);
            }

            public function getById($id)
            {
                return null;
            }
        };

        $customerRepoMock = new class {
            public function getByEmail($email)
            {
                return null;
            }

            public function create(array $data)
            {
                return 'cust-uuid';
            }

            public function getById($id)
            {
                return null;
            }
        };

        $addonRepoMock = new class {
            public function create(array $data)
            {
                return (object) $data;
            }

            public function getDetailedByReservation($id)
            {
                return [];
            }
        };

        $promoRepoMock = new class {
            public function findByCode($code)
            {
                return null;
            }

            public function incrementUsage($id)
            {
                return true;
            }
        };

        $emailTemplateMock = new class {
            public function render($slug, array $vars = [])
            {
                return ['subject' => 'stub', 'body' => 'stub'];
            }
        };

        $emailServiceMock = new class {
            public function sendEmail($to, $subject, $body)
            {
                return true;
            }
        };

        $this->service = new ReservationService();
        $this->setProp('repository', $this->repoMock);
        $this->setProp('customerRepository', $customerRepoMock);
        $this->setProp('reservationAddonRepository', $addonRepoMock);
        $this->setProp('promoCodeRepository', $promoRepoMock);
        $this->setProp('emailTemplateService', $emailTemplateMock);
        $this->setProp('emailService', $emailServiceMock);
        $this->setProp('brevoContactService', null);

        $dbMock = new class {
            public function transStart(): void {}

            public function transComplete(): void {}

            public function transStatus(): bool
            {
                return true;
            }

            public function transRollback(): bool
            {
                return true;
            }
        };

        $instances = new \ReflectionProperty(DatabaseConfig::class, 'instances');
        $instances->setAccessible(true);
        $this->originalDbInstances = $instances->getValue();
        $instances->setValue(null, array_merge(
            is_array($this->originalDbInstances) ? $this->originalDbInstances : [],
            ['tests' => $dbMock, 'default' => $dbMock]
        ));
    }

    protected function tearDown(): void
    {
        $instances = new \ReflectionProperty(DatabaseConfig::class, 'instances');
        $instances->setAccessible(true);
        $instances->setValue(null, $this->originalDbInstances ?? []);

        parent::tearDown();
    }

    private function setProp(string $name, $value): void
    {
        $ref = new \ReflectionProperty(ReservationService::class, $name);
        $ref->setAccessible(true);
        $ref->setValue($this->service, $value);
    }

    /**
     * @return array<string,mixed>
     */
    private function baseFormData(): array
    {
        $futureDate = (new \DateTime('+30 days'))->format('Y-m-d');

        return [
            'customer' => [
                'email'         => $this->faker->safeEmail(),
                'firstName'     => 'Jamie',
                'lastName'      => 'Client',
                'phone'         => '5551234567',
                'eventType'     => 'Birthday Party',
                'childrenRange' => '1-10 kids',
            ],
            'zipcode' => [
                'id'        => 'zip-1',
                'zone_type' => 'standard',
            ],
            'service' => [
                'id'               => 'sp-1',
                'amount'           => 200.0,
                'performers_count' => 1,
                'max_kids_included' => 40,
                'extra_child_fee'  => 10.0,
                'duration_hours'   => 2.0,
            ],
            'addons'      => [],
            'information' => [
                'name'                 => 'Jamie',
                'lastName'             => 'Client',
                'fullAddress'          => '123 Main St',
                'eventDate'            => $futureDate,
                'startTime'            => '14:00',
                'entertainmentStartTime' => '14:30',
                'ageRange'             => null,
                'childAge'            => 0,
                'birthdayChildName'   => null,
                'songRequests'        => null,
                'happyBirthdayRequest' => 'no',
                'instructions'        => null,
                'customerNotes'       => null,
            ],
        ];
    }

    /**
     * @param array<string,mixed> $override
     *
     * @return array<string,mixed>
     */
    private function runCreateFromForm(array $override): array
    {
        $data = $this->deepMerge($this->baseFormData(), $override);
        $this->service->createFromForm($data);

        return $this->repoMock->lastCreated;
    }

    private function deepMerge(array $base, array $over): array
    {
        foreach ($over as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->deepMerge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    private function withExact(string $range, $exact): array
    {
        return $this->runCreateFromForm([
            'customer' => ['childrenRange' => $range, 'exactChildrenCount' => $exact],
        ]);
    }

    // -------------------------------------------------------------------------
    // Clamp: valores fuera del rango elegido se recortan (criterio 2)
    // -------------------------------------------------------------------------

    /**
     * @dataProvider clampProvider
     */
    public function testExactCountIsClampedToChosenRange(string $range, $exact, int $expected): void
    {
        $this->assertSame($expected, $this->withExact($range, $exact)['children_count']);
    }

    public static function clampProvider(): array
    {
        return [
            // 1-10 kids
            '1-10 / 0 -> 1'          => ['1-10 kids', 0, 1],
            '1-10 / -3 -> 1'         => ['1-10 kids', -3, 1],
            '1-10 / -999 -> 1'       => ['1-10 kids', -999, 1],
            '1-10 / 1 -> 1'          => ['1-10 kids', 1, 1],
            '1-10 / 10 -> 10'        => ['1-10 kids', 10, 10],
            '1-10 / 11 -> 10'        => ['1-10 kids', 11, 10],
            '1-10 / 30 -> 10'        => ['1-10 kids', 30, 10],
            '1-10 / 31 -> 10'        => ['1-10 kids', 31, 10],
            '1-10 / 50 -> 10'        => ['1-10 kids', 50, 10],
            '1-10 / 999 -> 10'       => ['1-10 kids', 999, 10],
            '1-10 / 999999 -> 10'    => ['1-10 kids', 999999, 10],
            // 11-30 kids
            '11-30 / 10 -> 11'       => ['11-30 kids', 10, 11],
            '11-30 / 0 -> 11'        => ['11-30 kids', 0, 11],
            '11-30 / -3 -> 11'       => ['11-30 kids', -3, 11],
            '11-30 / 11 -> 11'       => ['11-30 kids', 11, 11],
            '11-30 / 30 -> 30'       => ['11-30 kids', 30, 30],
            '11-30 / 31 -> 30'       => ['11-30 kids', 31, 30],
            '11-30 / 999999 -> 30'   => ['11-30 kids', 999999, 30],
            // 31+ kids  (bounds 31..999)
            '31+ / 30 -> 31'         => ['31+ kids', 30, 31],
            '31+ / 0 -> 31'          => ['31+ kids', 0, 31],
            '31+ / 31 -> 31'         => ['31+ kids', 31, 31],
            '31+ / 500 -> 500'       => ['31+ kids', 500, 500],
            '31+ / 999 -> 999'       => ['31+ kids', 999, 999],
            '31+ / 1000 -> 999'      => ['31+ kids', 1000, 999],
            '31+ / 999999 -> 999'    => ['31+ kids', 999999, 999],
        ];
    }

    // -------------------------------------------------------------------------
    // Entradas no enteras: string numérico, float, espacios
    // -------------------------------------------------------------------------

    /**
     * @dataProvider numericCoercionProvider
     */
    public function testNumericCoercionBeforeClamp(string $range, $exact, int $expected): void
    {
        $this->assertSame($expected, $this->withExact($range, $exact)['children_count']);
    }

    public static function numericCoercionProvider(): array
    {
        return [
            'string "7"'         => ['1-10 kids', '7', 7],
            'string "10"'        => ['1-10 kids', '10', 10],
            'float 7.9 -> 7'     => ['1-10 kids', 7.9, 7],
            'float 10.9 -> 10'   => ['1-10 kids', 10.9, 10],
            'string "7.9" -> 7'  => ['1-10 kids', '7.9', 7],
            'string " 7 " -> 7'  => ['1-10 kids', ' 7 ', 7],
            'float 29.9 (11-30)' => ['11-30 kids', 29.9, 29],
            'string "25" (11-30)'=> ['11-30 kids', '25', 25],
        ];
    }

    // -------------------------------------------------------------------------
    // Seguridad / type juggling: no debe escapar del rango (criterio 2, riesgos)
    // -------------------------------------------------------------------------

    public function testScientificNotationStringIsClampedNotEscaped(): void
    {
        // is_numeric('1e5') === true, intval('1e5') === 100000 -> clamp
        $this->assertSame(10, $this->withExact('1-10 kids', '1e5')['children_count']);
        $this->assertSame(30, $this->withExact('11-30 kids', '1e5')['children_count']);
        $this->assertSame(999, $this->withExact('31+ kids', '1e5')['children_count']);
    }

    public function testScientificNotationFloatIsClampedNotEscaped(): void
    {
        $this->assertSame(10, $this->withExact('1-10 kids', 1e5)['children_count']);
        $this->assertSame(999, $this->withExact('31+ kids', 1e5)['children_count']);
    }

    public function testTrailingGarbageStringIsRejectedAsNonNumericAndFallsToMidpoint(): void
    {
        // is_numeric('10abc') === false -> NO se usa intval('10abc') (que sería 10),
        // cae al punto medio del rango. Nunca escapa del rango.
        $saved = $this->withExact('1-10 kids', '10abc');
        $this->assertSame(5, $saved['children_count']);

        $saved = $this->withExact('11-30 kids', '99999abc');
        $this->assertSame(20, $saved['children_count']);
    }

    public function testHexStringIsRejectedAsNonNumeric(): void
    {
        $saved = $this->withExact('1-10 kids', '0x1A');
        $this->assertSame(5, $saved['children_count']);
    }

    /**
     * @dataProvider nonNumericProvider
     */
    public function testNonNumericExactCountFallsBackToMidpoint($exact): void
    {
        $saved = $this->withExact('11-30 kids', $exact);
        $this->assertSame(20, $saved['children_count'], 'value: ' . var_export($exact, true));
    }

    public static function nonNumericProvider(): array
    {
        return [
            'abc'          => ['abc'],
            'empty string' => [''],
            'null'         => [null],
            'whitespace'   => ['   '],
            'bool true'    => [true],
            'bool false'   => [false],
        ];
    }

    // -------------------------------------------------------------------------
    // Fallback legacy: sin exactChildrenCount -> midpoint + warning (criterio 3)
    // -------------------------------------------------------------------------

    public function testMissingExactCountLogsWarningForEachRange(): void
    {
        $this->runCreateFromForm(['customer' => ['childrenRange' => '1-10 kids']]);
        $this->assertSame(5, $this->repoMock->lastCreated['children_count']);
        $this->assertLogged('warning', 'createFromForm: exactChildrenCount missing, falling back to range midpoint');
    }

    public function testMissingExactCountMidpointForThirtyOnePlus(): void
    {
        // '31+ kids' sin exacto -> midpoint 31.
        $saved = $this->runCreateFromForm(['customer' => ['childrenRange' => '31+ kids']]);
        $this->assertSame(31, $saved['children_count']);
        $this->assertLogged('warning', 'createFromForm: exactChildrenCount missing, falling back to range midpoint');
    }

    // -------------------------------------------------------------------------
    // Rango desconocido -> $bounds === null: no debe romper (Notas Tester)
    // -------------------------------------------------------------------------

    public function testUnknownRangeWithExactCountStoresValueVerbatimWithoutClampOrCrash(): void
    {
        // '99 kids' no está en $childrenRangeBounds -> $bounds === null.
        // El número exacto es numérico -> se usa intval() sin clamp. No debe romper.
        $saved = $this->withExact('99 kids', 45);
        $this->assertSame(45, $saved['children_count']);
    }

    public function testUnknownRangeWithHugeExactCountIsNotClamped(): void
    {
        $saved = $this->withExact('weird range', 123456);
        $this->assertSame(123456, $saved['children_count']);
    }

    public function testUnknownRangeWithoutExactCountIsRejected(): void
    {
        $data = $this->baseFormData();
        $data['customer']['childrenRange'] = '99 kids';
        unset($data['customer']['exactChildrenCount']);

        $this->expectException(HTTPException::class);
        $this->expectExceptionMessage('At least one child is required');

        $this->service->createFromForm($data);
    }

    public function testUnknownRangeWithNonNumericExactCountIsRejected(): void
    {
        $data = $this->baseFormData();
        $data['customer']['childrenRange']      = '99 kids';
        $data['customer']['exactChildrenCount'] = 'abc';

        $this->expectException(HTTPException::class);
        $this->expectExceptionMessage('At least one child is required');

        $this->service->createFromForm($data);
    }

    // -------------------------------------------------------------------------
    // children_age_range: nunca el rango de CANTIDAD (criterios 4 y 5)
    // -------------------------------------------------------------------------

    /**
     * @dataProvider rangeProvider
     */
    public function testChildrenAgeRangeNeverStoresTheCountRange(string $range): void
    {
        $saved = $this->runCreateFromForm([
            'customer'    => ['childrenRange' => $range, 'exactChildrenCount' => $range === '1-10 kids' ? 5 : 15],
            'information' => ['ageRange' => null],
        ]);

        $this->assertArrayHasKey('children_age_range', $saved);
        $this->assertNull($saved['children_age_range']);
        $this->assertNotSame($range, $saved['children_age_range']);
    }

    public static function rangeProvider(): array
    {
        return [
            '1-10 kids'  => ['1-10 kids'],
            '11-30 kids' => ['11-30 kids'],
            '31+ kids'   => ['31+ kids'],
        ];
    }

    public function testChildrenAgeRangeStillHonorsRealAgeRangeFromInformation(): void
    {
        $saved = $this->runCreateFromForm([
            'customer'    => ['childrenRange' => '1-10 kids', 'exactChildrenCount' => 8],
            'information' => ['ageRange' => '3-5'],
        ]);

        $this->assertSame('3-5', $saved['children_age_range']);
        $this->assertSame(8, $saved['children_count']);
    }

    public function testChildrenAgeRangeAssignedExactlyOnceInCreateFromForm(): void
    {
        $source = file_get_contents(APPPATH . 'Services/ReservationService.php');
        $start  = strpos($source, 'public function createFromForm');
        $end    = strpos($source, 'public function update(');
        $method = substr($source, $start, $end - $start);

        $this->assertSame(
            1,
            substr_count($method, "'children_age_range' =>"),
            'createFromForm debe asignar children_age_range exactamente una vez'
        );
    }

    // -------------------------------------------------------------------------
    // Plan criterio 2, ejemplo textual: 50 con "1-10 kids" -> 10
    // -------------------------------------------------------------------------

    public function testPlanExampleFiftyChildrenWithOneToTenRangeIsClampedToTen(): void
    {
        $saved = $this->withExact('1-10 kids', 50);
        $this->assertSame(10, $saved['children_count']);
        $this->assertNotSame(50, $saved['children_count']);
    }
}
