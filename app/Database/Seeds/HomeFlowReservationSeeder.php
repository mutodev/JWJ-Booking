<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Services\ReservationService;
use App\Repositories\MetropolitanAreaRepository;
use App\Repositories\ZipCodeRepository;
use App\Repositories\ServicePriceRepository;
use App\Repositories\AddonRepository;
use App\Repositories\DurationRepository;
use App\Repositories\ChildrenAgeRangeRepository;

/**
 * Seeder que simula el flujo completo del home paso a paso
 * Crea 5 reservas siguiendo exactamente el proceso del formulario
 */
class HomeFlowReservationSeeder extends Seeder
{
    private $metropolitanAreaRepo;
    private $zipcodeRepo;
    private $servicePriceRepo;
    private $addonRepo;
    private $durationRepo;
    private $childrenAgeRangeRepo;
    private $reservationService;

    public function run()
    {
        // Inicializar repositorios
        $this->metropolitanAreaRepo = new MetropolitanAreaRepository();
        $this->zipcodeRepo = new ZipCodeRepository();
        $this->servicePriceRepo = new ServicePriceRepository();
        $this->addonRepo = new AddonRepository();
        $this->durationRepo = new DurationRepository();
        $this->childrenAgeRangeRepo = new ChildrenAgeRangeRepository();
        $this->reservationService = new ReservationService();

        echo "\n========================================\n";
        echo "SIMULACIÓN DEL FLUJO HOME - 5 RESERVAS\n";
        echo "========================================\n\n";

        // Datos de clientes de prueba
        $customers = [
            [
                'firstName' => 'Sarah',
                'lastName' => 'Anderson',
                'email' => 'sarah.anderson@homeflow.com',
                'phone' => '+1-555-100-2001'
            ],
            [
                'firstName' => 'David',
                'lastName' => 'Thompson',
                'email' => 'david.thompson@homeflow.com',
                'phone' => '+1-555-100-2002'
            ],
            [
                'firstName' => 'Emma',
                'lastName' => 'Martinez',
                'email' => 'emma.martinez@homeflow.com',
                'phone' => '+1-555-100-2003'
            ],
            [
                'firstName' => 'Daniel',
                'lastName' => 'Wilson',
                'email' => 'daniel.wilson@homeflow.com',
                'phone' => '+1-555-100-2004'
            ],
            [
                'firstName' => 'Sophia',
                'lastName' => 'Garcia',
                'email' => 'sophia.garcia@homeflow.com',
                'phone' => '+1-555-100-2005'
            ]
        ];

        for ($i = 0; $i < 5; $i++) {
            echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "RESERVA #" . ($i + 1) . "\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            try {
                $customer = $customers[$i];

                // STEP 1: Seleccionar Metropolitan Area y Zipcode
                echo "🔹 STEP 1: Contact Information\n";
                $step1Data = $this->simulateStep1($customer);
                if (!$step1Data) {
                    echo "✗ Error en Step 1\n";
                    continue;
                }
                echo "✓ Step 1 completado\n\n";

                // STEP 2: Seleccionar Servicio y Duración
                echo "🔹 STEP 2: Choose Service and Duration\n";
                $step2Data = $this->simulateStep2($step1Data);
                if (!$step2Data) {
                    echo "✗ Error en Step 2\n";
                    continue;
                }
                echo "✓ Step 2 completado\n\n";

                // STEP 3: Seleccionar Rango de Edades
                echo "🔹 STEP 3: Children Age Range\n";
                $step3Data = $this->simulateStep3($step2Data);
                if (!$step3Data) {
                    echo "✗ Error en Step 3\n";
                    continue;
                }
                echo "✓ Step 3 completado\n\n";

                // STEP 4: Seleccionar Addons
                echo "🔹 STEP 4: Select Add-ons\n";
                $step4Data = $this->simulateStep4($step3Data);
                echo "✓ Step 4 completado\n\n";

                // STEP 5: Resumen (solo confirmación)
                echo "🔹 STEP 5: Summary\n";
                $step5Data = $this->simulateStep5($step4Data);
                echo "✓ Step 5 completado\n\n";

                // STEP 6: Información del Evento
                echo "🔹 STEP 6: Event Information\n";
                $step6Data = $this->simulateStep6($step5Data, $customer);
                echo "✓ Step 6 completado\n\n";

                // Crear la reserva
                echo "🔹 CREATING RESERVATION...\n";
                $result = $this->reservationService->createFromForm($step6Data);

                echo "✓ RESERVA CREADA EXITOSAMENTE\n";
                echo "  Cliente: {$customer['firstName']} {$customer['lastName']}\n";
                echo "  Email: {$customer['email']}\n";
                echo "  Servicio: {$step2Data['service']->name}\n";
                echo "  Fecha: {$step6Data['information']['eventDate']}\n";
                echo "  Niños: {$step3Data['kids']['selectedKids']}\n";
                echo "  Duración: {$step2Data['hours']['hours']} hora(s)\n";
                echo "  Addons: " . count($step4Data['addons']) . "\n";
                echo "  Subtotal: \${$result['calculation']['base_total']}\n";
                echo "  Recargo: \${$result['calculation']['surcharge_amount']}\n";
                echo "  Total: \${$result['calculation']['grand_total']}\n";

            } catch (\Throwable $e) {
                echo "✗ ERROR: " . $e->getMessage() . "\n";
                echo "  Trace: " . $e->getFile() . ":" . $e->getLine() . "\n";
            }
        }

        echo "\n========================================\n";
        echo "PROCESO COMPLETADO\n";
        echo "========================================\n\n";
    }

    /**
     * STEP 1: Simula la selección de Metropolitan Area y Zipcode
     */
    private function simulateStep1($customer)
    {
        echo "  → Obteniendo Metropolitan Areas disponibles...\n";
        $areas = $this->metropolitanAreaRepo->getAllActive();

        if (empty($areas)) {
            echo "  ✗ No hay Metropolitan Areas disponibles\n";
            return null;
        }

        // Buscar el área de Miami que sabemos tiene datos completos
        $selectedArea = null;
        foreach ($areas as $area) {
            if (stripos($area->name, 'MIAMI') !== false || stripos($area->name, 'PALM BEACH') !== false) {
                $selectedArea = $area;
                break;
            }
        }

        // Si no se encuentra Miami, usar cualquier área
        if (!$selectedArea) {
            $selectedArea = $areas[array_rand($areas)];
        }

        echo "  → Metropolitan Area seleccionada: {$selectedArea->name}\n";

        // Obtener zipcodes del área
        echo "  → Buscando zipcodes en el área...\n";
        $db = \Config\Database::connect();
        $zipcodes = $db->table('zipcodes')
            ->select('zipcodes.*')
            ->join('cities', 'cities.id = zipcodes.city_id')
            ->join('counties', 'counties.id = cities.county_id')
            ->where('counties.metropolitan_area_id', $selectedArea->id)
            ->where('zipcodes.is_active', true)
            ->get()
            ->getResult();

        if (empty($zipcodes)) {
            echo "  ✗ No hay zipcodes disponibles en esta área\n";
            return null;
        }

        $selectedZipcode = $zipcodes[array_rand($zipcodes)];
        echo "  → Zipcode seleccionado: {$selectedZipcode->zipcode}\n";

        return [
            'customer' => array_merge($customer, [
                'areaId' => $selectedArea->id,
                'countyId' => '',
                'cityId' => ''
            ]),
            'zipcode' => [
                'id' => $selectedZipcode->id,
                'zipcode' => $selectedZipcode->zipcode
            ],
            'metropolitanArea' => $selectedArea
        ];
    }

    /**
     * STEP 2: Simula la selección de Servicio y Duración
     */
    private function simulateStep2($step1Data)
    {
        echo "  → Obteniendo servicios disponibles para el área...\n";
        $services = $this->servicePriceRepo->getAllByMetropolitanArea($step1Data['metropolitanArea']->id);

        if (empty($services)) {
            echo "  ✗ No hay servicios disponibles\n";
            return null;
        }

        $selectedService = $services[array_rand($services)];
        echo "  → Servicio seleccionado: {$selectedService->name}\n";
        echo "  → Precio: \${$selectedService->amount}\n";

        // Obtener duraciones disponibles
        echo "  → Obteniendo duraciones disponibles...\n";
        $durations = $this->durationRepo->findByServicePriceId($selectedService->id);

        if (empty($durations)) {
            echo "  ✗ No hay duraciones disponibles\n";
            return null;
        }

        $selectedDuration = $durations[array_rand($durations)];
        $hours = $selectedDuration->minutes / 60;
        echo "  → Duración seleccionada: {$hours} hora(s) ({$selectedDuration->minutes} minutos)\n";

        return array_merge($step1Data, [
            'service' => $selectedService,
            'hours' => [
                'id' => $selectedDuration->id,
                'duration' => $hours,
                'hours' => $hours,
                'minutes' => $selectedDuration->minutes
            ]
        ]);
    }

    /**
     * STEP 3: Simula la selección de Rango de Edades
     */
    private function simulateStep3($step2Data)
    {
        echo "  → Obteniendo rangos de edad disponibles...\n";
        $ageRanges = $this->childrenAgeRangeRepo->findByServicePriceId($step2Data['service']->id);

        if (empty($ageRanges)) {
            echo "  ✗ No hay rangos de edad disponibles\n";
            return null;
        }

        $selectedAgeRange = $ageRanges[array_rand($ageRanges)];
        $minKids = max(1, $selectedAgeRange->min_children); // Asegurar mínimo 1 niño
        $maxKids = max($minKids, min($selectedAgeRange->max_children, 50)); // Asegurar que max >= min
        $selectedKids = rand($minKids, $maxKids);

        echo "  → Rango de edad: {$selectedAgeRange->min_age}-{$selectedAgeRange->max_age} años\n";
        echo "  → Número de niños: {$selectedKids}\n";

        return array_merge($step2Data, [
            'kids' => [
                'selectedKids' => $selectedKids,
                'min_age' => $selectedAgeRange->min_age,
                'max_age' => $selectedAgeRange->max_age,
                'isValid' => true
            ]
        ]);
    }

    /**
     * STEP 4: Simula la selección de Addons
     */
    private function simulateStep4($step3Data)
    {
        echo "  → Obteniendo addons disponibles...\n";
        $allAddons = $this->addonRepo->getAllActive();

        $selectedAddons = [];
        $numAddons = rand(0, min(3, count($allAddons)));

        if ($numAddons > 0 && !empty($allAddons)) {
            $randomKeys = array_rand($allAddons, $numAddons);
            if (!is_array($randomKeys)) {
                $randomKeys = [$randomKeys];
            }

            foreach ($randomKeys as $key) {
                $addon = $allAddons[$key];
                $quantity = rand(1, 2);
                $selectedAddons[] = [
                    'id' => $addon->id,
                    'name' => $addon->name,
                    'base_price' => (float) $addon->base_price,
                    'quantity' => $quantity,
                    'estimated_duration_minutes' => $addon->estimated_duration_minutes ?? 0,
                    'price_type' => $addon->price_type ?? 'standard'
                ];
                echo "  → Addon: {$addon->name} (x{$quantity}) - \$" . ($addon->base_price * $quantity) . "\n";
            }
        } else {
            echo "  → Sin addons seleccionados\n";
        }

        return array_merge($step3Data, [
            'addons' => $selectedAddons
        ]);
    }

    /**
     * STEP 5: Simula el resumen (confirmación)
     */
    private function simulateStep5($step4Data)
    {
        // Calcular subtotal
        $servicePrice = (float) $step4Data['service']->amount;
        $addonsTotal = array_reduce($step4Data['addons'], function($sum, $addon) {
            return $sum + ($addon['base_price'] * $addon['quantity']);
        }, 0);

        $selectedKids = $step4Data['kids']['selectedKids'];
        $maxKidsIncluded = 40;
        $extraChildren = max(0, $selectedKids - $maxKidsIncluded);
        $extraChildFee = (float) ($step4Data['service']->extra_child_fee ?? 0);
        $extraChildrenTotal = $extraChildren * $extraChildFee;

        $subtotal = $servicePrice + $addonsTotal + $extraChildrenTotal;

        echo "  → Servicio: \${$servicePrice}\n";
        echo "  → Addons: \${$addonsTotal}\n";
        echo "  → Niños extra ({$extraChildren}): \${$extraChildrenTotal}\n";
        echo "  → Subtotal: \${$subtotal}\n";

        return array_merge($step4Data, [
            'subtotal' => [
                'amount' => $subtotal,
                'isConfirmed' => true
            ]
        ]);
    }

    /**
     * STEP 6: Simula la información del evento
     */
    private function simulateStep6($step5Data, $customer)
    {
        $eventDate = date('Y-m-d', strtotime('+' . rand(8, 60) . ' days'));
        $startTime = rand(10, 16) . ':00';

        echo "  → Dirección: 123 Main Street, City\n";
        echo "  → Fecha del evento: {$eventDate}\n";
        echo "  → Hora de inicio: {$startTime}\n";

        $information = [
            'fullAddress' => rand(100, 9999) . ' Main Street, Anytown, USA',
            'eventDate' => $eventDate,
            'startTime' => $startTime,
            'entertainmentStartTime' => $startTime,
            'birthdayChildName' => $customer['firstName'] . ' Jr.',
            'childAge' => rand(1, 12),
            'ageRange' => $step5Data['kids']['min_age'] . '-' . $step5Data['kids']['max_age'],
            'songRequests' => 'Happy Birthday, Baby Shark',
            'happyBirthdayRequest' => rand(0, 1) ? 'yes' : 'no',
            'instructions' => 'Please ring the doorbell upon arrival.',
            'isValid' => true
        ];

        return [
            'customer' => $step5Data['customer'],
            'zipcode' => $step5Data['zipcode'],
            'service' => [
                'id' => $step5Data['service']->id,
                'amount' => (float) $step5Data['service']->amount,
                'extra_child_fee' => (float) ($step5Data['service']->extra_child_fee ?? 0),
                'performers_count' => $step5Data['service']->performers_count,
                'children_count' => 40,
                'min_duration_hours' => $step5Data['hours']['hours']
            ],
            'kids' => $step5Data['kids'],
            'hours' => $step5Data['hours'],
            'addons' => $step5Data['addons'],
            'information' => $information
        ];
    }
}
