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
 * Seeder para crear 5 reservas de prueba con datos variados
 * Prueba el flujo completo desde el home
 * Actualizado: Eliminado campo 'hours' - ahora usa duration del servicio
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

        // Definir 5 casos de prueba específicos
        $testCases = [
            [
                'customer' => ['firstName' => 'Sarah', 'lastName' => 'Johnson', 'email' => 'sarah.johnson@test.com', 'phone' => '+1-555-123-4567'],
                'kids' => 8,
                'eventDays' => 15,
                'numAddons' => 0,
                'songRequest' => 'Baby Shark, Let It Go',
                'description' => 'Reserva simple sin addons'
            ],
            [
                'customer' => ['firstName' => 'Michael', 'lastName' => 'Brown', 'email' => 'michael.brown@test.com', 'phone' => '+1-555-234-5678'],
                'kids' => 25,
                'eventDays' => 30,
                'numAddons' => 1,
                'songRequest' => 'Popular Disney songs',
                'description' => 'Reserva con 1 addon'
            ],
            [
                'customer' => ['firstName' => 'Jennifer', 'lastName' => 'Garcia', 'email' => 'jennifer.garcia@test.com', 'phone' => '+1-555-345-6789'],
                'kids' => 45,
                'eventDays' => 10,
                'numAddons' => 2,
                'songRequest' => 'Whatever the kids like!',
                'description' => 'Reserva con 45+ niños y 2 addons'
            ],
            [
                'customer' => ['firstName' => 'Robert', 'lastName' => 'Martinez', 'email' => 'robert.martinez@test.com', 'phone' => '+1-555-456-7890'],
                'kids' => 12,
                'eventDays' => 5,
                'numAddons' => 3,
                'songRequest' => 'Classic children songs',
                'description' => 'Reserva con surcharge (< 7 días) y 3 addons'
            ],
            [
                'customer' => ['firstName' => 'Emily', 'lastName' => 'Davis', 'email' => 'emily.davis@test.com', 'phone' => '+1-555-567-8901'],
                'kids' => 60,
                'eventDays' => 1,
                'numAddons' => 1,
                'songRequest' => 'Top 40 hits for kids',
                'description' => 'Reserva con máximo surcharge (< 2 días) y muchos niños'
            ]
        ];

        $ageRanges = ['0-2', '3-5', '6-8', '9-12'];

        echo "Creando 5 reservas de prueba con datos variados...\n\n";

        foreach ($testCases as $i => $testCase) {
            $caseNum = $i + 1;
            try {
                echo "----------------------------------------\n";
                echo "Caso #{$caseNum}: {$testCase['description']}\n";
                echo "----------------------------------------\n";

                // Seleccionar datos del caso de prueba
                $customer = $testCase['customer'];
                $zipcode = $zipcodes[array_rand($zipcodes)];
                $servicePrice = $servicePrices[array_rand($servicePrices)];
                $selectedKids = $testCase['kids'];
                $eventDate = date('Y-m-d', strtotime('+' . $testCase['eventDays'] . ' days'));

                // Seleccionar addons según el caso de prueba
                $selectedAddons = [];
                if ($testCase['numAddons'] > 0 && !empty($addons)) {
                    $numToSelect = min($testCase['numAddons'], count($addons));
                    $randomKeys = array_rand($addons, $numToSelect);
                    if (!is_array($randomKeys)) $randomKeys = [$randomKeys];

                    foreach ($randomKeys as $key) {
                        $addon = $addons[$key];
                        $selectedAddons[] = [
                            'id' => $addon->id,
                            'base_price' => $addon->base_price,
                            'quantity' => 1,
                            'estimated_duration_minutes' => $addon->estimated_duration_minutes ?? 0
                        ];
                    }
                }

                // Crear datos del formulario (SIN campo 'hours')
                $formData = [
                    'customer' => [
                        'firstName' => $customer['firstName'],
                        'lastName' => $customer['lastName'],
                        'email' => $customer['email'],
                        'phone' => $customer['phone'],
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
                        'min_duration_hours' => (float) ($servicePrice->min_duration_hours ?? 1),
                        'duration_hours' => (float) ($servicePrice->min_duration_hours ?? 1)
                    ],
                    'kids' => [
                        'selectedKids' => $selectedKids
                    ],
                    'addons' => $selectedAddons,
                    'information' => [
                        'fullAddress' => rand(100, 9999) . ' ' . ['Main', 'Oak', 'Elm', 'Maple'][array_rand(['Main', 'Oak', 'Elm', 'Maple'])] . ' St, Anytown, USA',
                        'eventDate' => $eventDate,
                        'startTime' => sprintf('%02d:00', rand(10, 16)),
                        'entertainmentStartTime' => sprintf('%02d:30', rand(10, 16)),
                        'birthdayChildName' => $customer['firstName'] . ' Jr.',
                        'childAge' => rand(1, 12),
                        'ageRange' => $ageRanges[array_rand($ageRanges)],
                        'songRequests' => $testCase['songRequest'],
                        'happyBirthdayRequest' => rand(0, 1) ? 'yes' : 'no',
                        'instructions' => 'Please park in the driveway. Ring the doorbell when you arrive.'
                    ]
                ];

                // Crear reserva
                $result = $reservationService->createFromForm($formData);

                echo "✓ Reserva creada exitosamente\n";
                echo "  Cliente: {$customer['firstName']} {$customer['lastName']} ({$customer['email']})\n";
                echo "  Fecha evento: {$eventDate} (en {$testCase['eventDays']} días)\n";
                echo "  Niños: {$selectedKids}\n";
                echo "  Servicio: \${$servicePrice->amount}\n";
                echo "  Addons: " . count($selectedAddons) . "\n";
                echo "  Base Total: \${$result['calculation']['base_total']}\n";
                echo "  Surcharge: \${$result['calculation']['surcharge_amount']}\n";
                echo "  TOTAL: \${$result['calculation']['grand_total']}\n";
                echo "  Duración: {$result['calculation']['total_duration_hours']} horas\n\n";

            } catch (\Throwable $e) {
                echo "✗ Error creando reserva #{$caseNum}: " . $e->getMessage() . "\n";
                echo "  Stack trace: " . $e->getTraceAsString() . "\n\n";
            }
        }

        echo "========================================\n";
        echo "Proceso completado!\n";
        echo "========================================\n";
    }
}
