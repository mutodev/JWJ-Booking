<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use Ramsey\Uuid\Uuid;

class ServicePriceSeeder extends Seeder
{
    public function run()
    {
        // Verificar que existan los condados y servicios primero
        $miamiDadeCounty = $this->db->table('counties')->where('name', 'Miami-Dade County')->get()->getRow();
        $browardCounty = $this->db->table('counties')->where('name', 'Broward County')->get()->getRow();

        $classicJam = $this->db->table('services')->where('name', 'Classic Jam')->get()->getRow();
        $erasJam = $this->db->table('services')->where('name', 'Eras Jam')->get()->getRow();

        // Verificar que todos los registros necesarios existen
        if (!$miamiDadeCounty || !$browardCounty) {
            echo "❌ Error: No se encontraron los condados necesarios. Ejecuta primero CountiesSeeder.\n";
            return;
        }

        if (!$classicJam || !$erasJam) {
            echo "❌ Error: No se encontraron los servicios necesarios. Ejecuta primero ServicesSeeder.\n";
            return;
        }

        $servicePrices = [
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $classicJam->id,
                'county_id' => $miamiDadeCounty->id,
                'performers_count' => 1,
                'amount' => 350.00,
                'travel_fee' => 0.00,
                'range_age' => '1 - 10',
                'is_available' => true,
                'extra_child_fee' => 75.00,
                'notes' => 'Classic Jam con 1 performer - Miami-Dade',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $classicJam->id,
                'county_id' => $miamiDadeCounty->id,
                'performers_count' => 2,
                'amount' => 475.00,
                'travel_fee' => 0.00,
                'range_age' => '1 - 10',
                'is_available' => true,
                'extra_child_fee' => 75.00,
                'notes' => 'Classic Jam con 2 performers - Miami-Dade',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $classicJam->id,
                'county_id' => $browardCounty->id,
                'performers_count' => 1,
                'amount' => 350.00,
                'travel_fee' => 0.00,
                'range_age' => '1 - 10',
                'is_available' => true,
                'extra_child_fee' => 0.00,
                'notes' => 'Classic Jam con 1 performer - Broward',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $classicJam->id,
                'county_id' => $browardCounty->id,
                'performers_count' => 2,
                'amount' => 475.00,
                'travel_fee' => 0.00,
                'range_age' => '1 - 10',
                'is_available' => true,
                'extra_child_fee' => 0.00,
                'notes' => 'Classic Jam con 2 performers - Broward',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $erasJam->id,
                'county_id' => $miamiDadeCounty->id,
                'performers_count' => 1,
                'amount' => 400.00,
                'travel_fee' => 0.00,
                'range_age' => '1 - 10',
                'is_available' => true,
                'extra_child_fee' => 75.00,
                'notes' => 'Eras Jam con 1 performer - Miami-Dade',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $erasJam->id,
                'county_id' => $miamiDadeCounty->id,
                'performers_count' => 2,
                'amount' => 525.00,
                'travel_fee' => 0.00,
                'range_age' => '1 - 10',
                'is_available' => true,
                'extra_child_fee' => 75.00,
                'notes' => 'Eras Jam con 2 performers - Miami-Dade',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $erasJam->id,
                'county_id' => $browardCounty->id,
                'performers_count' => 1,
                'amount' => 400.00,
                'travel_fee' => 0.00,
                'range_age' => '1 - 10',
                'is_available' => true,
                'extra_child_fee' => 0.00,
                'notes' => 'Eras Jam con 1 performer - Broward',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $erasJam->id,
                'county_id' => $browardCounty->id,
                'performers_count' => 2,
                'amount' => 525.00,
                'travel_fee' => 0.00,
                'range_age' => '1 - 10',
                'is_available' => true,
                'extra_child_fee' => 0.00,
                'notes' => 'Eras Jam con 2 performers - Broward',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ]
        ];

        // Insertar los datos
        $this->db->table('service_prices')->insertBatch($servicePrices);

        echo "✅ Precios de servicios insertados: " . count($servicePrices) . " registros\n";
        echo "   - Relacionados con condados: Miami-Dade County, Broward County\n";
        echo "   - Campos incluidos: extra_child_fee, range_age, performers_count\n";
        echo "   - Classic Jam: $350-$475 | Eras Jam: $400-$525\n";
        echo "   - Miami-Dade: $75 fee extra por niño | Broward: sin fee extra\n";
    }
}