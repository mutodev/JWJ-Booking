<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use Ramsey\Uuid\Uuid;

class DurationsSeeder extends Seeder
{
    public function run()
    {
        // Verificar que existan precios de servicios primero
        $servicePrices = $this->db->table('service_prices')->get()->getResult();

        if (empty($servicePrices)) {
            echo "❌ Error: No se encontraron precios de servicios. Ejecuta primero ServicePriceSeeder.\n";
            return;
        }

        $durations = [];

        // Duraciónes comunes en minutos
        $commonDurations = [60, 90, 120, 150];

        foreach ($servicePrices as $servicePrice) {
            foreach ($commonDurations as $minutes) {
                $durations[] = [
                    'id' => Uuid::uuid4()->toString(),
                    'service_price_id' => $servicePrice->id,
                    'minutes' => $minutes,
                    'is_active' => 1,
                    'created_at' => Time::now(),
                    'updated_at' => Time::now()
                ];
            }
        }

        // Insertar los datos
        $this->db->table('durations')->insertBatch($durations);
    }
}
