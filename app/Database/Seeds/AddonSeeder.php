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
                'image' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop',
                'price_type' => 'standard',
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
                'image' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?w=400&h=300&fit=crop',
                'price_type' => 'standard',
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
                'image' => 'https://images.unsplash.com/photo-1560114928-40f1f1eb26a0?w=400&h=300&fit=crop',
                'price_type' => 'standard',
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
                'image' => 'https://images.unsplash.com/photo-1464047736614-af63643285bf?w=400&h=300&fit=crop',
                'price_type' => 'standard',
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
                'image' => 'https://images.unsplash.com/photo-1524230507669-5ff97982bb5e?w=400&h=300&fit=crop',
                'price_type' => 'standard',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Jukebox 1 Hour',
                'description' => 'Sistema de música familiar de fondo por 1 hora adicional.',
                'base_price' => 50.00,
                'is_active' => true,
                'estimated_duration_minutes' => 60,
                'image' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=400&h=300&fit=crop',
                'price_type' => 'jukebox',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Jukebox 2 Hours',
                'description' => 'Sistema de música familiar de fondo por 2 horas adicionales.',
                'base_price' => 85.00,
                'is_active' => true,
                'estimated_duration_minutes' => 120,
                'image' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=400&h=300&fit=crop',
                'price_type' => 'jukebox',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ]
        ];

        $this->db->table('addons')->insertBatch($addons);

        echo "✅ Add-ons creados exitosamente: " . count($addons) . " registros\n";
        echo "   - Glitter Tattoos ($75 - standard)\n";
        echo "   - Photo Booth ($150 - standard)\n";
        echo "   - Face Painting ($50 - standard)\n";
        echo "   - Custom Backdrop ($100 - standard)\n";
        echo "   - Balloon Artist ($80 - standard)\n";
        echo "   - Jukebox 1 Hour ($50 - jukebox)\n";
        echo "   - Jukebox 2 Hours ($85 - jukebox)\n";
    }
}
