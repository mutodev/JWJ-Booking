<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Ramsey\Uuid\Uuid;

/**
 * Seeder para insertar los Jams (Services) y sus precios por county
 * basado en el archivo RESERVAS ONLINE.xlsx
 *
 * Este seeder:
 * - Elimina todos los service_prices existentes
 * - Elimina todos los services existentes (excepto Test Services)
 * - Inserta los 5 servicios/jams del Excel
 * - Inserta los precios base y travel fees por county
 *
 * Ejecutar con: php spark db:seed JamsAndPricesSeeder
 */
class JamsAndPricesSeeder extends Seeder
{
    private int $errors = 0;
    private int $skipped = 0;

    // IDs fijos para los servicios (para mantener consistencia)
    private array $serviceIds = [
        'Classic Jam' => 'c0579b66-91dd-4674-8bcd-d6cb76ac9016',
        'Classic Jam Duo' => 'd1680c77-a2ee-5785-9cde-e7dc87bd0127',
        'Junior Jammer Mashup' => '22d8a962-f413-441b-9c13-58a169ff06a1',
        'Eras Jam' => '5f91c352-2b35-48a3-ada1-b62e28c6dfb4',
        'Big Kids Party' => 'e82a5d2e-8a6f-4bf3-a8a0-539e29478fcd',
    ];

    // Precios base para todos los servicios
    private array $basePrices = [
        'Classic Jam' => ['performers' => 1, 'base' => 350],
        'Classic Jam Duo' => ['performers' => 2, 'base' => 475],
        'Junior Jammer Mashup' => ['performers' => 2, 'base' => 525],
        'Eras Jam' => ['performers' => 2, 'base' => 675],
        'Big Kids Party' => ['performers' => 2, 'base' => 675],
    ];

    public function run()
    {
        echo "🚀 Iniciando JamsAndPricesSeeder...\n\n";

        // PASO 1: Limpiar datos existentes
        echo "🗑️ PASO 1: Limpiando datos existentes...\n";
        $this->cleanExistingData();
        echo "✅ Datos limpiados\n\n";

        // PASO 2: Insertar servicios/jams
        echo "🎵 PASO 2: Insertando servicios (Jams)...\n";
        $servicesInserted = $this->insertServices();
        echo "✅ Servicios insertados: {$servicesInserted}\n\n";

        // PASO 3: Insertar precios por county
        echo "💰 PASO 3: Insertando precios por county...\n";
        $pricesInserted = $this->insertServicePrices();
        echo "✅ Precios insertados: {$pricesInserted}\n\n";

        echo "🎉 JamsAndPricesSeeder completado!\n";
        echo "   Total servicios: {$servicesInserted}\n";
        echo "   Total precios: {$pricesInserted}\n";

        if ($this->skipped > 0) {
            echo "   ⏭️ Registros omitidos: {$this->skipped}\n";
        }
        if ($this->errors > 0) {
            echo "   ⚠️ Errores: {$this->errors}\n";
        }
    }

    private function cleanExistingData(): void
    {
        // Desactivar verificación de llaves foráneas temporalmente
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

        // 1. Limpiar reservation_addons (depende de reservations)
        $this->db->table('reservation_addons')->truncate();
        echo "   - reservation_addons limpiado\n";

        // 2. Limpiar reservations (depende de service_prices)
        $this->db->table('reservations')->truncate();
        echo "   - reservations limpiado\n";

        // 3. Limpiar children_age_ranges (depende de service_prices)
        $this->db->table('children_age_ranges')->truncate();
        echo "   - children_age_ranges limpiado\n";

        // 4. Limpiar durations (depende de service_prices)
        $this->db->table('durations')->truncate();
        echo "   - durations limpiado\n";

        // 5. Limpiar service_prices (depende de services)
        $this->db->table('service_prices')->truncate();
        echo "   - service_prices limpiado\n";

        // 6. Limpiar services (solo los que vamos a reinsertar, no los de test)
        $this->db->table('services')
            ->notLike('name', 'Test')
            ->delete();
        echo "   - services limpiado\n";

        // Reactivar verificación de llaves foráneas
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function insertServices(): int
    {
        $services = [
            [
                'id' => $this->serviceIds['Classic Jam'],
                'name' => 'Classic Jam',
                'description' => 'Classic Jam experience with 1 performer. Perfect for birthday parties and events.',
                'is_active' => true,
            ],
            [
                'id' => $this->serviceIds['Classic Jam Duo'],
                'name' => 'Classic Jam Duo',
                'description' => 'Classic Jam experience with 2 performers for an enhanced experience.',
                'is_active' => true,
            ],
            [
                'id' => $this->serviceIds['Junior Jammer Mashup'],
                'name' => 'Junior Jammer Mashup',
                'description' => 'Special mashup experience for younger kids with 2 performers.',
                'is_active' => true,
            ],
            [
                'id' => $this->serviceIds['Eras Jam'],
                'name' => 'Eras Jam',
                'description' => 'Journey through different music eras with 2 performers.',
                'is_active' => true,
            ],
            [
                'id' => $this->serviceIds['Big Kids Party'],
                'name' => 'Big Kids Party',
                'description' => 'High-energy party experience for older kids with 2 performers.',
                'is_active' => true,
            ],
        ];

        $count = 0;
        foreach ($services as $service) {
            try {
                $exists = $this->db->table('services')->where('id', $service['id'])->countAllResults();
                if ($exists > 0) {
                    $this->skipped++;
                    continue;
                }

                $service['created_at'] = date('Y-m-d H:i:s');
                $service['updated_at'] = date('Y-m-d H:i:s');

                $this->db->table('services')->insert($service);
                $count++;
                echo "   + {$service['name']}\n";
            } catch (\Exception $e) {
                $this->errors++;
                echo "   ⚠️ Error insertando {$service['name']}: " . $e->getMessage() . "\n";
            }
        }

        return $count;
    }

    private function insertServicePrices(): int
    {
        $count = 0;

        // Definir los travel fees por zona/county basado en el Excel
        $pricesByArea = $this->getPricesByArea();

        foreach ($pricesByArea as $areaName => $counties) {
            echo "   📍 {$areaName}\n";

            foreach ($counties as $countyConfig) {
                $countyNames = $countyConfig['counties'];
                $fees = $countyConfig['fees'];
                $minHours = $countyConfig['min_hours'] ?? 1;
                $isPackage = $countyConfig['is_package'] ?? false;

                foreach ($countyNames as $countyName) {
                    // Buscar el county
                    $county = $this->db->table('counties')
                        ->where('name', $countyName)
                        ->get()
                        ->getRow();

                    if (!$county) {
                        echo "      ⚠️ County no encontrado: {$countyName}\n";
                        continue;
                    }

                    // Insertar precios para cada servicio
                    foreach ($fees as $serviceName => $travelFee) {
                        if ($travelFee === null || $travelFee === 'no disponible') {
                            continue; // Servicio no disponible en esta zona
                        }

                        $serviceId = $this->serviceIds[$serviceName] ?? null;
                        if (!$serviceId) continue;

                        $basePrice = $this->basePrices[$serviceName]['base'];
                        $performers = $this->basePrices[$serviceName]['performers'];

                        // Si es paquete (2hrs mínimo), el precio ya incluye todo
                        $finalAmount = $isPackage ? $travelFee : ($basePrice + $travelFee);

                        try {
                            // Verificar si ya existe
                            $exists = $this->db->table('service_prices')
                                ->where('service_id', $serviceId)
                                ->where('county_id', $county->id)
                                ->where('performers_count', $performers)
                                ->countAllResults();

                            if ($exists > 0) {
                                $this->skipped++;
                                continue;
                            }

                            $notes = '';
                            if ($minHours > 1) {
                                $notes = "Mínimo {$minHours} horas de servicio. ";
                            }
                            if ($isPackage) {
                                $notes .= "Precio de paquete completo incluyendo sistema de sonido.";
                            }

                            $this->db->table('service_prices')->insert([
                                'id' => Uuid::uuid4()->toString(),
                                'service_id' => $serviceId,
                                'county_id' => $county->id,
                                'performers_count' => $performers,
                                'img' => '/img/default.jpg',
                                'amount' => $finalAmount,
                                'is_available' => 1,
                                'notes' => $notes ?: null,
                                'created_at' => date('Y-m-d H:i:s'),
                            ]);
                            $count++;
                        } catch (\Exception $e) {
                            $this->errors++;
                        }
                    }
                }
            }
        }

        return $count;
    }

    private function getPricesByArea(): array
    {
        return [
            // =====================
            // MIAMI, BOCA AND PALM BEACH
            // =====================
            'MIAMI, BOCA AND PALM BEACH' => [
                // Broward County - $0 travel fee
                [
                    'counties' => ['Broward County'],
                    'fees' => [
                        'Classic Jam' => 0,
                        'Classic Jam Duo' => 0,
                        'Junior Jammer Mashup' => 0,
                        'Eras Jam' => 0,
                        'Big Kids Party' => 0,
                    ],
                ],
                // Miami-Dade County - $0 travel fee
                [
                    'counties' => ['Miami-Dade County'],
                    'fees' => [
                        'Classic Jam' => 0,
                        'Classic Jam Duo' => 0,
                        'Junior Jammer Mashup' => 0,
                        'Eras Jam' => 0,
                        'Big Kids Party' => 0,
                    ],
                ],
                // Palm Beach County - $50/$75 travel fee
                [
                    'counties' => ['Palm Beach County'],
                    'fees' => [
                        'Classic Jam' => 50,
                        'Classic Jam Duo' => 75,
                        'Junior Jammer Mashup' => 75,
                        'Eras Jam' => 75,
                        'Big Kids Party' => 75,
                    ],
                ],
            ],

            // =====================
            // NEW YORK
            // =====================
            'NEW YORK' => [
                // New York County - $0 travel fee
                [
                    'counties' => ['New York County'],
                    'fees' => [
                        'Classic Jam' => 0,
                        'Classic Jam Duo' => 0,
                        'Junior Jammer Mashup' => 0,
                        'Eras Jam' => 0,
                        'Big Kids Party' => 0,
                    ],
                ],
                // Queens, Kings County - $50/$75 travel fee
                [
                    'counties' => ['Queens County', 'Kings County'],
                    'fees' => [
                        'Classic Jam' => 50,
                        'Classic Jam Duo' => 75,
                        'Junior Jammer Mashup' => 75,
                        'Eras Jam' => 75,
                        'Big Kids Party' => 75,
                    ],
                ],
                // Richmond, Bronx County - $150/$200 travel fee
                [
                    'counties' => ['Richmond County', 'Bronx County'],
                    'fees' => [
                        'Classic Jam' => 150,
                        'Classic Jam Duo' => 200,
                        'Junior Jammer Mashup' => 200,
                        'Eras Jam' => 200,
                        'Big Kids Party' => 200,
                    ],
                ],
                // Westchester, Rockland, Putnam, Nassau - $190/$250 travel fee
                [
                    'counties' => ['Westchester County', 'Rockland County', 'Putnam County', 'Nassau County'],
                    'fees' => [
                        'Classic Jam' => 190,
                        'Classic Jam Duo' => 250,
                        'Junior Jammer Mashup' => 250,
                        'Eras Jam' => 250,
                        'Big Kids Party' => 250,
                    ],
                ],
                // Orange, Dutchess, Suffolk - 2hrs mínimo (paquete)
                [
                    'counties' => ['Orange County', 'Dutchess County', 'Suffolk County'],
                    'min_hours' => 2,
                    'is_package' => true,
                    'fees' => [
                        'Classic Jam' => 1075,
                        'Classic Jam Duo' => 1675,
                        'Junior Jammer Mashup' => null, // No disponible
                        'Eras Jam' => 1825,
                        'Big Kids Party' => null, // No disponible
                    ],
                ],
            ],

            // =====================
            // NEW JERSEY
            // =====================
            'NEW JERSEY' => [
                // Hudson County (Hoboken, Jersey City, Weehawken) - $150/$200 travel fee
                [
                    'counties' => ['Hudson County'],
                    'fees' => [
                        'Classic Jam' => 150,
                        'Classic Jam Duo' => 200,
                        'Junior Jammer Mashup' => 200,
                        'Eras Jam' => 200,
                        'Big Kids Party' => 200,
                    ],
                ],
                // Bergen, Essex, Middlesex, Monmouth, Passaic, Union - $190/$250 travel fee
                [
                    'counties' => ['Bergen County', 'Essex County', 'Middlesex County', 'Monmouth County', 'Passaic County', 'Union County'],
                    'fees' => [
                        'Classic Jam' => 190,
                        'Classic Jam Duo' => 250,
                        'Junior Jammer Mashup' => 250,
                        'Eras Jam' => 250,
                        'Big Kids Party' => 250,
                    ],
                ],
                // Burlington, Camden, Hunterdon, Mercer, Morris, Ocean, Somerset - 2hrs mínimo (paquete)
                [
                    'counties' => ['Burlington County', 'Camden County', 'Hunterdon County', 'Mercer County', 'Morris County', 'Ocean County', 'Somerset County'],
                    'min_hours' => 2,
                    'is_package' => true,
                    'fees' => [
                        'Classic Jam' => 1075,
                        'Classic Jam Duo' => 1675,
                        'Junior Jammer Mashup' => null,
                        'Eras Jam' => 1825,
                        'Big Kids Party' => null,
                    ],
                ],
            ],

            // =====================
            // LOS ANGELES
            // =====================
            'LOS ANGELES' => [
                // Los Angeles County (hasta 20 millas) - $0 travel fee
                [
                    'counties' => ['Los Angeles County'],
                    'fees' => [
                        'Classic Jam' => 0,
                        'Classic Jam Duo' => 0,
                        'Junior Jammer Mashup' => 0,
                        'Eras Jam' => 0,
                        'Big Kids Party' => 0,
                    ],
                ],
                // Ventura County - $50/$75 travel fee
                [
                    'counties' => ['Ventura County'],
                    'fees' => [
                        'Classic Jam' => 50,
                        'Classic Jam Duo' => 75,
                        'Junior Jammer Mashup' => 75,
                        'Eras Jam' => 75,
                        'Big Kids Party' => 75,
                    ],
                ],
                // Orange County, Riverside - 2hrs mínimo (paquete)
                [
                    'counties' => ['Orange County', 'Riverside County'],
                    'min_hours' => 2,
                    'is_package' => true,
                    'fees' => [
                        'Classic Jam' => 975,
                        'Classic Jam Duo' => 1575,
                        'Junior Jammer Mashup' => null,
                        'Eras Jam' => 1775,
                        'Big Kids Party' => null,
                    ],
                ],
            ],

            // =====================
            // WASHINGTON DC
            // =====================
            'WASHINGTON DC' => [
                // DC, Arlington, Fairfax, Loudoun, Virginia, Maryland - $0 travel fee
                [
                    'counties' => ['Northwest (NW)', 'Northeast (NE)', 'Southwest (SW)', 'Southeast (SE)', 'Virginia', 'Maryland'],
                    'fees' => [
                        'Classic Jam' => 0,
                        'Classic Jam Duo' => 0,
                        'Junior Jammer Mashup' => 0,
                        'Eras Jam' => 0,
                        'Big Kids Party' => 0,
                    ],
                ],
            ],

            // =====================
            // CHICAGO
            // =====================
            'CHICAGO' => [
                // Cook County (Chicago, IL) - $0 travel fee
                [
                    'counties' => ['Chicago, IL'],
                    'fees' => [
                        'Classic Jam' => 0,
                        'Classic Jam Duo' => 0,
                        'Junior Jammer Mashup' => 0,
                        'Eras Jam' => null, // No disponible
                        'Big Kids Party' => null, // No disponible
                    ],
                ],
                // DuPage, Lake, Will - $50/$75 travel fee
                [
                    'counties' => ['DuPage County', 'Lake County', 'Will County'],
                    'fees' => [
                        'Classic Jam' => 50,
                        'Classic Jam Duo' => 75,
                        'Junior Jammer Mashup' => 75,
                        'Eras Jam' => null,
                        'Big Kids Party' => null,
                    ],
                ],
                // Kendall, McHenry, Kane, Grundy, DeKalb, Kankakee - 2hrs mínimo (paquete)
                [
                    'counties' => ['Kendall County', 'McHenry County', 'Kane County', 'Grundy County', 'DeKalb County', 'Kankakee County'],
                    'min_hours' => 2,
                    'is_package' => true,
                    'fees' => [
                        'Classic Jam' => 975,
                        'Classic Jam Duo' => 1575,
                        'Junior Jammer Mashup' => null,
                        'Eras Jam' => null,
                        'Big Kids Party' => null,
                    ],
                ],
            ],

            // =====================
            // NASHVILLE
            // =====================
            'NASHVILLE' => [
                // Davidson County - $0 travel fee
                [
                    'counties' => ['Davidson County'],
                    'fees' => [
                        'Classic Jam' => 0,
                        'Classic Jam Duo' => 0,
                        'Junior Jammer Mashup' => 0,
                        'Eras Jam' => null, // No disponible
                        'Big Kids Party' => null, // No disponible
                    ],
                ],
                // Williamson, Rutherford, Wilson, Sumner, Cheatham, Robertson, Dickson, Maury - $50/$75 travel fee
                [
                    'counties' => ['Williamson County', 'Rutherford County', 'Wilson County', 'Sumner County', 'Cheatham County', 'Robertson County', 'Dickson County', 'Maury County'],
                    'fees' => [
                        'Classic Jam' => 50,
                        'Classic Jam Duo' => 75,
                        'Junior Jammer Mashup' => 75,
                        'Eras Jam' => null,
                        'Big Kids Party' => null,
                    ],
                ],
                // Trousdale County - 2hrs mínimo (paquete)
                [
                    'counties' => ['Trousdale County'],
                    'min_hours' => 2,
                    'is_package' => true,
                    'fees' => [
                        'Classic Jam' => 975,
                        'Classic Jam Duo' => 1575,
                        'Junior Jammer Mashup' => null,
                        'Eras Jam' => null,
                        'Big Kids Party' => null,
                    ],
                ],
            ],

            // =====================
            // SAN FRANCISCO
            // =====================
            'SAN FRANCISCO' => [
                // San Francisco County, Marin County, Oakland - $0 travel fee (solo Classic Jam)
                [
                    'counties' => ['San Francisco County', 'Marin County, CA', 'Oakland, CA'],
                    'fees' => [
                        'Classic Jam' => 0,
                        'Classic Jam Duo' => null, // No disponible
                        'Junior Jammer Mashup' => null,
                        'Eras Jam' => null,
                        'Big Kids Party' => null,
                    ],
                ],
                // San Mateo County - $50 travel fee (solo Classic Jam)
                [
                    'counties' => ['San Mateo County, CA'],
                    'fees' => [
                        'Classic Jam' => 50,
                        'Classic Jam Duo' => null,
                        'Junior Jammer Mashup' => null,
                        'Eras Jam' => null,
                        'Big Kids Party' => null,
                    ],
                ],
                // Santa Clara County - $150 travel fee (solo Classic Jam)
                [
                    'counties' => ['Santa Clara County, CA'],
                    'fees' => [
                        'Classic Jam' => 150,
                        'Classic Jam Duo' => null,
                        'Junior Jammer Mashup' => null,
                        'Eras Jam' => null,
                        'Big Kids Party' => null,
                    ],
                ],
                // Alameda - $0 travel fee (solo Classic Jam)
                [
                    'counties' => ['Alameda, CA'],
                    'fees' => [
                        'Classic Jam' => 0,
                        'Classic Jam Duo' => null,
                        'Junior Jammer Mashup' => null,
                        'Eras Jam' => null,
                        'Big Kids Party' => null,
                    ],
                ],
            ],
        ];
    }
}
