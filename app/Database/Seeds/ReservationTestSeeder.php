<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Services\ReservationService;
use App\Repositories\CustomerRepository;
use App\Repositories\ZipCodeRepository;
use App\Repositories\ServicePriceRepository;
use App\Repositories\MetropolitanAreaRepository;
use App\Repositories\AddonRepository;

/**
 * Seeder para crear 10 reservas de prueba con datos variados
 * Prueba el flujo completo desde el home
 */
class ReservationTestSeeder extends Seeder
{
    public function run()
    {
        $reservationService = new ReservationService();
        $customerRepo = new CustomerRepository();
        $zipcodeRepo = new ZipCodeRepository();
        $servicePriceRepo = new ServicePriceRepository();
        $addonRepo = new AddonRepository();

        // Obtener datos necesarios
        $zipcodes = $zipcodeRepo->getAll();
        $servicePrices = $servicePriceRepo->getAll();
        $addons = $addonRepo->getAll();

        if (empty($zipcodes) || empty($servicePrices)) {
            echo "No hay zipcodes o service prices disponibles. Por favor ejecuta los seeders base primero.\n";
            return;
        }

        $firstNames = ['John', 'Mary', 'James', 'Patricia', 'Robert', 'Jennifer', 'Michael', 'Linda', 'William', 'Elizabeth'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];
        $ageRanges = ['0-2', '3-5', '6-8', '9-12'];
        $songRequests = [
            'Baby Shark, Let It Go',
            'Wheels on the Bus, Old MacDonald',
            'Happy Birthday',
            'No song requests',
            'Whatever the kids like!',
            'Popular Disney songs',
            'Classic children songs',
            'Top 40 hits for kids'
        ];

        echo "Creando 10 reservas de prueba...\n\n";

        for ($i = 1; $i <= 10; $i++) {
            try {
                // Seleccionar datos aleatorios
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $email = strtolower($firstName . '.' . $lastName . $i . '@test.com');
                $zipcode = $zipcodes[array_rand($zipcodes)];
                $servicePrice = $servicePrices[array_rand($servicePrices)];
                $selectedKids = rand(5, 50);
                $eventDate = date('Y-m-d', strtotime('+' . rand(8, 60) . ' days'));

                // Seleccionar addons aleatorios (0-3 addons)
                $numAddons = rand(0, min(3, count($addons)));
                $selectedAddons = [];
                if ($numAddons > 0 && !empty($addons)) {
                    $randomAddons = (array) array_rand(array_column($addons, 'id'), $numAddons);
                    foreach ($randomAddons as $idx) {
                        if (isset($addons[$idx])) {
                            $selectedAddons[] = [
                                'id' => $addons[$idx]->id,
                                'base_price' => $addons[$idx]->base_price,
                                'quantity' => rand(1, 2),
                                'estimated_duration_minutes' => $addons[$idx]->estimated_duration_minutes ?? 0
                            ];
                        }
                    }
                }

                // Crear datos del formulario
                $formData = [
                    'customer' => [
                        'firstName' => $firstName,
                        'lastName' => $lastName,
                        'email' => $email,
                        'phone' => '+1-555-' . rand(100, 999) . '-' . rand(1000, 9999),
                        'areaId' => $zipcode->metropolitan_area_id ?? null
                    ],
                    'zipcode' => [
                        'id' => $zipcode->id,
                        'zipcode' => $zipcode->zipcode
                    ],
                    'service' => [
                        'id' => $servicePrice->id,
                        'amount' => (float) $servicePrice->amount,
                        'extra_child_fee' => (float) ($servicePrice->extra_child_fee ?? 0),
                        'performers_count' => $servicePrice->performers_count,
                        'children_count' => 40,
                        'min_duration_hours' => 2
                    ],
                    'kids' => [
                        'selectedKids' => $selectedKids
                    ],
                    'hours' => [
                        'duration' => rand(2, 4),
                        'hours' => rand(2, 4)
                    ],
                    'addons' => $selectedAddons,
                    'information' => [
                        'fullAddress' => rand(100, 9999) . ' Main St, Anytown, USA',
                        'eventDate' => $eventDate,
                        'startTime' => rand(10, 16) . ':00',
                        'entertainmentStartTime' => rand(10, 16) . ':30',
                        'birthdayChildName' => $firstName . ' Jr.',
                        'childAge' => rand(1, 12),
                        'ageRange' => $ageRanges[array_rand($ageRanges)],
                        'songRequests' => $songRequests[array_rand($songRequests)],
                        'happyBirthdayRequest' => rand(0, 1) ? 'yes' : 'no',
                        'instructions' => 'Please park in the driveway. Ring the doorbell when you arrive.'
                    ]
                ];

                // Crear reserva
                $result = $reservationService->createFromForm($formData);

                echo "✓ Reserva #{$i} creada exitosamente\n";
                echo "  Cliente: {$firstName} {$lastName} ({$email})\n";
                echo "  Fecha: {$eventDate}\n";
                echo "  Niños: {$selectedKids}\n";
                echo "  Total: \${$result['calculation']['grand_total']}\n";
                echo "  Addons: " . count($selectedAddons) . "\n\n";

            } catch (\Throwable $e) {
                echo "✗ Error creando reserva #{$i}: " . $e->getMessage() . "\n\n";
            }
        }

        echo "Proceso completado!\n";
    }
}
