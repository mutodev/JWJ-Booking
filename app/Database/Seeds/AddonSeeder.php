<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use Ramsey\Uuid\Uuid;

class AddonSeeder extends Seeder
{
    public function run()
    {
        $addons = [
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Glitter Tattoos',
                'description' => 'Tatuajes temporales con brillantina para niños. Incluye diseño y aplicación.',
                'base_price' => 75.00,
                'is_active' => true,
                'estimated_duration_minutes' => 30,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Photo Booth',
                'description' => 'Cabina fotográfica con props divertidos. Incluye 20 fotos impresas y digitales.',
                'base_price' => 150.00,
                'is_active' => true,
                'estimated_duration_minutes' => 60,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Face Painting',
                'description' => 'Pintura de caras artística para niños. Diseños temáticos disponibles.',
                'base_price' => 50.00,
                'is_active' => true,
                'estimated_duration_minutes' => 45,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Custom Backdrop',
                'description' => 'Fondo personalizado para fotos con tema de la fiesta. Incluye instalación.',
                'base_price' => 100.00,
                'is_active' => true,
                'estimated_duration_minutes' => 15,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Balloon Artist',
                'description' => 'Artista especializado en crear figuras con globos para los niños.',
                'base_price' => 80.00,
                'is_active' => true,
                'estimated_duration_minutes' => 45,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ]
        ];

        $this->db->table('addons')->insertBatch($addons);

        echo "✅ Add-ons creados exitosamente: " . count($addons) . " registros\n";
        echo "   - Glitter Tattoos ($75)\n";
        echo "   - Photo Booth ($150)\n";
        echo "   - Face Painting ($50)\n";
        echo "   - Custom Backdrop ($100)\n";
        echo "   - Balloon Artist ($80)\n";
    }
}
