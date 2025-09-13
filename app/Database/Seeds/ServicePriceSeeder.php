<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use Ramsey\Uuid\Uuid;

class ServicePriceSeeder extends Seeder
{
    public function run()
    {
        // Obtener IDs reales de condados y servicios
        $miamiDadeCounty = $this->db->table('counties')->where('name', 'Miami-Dade County')->get()->getRow();
        $browardCounty = $this->db->table('counties')->where('name', 'Broward County')->get()->getRow();
        
        $classicJam = $this->db->table('services')->where('name', 'Classic Jam')->get()->getRow();
        $erasJam = $this->db->table('services')->where('name', 'Eras Jam')->get()->getRow(); // Para el ejemplo

        $servicePrices = [
            // Miami-Dade County - Classic Jam
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $classicJam->id,
                'county_id' => $miamiDadeCounty->id,
                'performers_count' => 1,
                'price_type' => 'standard',
                'amount' => 350.00,
                'max_children' => 10, // 👈 NUEVO CAMPO
                'extra_child_fee' => 75.00, // 👈 NUEVO CAMPO
                'min_duration_hours' => 1,
                'is_available' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $classicJam->id,
                'county_id' => $miamiDadeCounty->id,
                'performers_count' => 1,
                'price_type' => 'jukebox',
                'amount' => 325.00,
                'max_children' => 10, // 👈 NUEVO CAMPO
                'extra_child_fee' => 75.00, // 👈 NUEVO CAMPO
                'min_duration_hours' => 1,
                'is_available' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $classicJam->id,
                'county_id' => $miamiDadeCounty->id,
                'performers_count' => 2,
                'price_type' => 'standard',
                'amount' => 475.00,
                'max_children' => 10, // 👈 NUEVO CAMPO
                'extra_child_fee' => 75.00, // 👈 NUEVO CAMPO
                'min_duration_hours' => 1,
                'is_available' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $classicJam->id,
                'county_id' => $miamiDadeCounty->id,
                'performers_count' => 2,
                'price_type' => 'jukebox',
                'amount' => 450.00,
                'max_children' => 10, // 👈 NUEVO CAMPO
                'extra_child_fee' => null, // 👈 NUEVO CAMPO
                'min_duration_hours' => 1,
                'is_available' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],

            // Broward County - Classic Jam
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $classicJam->id,
                'county_id' => $browardCounty->id,
                'performers_count' => 1,
                'price_type' => 'standard',
                'amount' => 350.00,
                'max_children' => 10, // 👈 NUEVO CAMPO
                'extra_child_fee' => null, // 👈 NUEVO CAMPO
                'min_duration_hours' => 1,
                'is_available' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $classicJam->id,
                'county_id' => $browardCounty->id,
                'performers_count' => 2,
                'price_type' => 'standard',
                'amount' => 475.00,
                'max_children' => 10, // 👈 NUEVO CAMPO
                'extra_child_fee' => null, // 👈 NUEVO CAMPO
                'min_duration_hours' => 1,
                'is_available' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],

            // 👇 EJEMPLO PARA ERAS JAM (con el cargo de $75)
            [
                'id' => Uuid::uuid4()->toString(),
                'service_id' => $erasJam->id, // Asegúrate de tener este servicio creado
                'county_id' => $miamiDadeCounty->id,
                'performers_count' => 1,
                'price_type' => 'standard',
                'amount' => 400.00, // Precio base diferente
                'max_children' => 24, // 👈 Límite para Eras Jam
                'extra_child_fee' => 75.00, // 👈 Cargo de $75 por niño extra
                'min_duration_hours' => 1,
                'is_available' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ]
        ];

        $this->db->table('service_prices')->insertBatch($servicePrices);
        
        echo "✅ Precios de servicios insertados: " . count($servicePrices) . " registros\n";
        echo "   - Relacionados con condados: Miami-Dade County, Broward County\n";
        echo "   - Incluye nuevos campos: max_children y extra_child_fee\n";
    }
}