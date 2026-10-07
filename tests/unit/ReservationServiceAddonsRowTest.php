<?php

namespace Tests\Unit;

use App\Services\ReservationService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Fila "Add-ons" del correo de confirmación: los add-ons con precio 0
 * (cortesía asignada desde el admin) no se muestran al cliente.
 *
 * @internal
 */
final class ReservationServiceAddonsRowTest extends CIUnitTestCase
{
    private function buildRow(array $addons): string
    {
        $service = new ReservationService();

        $repo = new class ($addons) {
            public function __construct(private array $addons) {}

            public function getDetailedByReservation($id)
            {
                return $this->addons;
            }
        };

        $prop = new \ReflectionProperty(ReservationService::class, 'reservationAddonRepository');
        $prop->setAccessible(true);
        $prop->setValue($service, $repo);

        $method = new \ReflectionMethod(ReservationService::class, 'buildAddonsRow');
        $method->setAccessible(true);

        return $method->invoke($service, 'res-1');
    }

    public function testZeroPriceAddonsAreOmitted(): void
    {
        $row = $this->buildRow([
            (object) ['name' => 'Bubble Machine', 'suboption' => null, 'quantity' => 1, 'price_at_time' => 0],
            (object) ['name' => 'Face Painting', 'suboption' => null, 'quantity' => 2, 'price_at_time' => 50],
        ]);

        $this->assertStringContainsString('Face Painting x2', $row);
        $this->assertStringNotContainsString('Bubble Machine', $row);
    }

    public function testRowIsEmptyWhenAllAddonsAreZero(): void
    {
        $row = $this->buildRow([
            (object) ['name' => 'Bubble Machine', 'suboption' => null, 'quantity' => 1, 'price_at_time' => '0.00'],
        ]);

        $this->assertSame('', $row);
    }
}
