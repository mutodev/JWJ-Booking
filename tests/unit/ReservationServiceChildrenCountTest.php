<?php

namespace Tests\Unit;

use App\Services\ReservationService;
use CodeIgniter\Database\Config as DatabaseConfig;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\Test\CIUnitTestCase;
use Faker\Factory as FakerFactory;

/**
 * A1 — "Número de niños y edades del segundo formulario".
 *
 * Cubre la resolución de children_count / children_age_range dentro de
 * ReservationService::createFromForm() (flujo público del formulario).
 *
 * Estrategia sin base de datos:
 *  - Todos los repositorios se sustituyen por dobles vía Reflection.
 *  - La conexión a BD (\Config\Database::connect()) se sustituye por un doble
 *    que hace no-op de las transacciones, inyectado en el cache estático
 *    protegido de CodeIgniter\Database\Config.
 *  - sendConfirmationEmail() sale temprano porque el objeto reserva devuelto
 *    por el repositorio doble tiene email = null.
 *
 * @internal
 */
final class ReservationServiceChildrenCountTest extends CIUnitTestCase
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

        // -- Repository doubles -------------------------------------------------
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

        // -- Database connection double --------------------------------------
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
     * Base payload for the public multi-step form. The base customer has a
     * children range but NO exactChildrenCount (legacy fallback path).
     *
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
     * @return array<string,mixed> The reservation payload passed to repository::create()
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

    // -------------------------------------------------------------------------
    // Éxito: exactChildrenCount dentro del rango
    // -------------------------------------------------------------------------

    public function testExactCountWithinOneToTenRangeIsStoredVerbatim(): void
    {
        $saved = $this->runCreateFromForm([
            'customer' => ['childrenRange' => '1-10 kids', 'exactChildrenCount' => 7],
        ]);

        $this->assertSame(7, $saved['children_count']);
    }

    public function testExactCountWithinElevenToThirtyRangeIsStoredVerbatim(): void
    {
        $saved = $this->runCreateFromForm([
            'customer' => ['childrenRange' => '11-30 kids', 'exactChildrenCount' => 14],
        ]);

        $this->assertSame(14, $saved['children_count']);
    }

    // -------------------------------------------------------------------------
    // Clamp por arriba
    // -------------------------------------------------------------------------

    public function testExactCountAboveOneToTenRangeIsClampedToTen(): void
    {
        $saved = $this->runCreateFromForm([
            'customer' => ['childrenRange' => '1-10 kids', 'exactChildrenCount' => 999999],
        ]);

        $this->assertSame(10, $saved['children_count']);
    }

    public function testExactCountAboveElevenToThirtyRangeIsClampedToThirty(): void
    {
        $saved = $this->runCreateFromForm([
            'customer' => ['childrenRange' => '11-30 kids', 'exactChildrenCount' => 999999],
        ]);

        $this->assertSame(30, $saved['children_count']);
    }

    public function testExactCountAboveThirtyOnePlusRangeIsClampedToRangeCeiling(): void
    {
        $saved = $this->runCreateFromForm([
            'customer' => ['childrenRange' => '31+ kids', 'exactChildrenCount' => 999999],
        ]);

        $this->assertSame(999, $saved['children_count']);
    }

    // -------------------------------------------------------------------------
    // Clamp por abajo
    // -------------------------------------------------------------------------

    public function testExactCountZeroIsRaisedToRangeMinimum(): void
    {
        $saved = $this->runCreateFromForm([
            'customer' => ['childrenRange' => '1-10 kids', 'exactChildrenCount' => 0],
        ]);

        $this->assertSame(1, $saved['children_count']);
    }

    public function testNegativeExactCountIsRaisedToRangeMinimum(): void
    {
        $saved = $this->runCreateFromForm([
            'customer' => ['childrenRange' => '11-30 kids', 'exactChildrenCount' => -3],
        ]);

        $this->assertSame(11, $saved['children_count']);
    }

    // -------------------------------------------------------------------------
    // Valores límite exactos
    // -------------------------------------------------------------------------

    public function testExactCountAtRangeBoundariesIsUnchanged(): void
    {
        $this->assertSame(1, $this->runCreateFromForm([
            'customer' => ['childrenRange' => '1-10 kids', 'exactChildrenCount' => 1],
        ])['children_count']);

        $this->assertSame(10, $this->runCreateFromForm([
            'customer' => ['childrenRange' => '1-10 kids', 'exactChildrenCount' => 10],
        ])['children_count']);

        $this->assertSame(11, $this->runCreateFromForm([
            'customer' => ['childrenRange' => '11-30 kids', 'exactChildrenCount' => 11],
        ])['children_count']);

        $this->assertSame(30, $this->runCreateFromForm([
            'customer' => ['childrenRange' => '11-30 kids', 'exactChildrenCount' => 30],
        ])['children_count']);
    }

    // -------------------------------------------------------------------------
    // exactChildrenCount como string / no numérico
    // -------------------------------------------------------------------------

    public function testNumericStringExactCountIsAccepted(): void
    {
        $saved = $this->runCreateFromForm([
            'customer' => ['childrenRange' => '11-30 kids', 'exactChildrenCount' => '14'],
        ]);

        $this->assertSame(14, $saved['children_count']);
    }

    public function testNonNumericExactCountFallsBackToMidpoint(): void
    {
        foreach (['abc', '', null] as $bad) {
            $saved = $this->runCreateFromForm([
                'customer' => ['childrenRange' => '1-10 kids', 'exactChildrenCount' => $bad],
            ]);

            $this->assertSame(5, $saved['children_count'], 'value: ' . var_export($bad, true));
        }
    }

    // -------------------------------------------------------------------------
    // Fallback legacy: sin exactChildrenCount -> punto medio + warning
    // -------------------------------------------------------------------------

    public function testMissingExactCountFallsBackToMidpointOfOneToTen(): void
    {
        $saved = $this->runCreateFromForm([
            'customer' => ['childrenRange' => '1-10 kids'],
        ]);

        $this->assertSame(5, $saved['children_count']);
        $this->assertLogged('warning', 'createFromForm: exactChildrenCount missing, falling back to range midpoint');
    }

    public function testMissingExactCountFallsBackToMidpointOfElevenToThirty(): void
    {
        $saved = $this->runCreateFromForm([
            'customer' => ['childrenRange' => '11-30 kids'],
        ]);

        $this->assertSame(20, $saved['children_count']);
    }

    // -------------------------------------------------------------------------
    // children_age_range: ya NO se copia el rango de cantidad
    // -------------------------------------------------------------------------

    public function testChildrenAgeRangeIsNullWhenInformationAgeRangeIsNull(): void
    {
        $saved = $this->runCreateFromForm([
            'customer'    => ['childrenRange' => '1-10 kids', 'exactChildrenCount' => 6],
            'information' => ['ageRange' => null],
        ]);

        $this->assertArrayHasKey('children_age_range', $saved);
        $this->assertNull($saved['children_age_range']);
        // Regresión A1: nunca debe guardarse el rango de CANTIDAD como edad.
        $this->assertNotSame('1-10 kids', $saved['children_age_range']);
    }

    public function testChildrenAgeRangeUsesInformationAgeRangeWhenProvided(): void
    {
        $saved = $this->runCreateFromForm([
            'customer'    => ['childrenRange' => '11-30 kids', 'exactChildrenCount' => 12],
            'information' => ['ageRange' => '5-7'],
        ]);

        $this->assertSame('5-7', $saved['children_age_range']);
        $this->assertSame(12, $saved['children_count']);
    }

    public function testChildrenAgeRangeIsNotAssignedTwice(): void
    {
        // El array literal de reservationData sólo debe contener una asignación
        // de 'children_age_range' (se eliminó la copia de $customer['childrenRange']).
        $source = file_get_contents(APPPATH . 'Services/ReservationService.php');
        $method = substr(
            $source,
            strpos($source, 'public function createFromForm'),
            strpos($source, 'public function update(') - strpos($source, 'public function createFromForm')
        );

        $this->assertSame(
            1,
            substr_count($method, "'children_age_range' =>"),
            'createFromForm debe asignar children_age_range exactamente una vez'
        );
    }

    // -------------------------------------------------------------------------
    // No regresión: rama legacy $formData['kids']
    // -------------------------------------------------------------------------

    public function testLegacyKidsSelectedKidsStillPopulatesChildrenCount(): void
    {
        $data = $this->baseFormData();
        unset($data['customer']['childrenRange']);
        $data['kids'] = ['selectedKids' => 8];

        $this->service->createFromForm($data);

        $this->assertSame(8, $this->repoMock->lastCreated['children_count']);
    }

    public function testLegacyKidsCountKeyStillPopulatesChildrenCount(): void
    {
        $data = $this->baseFormData();
        unset($data['customer']['childrenRange']);
        $data['kids'] = ['count' => 15];

        $this->service->createFromForm($data);

        $this->assertSame(15, $this->repoMock->lastCreated['children_count']);
    }

    public function testLegacyPathRejectsLessThanOneChild(): void
    {
        $data = $this->baseFormData();
        unset($data['customer']['childrenRange']);
        $data['kids'] = ['selectedKids' => 0];

        $this->expectException(HTTPException::class);
        $this->expectExceptionMessage('At least one child is required');

        $this->service->createFromForm($data);
    }

    public function testUnknownChildrenRangeWithoutExactCountIsRejected(): void
    {
        // Rango desconocido -> bounds null -> selectedKids sigue 0 -> rechazo.
        $data = $this->baseFormData();
        $data['customer']['childrenRange'] = 'weird range';

        $this->expectException(HTTPException::class);
        $this->expectExceptionMessage('At least one child is required');

        $this->service->createFromForm($data);
    }

    // -------------------------------------------------------------------------
    // 31+ kids: número exacto real (no clamp cuando está dentro de límites)
    // -------------------------------------------------------------------------

    public function testThirtyOnePlusRangeKeepsExactCountWithinBounds(): void
    {
        $saved = $this->runCreateFromForm([
            'customer' => ['childrenRange' => '31+ kids', 'exactChildrenCount' => 50],
        ]);

        $this->assertSame(50, $saved['children_count']);
    }
}
