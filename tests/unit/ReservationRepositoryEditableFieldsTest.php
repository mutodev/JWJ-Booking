<?php

namespace Tests\Unit;

use App\Repositories\ReservationRepository;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * B6 — mass-assignment guard: ReservationRepository::updateEditable().
 *
 * PUT /api/reservations/{id} enruta aqui. Solo las columnas no-financieras y
 * editables por el admin sobreviven al `array_intersect_key` contra
 * EDITABLE_FIELDS; toda columna de precio / balance / identificador de pago
 * (total_amount, amount_paid, balance_due, base_price, travel_fee, expedite_fee,
 * expedition_fee, discount_amount, promo_code, gratuity_amount, paid_at,
 * stripe_*) queda fuera y solo la escriben los metodos de servicio dedicados.
 *
 * Sin base de datos: se inyecta un ReservationModel falso (fluent no-op + update
 * que registra lo recibido) en la propiedad protegida `model`.
 *
 * Cubre los riesgos de seguridad "Mass assignment" del plan B6.
 *
 * @internal
 */
final class ReservationRepositoryEditableFieldsTest extends CIUnitTestCase
{
    private ReservationRepository $repo;
    private object $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = new class {
            /** @var array<int,array{0:string,1:array}> */
            public array $updateCalls = [];

            public function update($id = null, $data = null): bool
            {
                $this->updateCalls[] = [(string) $id, (array) $data];

                return true;
            }

            // getById() del repo encadena select()->join()->where()->first().
            public function select($s)
            {
                return $this;
            }

            public function join($a, $b, $c = 'inner')
            {
                return $this;
            }

            public function where($k, $v = null)
            {
                return $this;
            }

            public function first()
            {
                return (object) ['id' => 'res-1'];
            }
        };

        $this->repo = new ReservationRepository();
        $ref = new \ReflectionProperty(ReservationRepository::class, 'model');
        $ref->setAccessible(true);
        $ref->setValue($this->repo, $this->model);
    }

    private function lastUpdatePayload(): ?array
    {
        if ($this->model->updateCalls === []) {
            return null;
        }

        return $this->model->updateCalls[array_key_last($this->model->updateCalls)][1];
    }

    // -------------------------------------------------------------------------

    public function testFinancialFieldsNeverReachTheModel(): void
    {
        $this->repo->updateEditable('res-1', [
            'total_amount'     => 999.0,
            'amount_paid'      => 999.0,
            'balance_due'      => -50.0,
            'base_price'       => 1.0,
            'travel_fee'       => 1.0,
            'expedite_fee'     => 1.0,
            'expedition_fee'   => 1.0,
            'discount_amount'  => 500.0,
            'promo_code'       => 'HACK',
            'gratuity_amount'  => 1.0,
            'paid_at'          => '2020-01-01 00:00:00',
            'stripe_payment_intent_id' => 'pi_hack',
            'stripe_session_id'        => 'cs_hack',
            'payment_url'              => 'https://evil',
        ]);

        // Ninguna clave financiera coincide con EDITABLE_FIELDS -> $clean vacio
        // -> model->update() NUNCA se llama.
        $this->assertSame([], $this->model->updateCalls);
    }

    public function testEditableAdminFieldsDoReachTheModel(): void
    {
        $this->repo->updateEditable('res-1', [
            'event_address'  => '742 Evergreen Terrace',
            'children_count' => 12,
            'status'         => 'confirmed',
            'is_paid'        => true,
            'is_invoiced'    => true,
        ]);

        $payload = $this->lastUpdatePayload();
        $this->assertNotNull($payload);
        $this->assertSame('742 Evergreen Terrace', $payload['event_address']);
        $this->assertSame(12, $payload['children_count']);
        $this->assertSame('confirmed', $payload['status']);
        $this->assertTrue($payload['is_paid']);
        $this->assertTrue($payload['is_invoiced']);
    }

    public function testMixedPayloadPassesOnlyTheWhitelistedKeys(): void
    {
        $this->repo->updateEditable('res-1', [
            'event_address' => 'New address',
            'total_amount'  => 12345.0,
            'promo_code'    => 'HACK',
            'song_requests' => 'Let it go',
            'balance_due'   => 0.0,
        ]);

        $payload = $this->lastUpdatePayload();
        $this->assertSame(['event_address', 'song_requests'], array_keys($payload));
        $this->assertArrayNotHasKey('total_amount', $payload);
        $this->assertArrayNotHasKey('promo_code', $payload);
        $this->assertArrayNotHasKey('balance_due', $payload);
    }

    public function testEditableFieldsConstantOmitsEveryFinancialColumn(): void
    {
        $ref = new \ReflectionClass(ReservationRepository::class);
        $fields = $ref->getConstant('EDITABLE_FIELDS');

        foreach ([
            'base_price', 'total_amount', 'amount_paid', 'balance_due', 'travel_fee',
            'expedite_fee', 'expedition_fee', 'discount_amount', 'promo_code',
            'gratuity_amount', 'paid_at', 'addons_total', 'extra_children_fee',
            'stripe_payment_intent_id', 'stripe_session_id', 'payment_url',
        ] as $forbidden) {
            $this->assertNotContains($forbidden, $fields, "{$forbidden} no debe ser editable via PUT");
        }
    }

    public function testEditableFieldsConstantKeepsAdminTogglableColumns(): void
    {
        $ref = new \ReflectionClass(ReservationRepository::class);
        $fields = $ref->getConstant('EDITABLE_FIELDS');

        foreach (['is_paid', 'is_invoiced', 'status', 'service_price_id', 'zipcode_id', 'duration_hours', 'performers_count'] as $allowed) {
            $this->assertContains($allowed, $fields, "{$allowed} debe seguir siendo editable");
        }
    }
}
