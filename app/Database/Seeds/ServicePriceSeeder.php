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
                'range_age' => '1 - 10',
                'is_available' => true,
                'notes' => 'Máximo 10 niños, fee extra por niño adicional: $75.00',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $classicJam->id,
                'county_id' => $miamiDadeCounty->id,
                'performers_count' => 2,
                'amount' => 475.00,
                'range_age' => '1 - 10',
                'is_available' => true,
                'notes' => 'Máximo 10 niños, fee extra por niño adicional: $75.00',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $classicJam->id,
                'county_id' => $browardCounty->id,
                'performers_count' => 1,
                'amount' => 350.00,
                'range_age' => '1 - 10',
                'is_available' => true,
                'notes' => 'Máximo 10 niños, sin fee extra por niño adicional',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $classicJam->id,
                'county_id' => $browardCounty->id,
                'performers_count' => 2,
                'amount' => 475.00,
                'range_age' => '1 - 10',
                'is_available' => true,
                'notes' => 'Máximo 10 niños, sin fee extra por niño adicional',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $erasJam->id,
                'county_id' => $miamiDadeCounty->id,
                'performers_count' => 1,
                'amount' => 400.00,
                'range_age' => '1 - 10',
                'is_available' => true,
                'notes' => 'Máximo 24 niños, fee extra por niño adicional: $75.00',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $erasJam->id,
                'county_id' => $miamiDadeCounty->id,
                'performers_count' => 2,
                'amount' => 525.00,
                'range_age' => '1 - 10',
                'is_available' => true,
                'notes' => 'Máximo 24 niños, fee extra por niño adicional: $75.00',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $erasJam->id,
                'county_id' => $browardCounty->id,
                'performers_count' => 1,
                'amount' => 400.00,
                'range_age' => '1 - 10',
                'is_available' => true,
                'notes' => 'Máximo 24 niños, sin fee extra por niño adicional',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $erasJam->id,
                'county_id' => $browardCounty->id,
                'performers_count' => 2,
                'amount' => 525.00,
                'range_age' => '1 - 10',
                'is_available' => true,
                'notes' => 'Máximo 24 niños, sin fee extra por niño adicional',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ]
        ];

        // Insertar los datos
        $this->db->table('service_prices')->insertBatch($servicePrices);

        echo "✅ Precios de servicios insertados: " . count($servicePrices) . " registros\n";
        echo "   - Relacionados con condados: Miami-Dade County, Broward County\n";
        echo "   - Información de máximo de niños y fees extras incluida en el campo 'notes'\n";
    }
}