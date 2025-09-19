<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use Ramsey\Uuid\Uuid;

class ChildrenAgeRangesSeeder extends Seeder
{
    public function run()
    {
        // Verificar que existan precios de servicios primero
        $servicePrices = $this->db->table('service_prices')->get()->getResult();

        if (empty($servicePrices)) {
            echo "❌ Error: No se encontraron precios de servicios. Ejecuta primero ServicePriceSeeder.\n";
            return;
        }

        $ageRanges = [];

        foreach ($servicePrices as $servicePrice) {
            // Rangos específicos basados en el tipo de servicio
            if (strpos($servicePrice->notes, 'Máximo 10 niños') !== false) {
                // Para servicios con máximo 10 niños (probablemente Classic Jam)
                $ageRanges[] = [
                    'id' => Uuid::uuid4()->toString(),
                    'service_price_id' => $servicePrice->id,
                    'min_age' => 3,
                    'max_age' => 10,
                    'is_active' => 1,
                    'created_at' => Time::now(),
                    'updated_at' => Time::now()
                ];
            } elseif (strpos($servicePrice->notes, 'Máximo 24 niños') !== false) {
                // Para servicios con máximo 24 niños (probablemente Eras Jam)
                $ageRanges[] = [
                    'id' => Uuid::uuid4()->toString(),
                    'service_price_id' => $servicePrice->id,
                    'min_age' => 5,
                    'max_age' => 12,
                    'is_active' => 1,
                    'created_at' => Time::now(),
                    'updated_at' => Time::now()
                ];
            } else {
                // Rango por defecto para otros servicios
                $ageRanges[] = [
                    'id' => Uuid::uuid4()->toString(),
                    'service_price_id' => $servicePrice->id,
                    'min_age' => 4,
                    'max_age' => 12,
                    'is_active' => 1,
                    'created_at' => Time::now(),
                    'updated_at' => Time::now()
                ];
            }
        }

        // Insertar los datos
        $this->db->table('children_age_ranges')->insertBatch($ageRanges);
    }
}
