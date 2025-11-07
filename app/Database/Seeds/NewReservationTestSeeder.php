<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Services\ReservationService;
use App\Repositories\ZipCodeRepository;
use App\Repositories\ServicePriceRepository;
use App\Repositories\AddonRepository;

/**
 * Seeder para crear 5 NUEVAS reservas de prueba con escenarios específicos
 * Incluye prueba de Jukebox Live con sub-opciones
 */
class NewReservationTestSeeder extends Seeder
{
    public function run()
    {
        $reservationService = new ReservationService();
        $zipcodeRepo = new ZipCodeRepository();
        $servicePriceRepo = new ServicePriceRepository();
        $addonRepo = new AddonRepository();

        // Obtener datos necesarios
        $zipcodes = $zipcodeRepo->getAll();
        $servicePrices = $servicePriceRepo->getAll();
        $addons = $addonRepo->getAll();

        if (empty($zipcodes) || empty($servicePrices)) {
            echo "No hay zipcodes o service prices disponibles.\n";
            return;
        }

        // Buscar addon de Jukebox si existe
        $jukeboxAddon = null;
        foreach ($addons as $addon) {
            if (stripos($addon->name, 'Jukebox') !== false || $addon->price_type === 'jukebox') {
                $jukeboxAddon = $addon;
                break;
            }
        }

        // Definir 5 casos de prueba específicos
        $testCases = [
            [
                'customer' => [
                    'firstName' => 'Amanda',
                    'lastName' => 'Thompson',
                    'email' => 'amanda.thompson@newtest.com',
                    'phone' => '+1-555-111-2222'
                ],
                'kids' => 15,
                'eventDays' => 20,
                'serviceIdx' => 0,
                'addons' => $jukeboxAddon ? [
                    [
                        'addon' => $jukeboxAddon,
                        'selectedPrice' => 850,  // 2 hours, 2 performers
                        'selectedOption' => '2h-2p',
                        'quantity' => 1
                    ]
                ] : [],
                'songRequest' => 'Taylor Swift hits for kids',
                'description' => 'Reserva con Jukebox Live (2h, 2 performers)'
            ],
            [
                'customer' => [
                    'firstName' => 'Christopher',
                    'lastName' => 'Wilson',
                    'email' => 'christopher.wilson@newtest.com',
                    'phone' => '+1-555-222-3333'
                ],
                'kids' => 40, // Exactamente 40 niños (límite)
                'eventDays' => 45,
                'serviceIdx' => 1,
                'addons' => [],
                'songRequest' => 'Classic Disney songs',
                'description' => 'Reserva con exactamente 40 niños (sin extra charge)'
            ],
            [
                'customer' => [
                    'firstName' => 'Jessica',
                    'lastName' => 'Lee',
                    'email' => 'jessica.lee@newtest.com',
                    'phone' => '+1-555-333-4444'
                ],
                'kids' => 3,
                'eventDays' => 1, // Surcharge máximo (20%)
                'serviceIdx' => 2,
                'addons' => array_slice($addons, 0, 1), // Solo 1 addon
                'songRequest' => 'No requests',
                'description' => 'Reserva de último momento (1 día, surcharge 20%)'
            ],
            [
                'customer' => [
                    'firstName' => 'David',
                    'lastName' => 'Rodriguez',
                    'email' => 'david.rodriguez@newtest.com',
                    'phone' => '+1-555-444-5555'
                ],
                'kids' => 50, // Muchos niños extra
                'eventDays' => 60,
                'serviceIdx' => 3,
                'addons' => array_slice($addons, 0, min(4, count($addons))), // Varios addons
                'songRequest' => 'Mix of everything!',
                'description' => 'Reserva grande (50 niños) con múltiples addons'
            ],
            [
                'customer' => [
                    'firstName' => 'Rachel',
                    'lastName' => 'Anderson',
                    'email' => 'rachel.anderson@newtest.com',
                    'phone' => '+1-555-555-6666'
                ],
                'kids' => 100, // Cantidad máxima de niños
                'eventDays' => 3, // Surcharge medio (10%)
                'serviceIdx' => 4,
                'addons' => $jukeboxAddon ? [
                    [
                        'addon' => $jukeboxAddon,
                        'selectedPrice' => 375,  // 1 hour, 1 performer
                        'selectedOption' => '1h-1p',
                        'quantity' => 1
                    ]
                ] : array_slice($addons, 0, 2),
                'songRequest' => 'Top 40 kids hits',
                'description' => 'Reserva masiva (100 niños) con Jukebox 1h'
            ]
        ];

        $ageRanges = ['0-3', '4-6', '7-9', '10-12'];

        echo "\n";
        echo "╔══════════════════════════════════════════════════════════╗\n";
        echo "║  Creando 5 NUEVAS reservas de prueba (casos especiales) ║\n";
        echo "╚══════════════════════════════════════════════════════════╝\n";
        echo "\n";

        foreach ($testCases as $i => $testCase) {
            $caseNum = $i + 1;

            try {
                echo "┌────────────────────────────────────────────────────────┐\n";
                echo "│ Caso #{$caseNum}: {$testCase['description']}\n";
                echo "└────────────────────────────────────────────────────────┘\n";

                // Seleccionar datos del caso de prueba
                $customer = $testCase['customer'];
                $zipcode = $zipcodes[array_rand($zipcodes)];

                // Seleccionar servicio específico por índice
                $serviceIdx = $testCase['serviceIdx'] % count($servicePrices);
                $servicePrice = $servicePrices[$serviceIdx];

                $selectedKids = $testCase['kids'];
                $eventDate = date('Y-m-d', strtotime('+' . $testCase['eventDays'] . ' days'));

                // Procesar addons
                $selectedAddons = [];
                if (!empty($testCase['addons'])) {
                    foreach ($testCase['addons'] as $addonData) {
                        if (is_array($addonData) && isset($addonData['addon'])) {
                            // Addon con selectedPrice (Jukebox)
                            $addon = $addonData['addon'];
                            $selectedAddons[] = [
                                'id' => $addon->id,
                                'base_price' => $addon->base_price,
                                'selectedPrice' => $addonData['selectedPrice'],
                                'selectedOption' => $addonData['selectedOption'],
                                'suboption' => $addonData['selectedOption'],
                                'quantity' => $addonData['quantity'],
                                'estimated_duration_minutes' => $addon->estimated_duration_minutes ?? 0
                            ];
                        } else {
                            // Addon normal
                            $selectedAddons[] = [
                                'id' => $addonData->id,
                                'base_price' => $addonData->base_price,
                                'quantity' => 1,
                                'estimated_duration_minutes' => $addonData->estimated_duration_minutes ?? 0
                            ];
                        }
                    }
                }

                // Crear datos del formulario
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
                        'fullAddress' => rand(1000, 9999) . ' ' . ['Broadway', 'Park Ave', 'Main St', 'Ocean Dr'][array_rand(['Broadway', 'Park Ave', 'Main St', 'Ocean Dr'])] . ', Test City',
                        'eventDate' => $eventDate,
                        'startTime' => sprintf('%02d:00', rand(11, 17)),
                        'entertainmentStartTime' => sprintf('%02d:30', rand(11, 17)),
                        'birthdayChildName' => $customer['firstName'] . ' Jr.',
                        'childAge' => rand(2, 11),
                        'ageRange' => $ageRanges[array_rand($ageRanges)],
                        'songRequests' => $testCase['songRequest'],
                        'happyBirthdayRequest' => ($selectedKids <= 20) ? 'yes' : 'no',
                        'instructions' => 'Test reservation - Please confirm before event.'
                    ]
                ];

                // Crear reserva
                $result = $reservationService->createFromForm($formData);

                // Calcular extras
                $extraKids = max(0, $selectedKids - 40);

                echo "✓ Reserva creada exitosamente\n";
                echo "  ┌─ Cliente: {$customer['firstName']} {$customer['lastName']}\n";
                echo "  ├─ Email: {$customer['email']}\n";
                echo "  ├─ Fecha: {$eventDate} (en {$testCase['eventDays']} días)\n";
                echo "  ├─ Niños: {$selectedKids}" . ($extraKids > 0 ? " ({$extraKids} extras sobre 40)" : " (dentro del límite)") . "\n";
                echo "  ├─ Servicio: \${$servicePrice->amount}\n";
                echo "  ├─ Addons: " . count($selectedAddons);

                if (!empty($selectedAddons)) {
                    echo " (";
                    $addonNames = [];
                    foreach ($selectedAddons as $sa) {
                        if (isset($sa['selectedOption'])) {
                            $addonNames[] = "Jukebox {$sa['selectedOption']}";
                        }
                    }
                    echo implode(', ', $addonNames) . ")";
                }
                echo "\n";

                echo "  ├─ Duración: {$result['calculation']['total_duration_hours']} horas\n";
                echo "  ├────────── DESGLOSE ──────────\n";
                echo "  ├─ Base: \${$result['calculation']['service_price']}\n";
                echo "  ├─ Addons: \${$result['calculation']['addons_total']}\n";
                echo "  ├─ Extra niños: \${$result['calculation']['extra_children_total']}\n";
                echo "  ├─ Surcharge: \${$result['calculation']['surcharge_amount']}";

                $surchargePercent = 0;
                if ($testCase['eventDays'] < 2) {
                    $surchargePercent = 20;
                } elseif ($testCase['eventDays'] < 7) {
                    $surchargePercent = 10;
                }
                if ($surchargePercent > 0) {
                    echo " ({$surchargePercent}%)";
                }
                echo "\n";

                echo "  └─ TOTAL: \${$result['calculation']['grand_total']}\n";
                echo "\n";

            } catch (\Throwable $e) {
                echo "✗ Error creando reserva #{$caseNum}\n";
                echo "  Error: " . $e->getMessage() . "\n";
                echo "  Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
            }
        }

        echo "╔══════════════════════════════════════════════════════════╗\n";
        echo "║              ✓ Proceso completado!                      ║\n";
        echo "╚══════════════════════════════════════════════════════════╝\n";
    }
}
