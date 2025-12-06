<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use Ramsey\Uuid\Uuid;

class AddonSeeder extends Seeder
{
    public function run()
    {
        // Limpiar tablas
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $this->db->table('reservation_addons')->truncate();
        $this->db->table('addons')->truncate();
        $this->db->table('type_addons')->truncate();
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');

        // ===== TIPOS DE ADDONS =====
        $typeAdditionalTime = Uuid::uuid4()->toString();
        $typeJukeboxLive = Uuid::uuid4()->toString();
        $typeCustomSong = Uuid::uuid4()->toString();
        $typeDecor = Uuid::uuid4()->toString();
        $typeFacePainting = Uuid::uuid4()->toString();

        $typeAddons = [
            [
                'id' => $typeAdditionalTime,
                'name' => 'Additional Time',
                'description' => 'Keep the jam going for a full hour! Add 15 minutes to your jam.',
                'image' => '/img/addons/additional-time.jpg',
                'is_active' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ],
            [
                'id' => $typeJukeboxLive,
                'name' => 'Jukebox Live',
                'description' => 'Our Jukebox Live option provides family friendly background music to entertain all family and friends! We can split it up as you like around the timing for the interactive set. Enjoy a variety of genres, including pop, rock, showtunes, reggae, and folk. A sound system is included.',
                'image' => '/img/addons/jukebox-live.jpg',
                'is_active' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ],
            [
                'id' => $typeCustomSong,
                'name' => 'Custom Song',
                'description' => "This is a song composed specially for your child. We'll be in touch to learn more about your child, and one of our amazing team members will write and record a song for them, also to be performed live at the event!",
                'image' => '/img/addons/custom-song.jpg',
                'is_active' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ],
            [
                'id' => $typeDecor,
                'name' => 'Decor',
                'description' => 'We work with the best in the business and are excited to connect you with an exceptional decor artist who will make your event truly unforgettable.',
                'image' => '/img/addons/decor.jpg',
                'is_active' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ],
            [
                'id' => $typeFacePainting,
                'name' => 'Face Painting, Glitter Tattoos, Balloon Art',
                'description' => 'Add sparkle and excitement to any event with stunning face and body painting or amazing balloon art! Perfect for birthdays, special occasions, corporate events, and more. We can connect you with a talented artist who will bring creativity and fun to your celebration.',
                'image' => '/img/addons/face-painting.jpg',
                'is_active' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ],
        ];

        $this->db->table('type_addons')->insertBatch($typeAddons);
        echo "✅ Tipos de addons creados: " . count($typeAddons) . "\n";

        // ===== ADDONS =====
        $addons = [
            // Additional Time
            [
                'id' => Uuid::uuid4()->toString(),
                'type_addon_id' => $typeAdditionalTime,
                'name' => '15 minutes (1 performer)',
                'base_price' => 50.00,
                'is_active' => true,
                'is_referral_service' => false,
                'estimated_duration_minutes' => 15,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type_addon_id' => $typeAdditionalTime,
                'name' => '15 minutes (2 performers)',
                'base_price' => 80.00,
                'is_active' => true,
                'is_referral_service' => false,
                'estimated_duration_minutes' => 15,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ],
            // Jukebox Live
            [
                'id' => Uuid::uuid4()->toString(),
                'type_addon_id' => $typeJukeboxLive,
                'name' => '1 hour, 1 performer',
                'base_price' => 375.00,
                'is_active' => true,
                'is_referral_service' => false,
                'estimated_duration_minutes' => 60,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type_addon_id' => $typeJukeboxLive,
                'name' => '1 hour, 2 performers',
                'base_price' => 500.00,
                'is_active' => true,
                'is_referral_service' => false,
                'estimated_duration_minutes' => 60,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type_addon_id' => $typeJukeboxLive,
                'name' => '2 hours, 1 performer',
                'base_price' => 650.00,
                'is_active' => true,
                'is_referral_service' => false,
                'estimated_duration_minutes' => 120,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type_addon_id' => $typeJukeboxLive,
                'name' => '2 hours, 2 performers',
                'base_price' => 850.00,
                'is_active' => true,
                'is_referral_service' => false,
                'estimated_duration_minutes' => 120,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ],
            // Custom Song (referral)
            [
                'id' => Uuid::uuid4()->toString(),
                'type_addon_id' => $typeCustomSong,
                'name' => 'Custom Song',
                'base_price' => 0.00,
                'is_active' => true,
                'is_referral_service' => true,
                'estimated_duration_minutes' => null,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ],
            // Decor (referral)
            [
                'id' => Uuid::uuid4()->toString(),
                'type_addon_id' => $typeDecor,
                'name' => 'Decor',
                'base_price' => 0.00,
                'is_active' => true,
                'is_referral_service' => true,
                'estimated_duration_minutes' => null,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ],
            // Face Painting, Glitter Tattoos, Balloon Art (referral)
            [
                'id' => Uuid::uuid4()->toString(),
                'type_addon_id' => $typeFacePainting,
                'name' => 'Face Painting, Glitter Tattoos, Balloon Art',
                'base_price' => 0.00,
                'is_active' => true,
                'is_referral_service' => true,
                'estimated_duration_minutes' => null,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ],
        ];

        $this->db->table('addons')->insertBatch($addons);
        echo "✅ Addons creados: " . count($addons) . "\n";

        echo "\n🎉 AddonSeeder completado!\n";
        echo "   - Additional Time: 2 opciones\n";
        echo "   - Jukebox Live: 4 opciones\n";
        echo "   - Custom Song: 1 (referral)\n";
        echo "   - Decor: 1 (referral)\n";
        echo "   - Face Painting, Glitter Tattoos, Balloon Art: 1 (referral)\n";
    }
}
