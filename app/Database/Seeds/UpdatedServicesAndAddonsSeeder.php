<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class UpdatedServicesAndAddonsSeeder extends Seeder
{
    public function run()
    {
        echo "\n╔════════════════════════════════════════════════════════╗\n";
        echo "║  ACTUALIZANDO SERVICIOS Y ADD-ONS (DOCUMENTO WORD)    ║\n";
        echo "╚════════════════════════════════════════════════════════╝\n\n";

        $this->updateServicesDescriptions();
        $this->updateServicePrices();
        $this->createOrUpdateAddons();

        echo "\n✅ ACTUALIZACIÓN COMPLETADA\n\n";
    }

    private function updateServicesDescriptions()
    {
        echo "🔄 Actualizando descripciones de servicios...\n";

        $services = [
            [
                'name' => 'Classic Jam',
                'description' => 'Get ready for an interactive acoustic jam session that will have everyone up and dancing! It\'s a sing along and dance party for the whole family with classic children\'s songs, rock n\' roll hits, Disney and pop favorites! We include prop play with animal puppets, shakers, scarves, parachute and bubbles.',
                'age_range' => '0-5 years old'
            ],
            [
                'name' => 'Junior Jammer Mashup',
                'description' => 'A blend of the Classic Jam and Big Kids Party: It\'s a high energy, interactive party with music spanning from beloved children\'s songs, pop hits, Disney favorites and Rock n\' Roll! Jamie provides all the fun with props like shakers, puppets, parachute, bubbles AND the dance party with music in the background! *This party is for groups with a wide range of ages in attendance.',
                'age_range' => '0-10 years old'
            ],
            [
                'name' => 'Eras Jam',
                'description' => 'Calling all Swifties! Join us for an unforgettable celebration of Taylor Swift\'s iconic Eras! This high-energy, interactive party brings together children of all ages (and their adults!) for a magical journey through Taylor\'s greatest hits. We\'ll sing, dance, and relive every era with themed props, friendship bracelets, and non-stop fun. Whether you\'re a Fearless fan or all about the Midnights vibes, this party is for every Swiftie ready to Shake It Off and make memories. *This party is best enjoyed by adults and children together. Age range varies and is dependent on the Swiftie attending.',
                'age_range' => 'All ages (Swifties)'
            ],
            [
                'name' => 'Big Kids Party',
                'description' => 'A high energy interactive sing along and dance party for older kids! Jamie opens the party with 15 minutes of games and warm up songs and then turns on the music for a full on dance party! Song requests are welcome and Jamie will do her best to accommodate. (Please note that explicit music is not permitted).',
                'age_range' => '5-10 years old'
            ]
        ];

        foreach ($services as $service) {
            $updated = $this->db->table('services')
                ->where('name', $service['name'])
                ->update([
                    'description' => $service['description'],
                    'age_range' => $service['age_range']
                ]);

            if ($updated) {
                echo "  ✓ Actualizado: {$service['name']}\n";
            } else {
                echo "  ⚠ No se encontró: {$service['name']}\n";
            }
        }

        echo "\n";
    }

    private function updateServicePrices()
    {
        echo "💰 Actualizando precios de servicios...\n";

        $db = \Config\Database::connect();

        // Classic Jam - 1 performer = $350
        $classicJam = $db->table('services')->where('name', 'Classic Jam')->get()->getRow();
        if ($classicJam) {
            $updated = $db->table('service_prices')
                ->where('service_id', $classicJam->id)
                ->where('performers_count', 1)
                ->update(['amount' => 350.00]);
            echo "  ✓ Classic Jam (1 performer): $350 ($updated registros)\n";
        }

        // Classic Jam - 2 performers = $525
        if ($classicJam) {
            $updated = $db->table('service_prices')
                ->where('service_id', $classicJam->id)
                ->where('performers_count', 2)
                ->update(['amount' => 525.00]);
            echo "  ✓ Classic Jam (2 performers): $525 ($updated registros)\n";
        }

        // Junior Jammer Mashup - 1 performer = $400
        $juniorJammer = $db->table('services')->where('name', 'Junior Jammer Mashup')->get()->getRow();
        if ($juniorJammer) {
            $updated = $db->table('service_prices')
                ->where('service_id', $juniorJammer->id)
                ->where('performers_count', 1)
                ->update(['amount' => 400.00]);
            echo "  ✓ Junior Jammer Mashup (1 performer): $400 ($updated registros)\n";
        }

        // Eras Jam - 1 performer = $450
        $erasJam = $db->table('services')->where('name', 'Eras Jam')->get()->getRow();
        if ($erasJam) {
            $updated = $db->table('service_prices')
                ->where('service_id', $erasJam->id)
                ->where('performers_count', 1)
                ->update(['amount' => 450.00]);
            echo "  ✓ Eras Jam (1 performer): $450 ($updated registros)\n";
        }

        // Big Kids Party - 1 performer = $400
        $bigKidsParty = $db->table('services')->where('name', 'Big Kids Party')->get()->getRow();
        if ($bigKidsParty) {
            $updated = $db->table('service_prices')
                ->where('service_id', $bigKidsParty->id)
                ->where('performers_count', 1)
                ->update(['amount' => 400.00]);
            echo "  ✓ Big Kids Party (1 performer): $400 ($updated registros)\n";
        }

        echo "\n";
    }

    private function createOrUpdateAddons()
    {
        echo "🎁 Creando/actualizando add-ons...\n";

        $db = \Config\Database::connect();

        // 1. Actualizar Jukebox Live
        $jukeboxUpdated = $db->table('addons')
            ->where('name', 'Jukebox 1 Hour')
            ->update([
                'description' => 'Want to keep the party going? Add an extra hour of music to keep the dance floor packed! Choose from our curated playlists or request your favorite songs. Perfect for extending the celebration and ensuring non-stop fun for all your guests.'
            ]);

        if ($jukeboxUpdated) {
            echo "  ✓ Jukebox Live descripción actualizada\n";
        }

        // 2. Additional Time - 1 Performer (15 minutes)
        $additionalTime1p = $db->table('addons')->where('name', 'Additional Time (1 Performer)')->get()->getRow();

        if (!$additionalTime1p) {
            $db->table('addons')->insert([
                'id' => $this->generateUUID(),
                'name' => 'Additional Time (1 Performer)',
                'description' => 'Need a little more time? Add 15-minute increments to your party with 1 performer!',
                'base_price' => 75.00,
                'is_active' => true,
                'estimated_duration_minutes' => 15,
                'price_type' => 'standard',
                'created_at' => Time::now()
            ]);
            echo "  ✓ Additional Time (1 Performer) CREADO: $75\n";
        } else {
            echo "  ⚠ Additional Time (1 Performer) ya existe\n";
        }

        // 3. Additional Time - 2 Performers (15 minutes)
        $additionalTime2p = $db->table('addons')->where('name', 'Additional Time (2 Performers)')->get()->getRow();

        if (!$additionalTime2p) {
            $db->table('addons')->insert([
                'id' => $this->generateUUID(),
                'name' => 'Additional Time (2 Performers)',
                'description' => 'Need a little more time? Add 15-minute increments to your party with 2 performers!',
                'base_price' => 112.50,
                'is_active' => true,
                'estimated_duration_minutes' => 15,
                'price_type' => 'standard',
                'created_at' => Time::now()
            ]);
            echo "  ✓ Additional Time (2 Performers) CREADO: $112.50\n";
        } else {
            echo "  ⚠ Additional Time (2 Performers) ya existe\n";
        }

        // 4. Custom Song (referral service)
        $customSong = $db->table('addons')->where('name', 'Custom Song')->get()->getRow();

        if (!$customSong) {
            $db->table('addons')->insert([
                'id' => $this->generateUUID(),
                'name' => 'Custom Song',
                'description' => 'Make your celebration truly unique with a personalized song! Our talented team will create a custom song tailored to your event. This is a referral service - we\'ll connect you with our partner to bring your musical vision to life.',
                'base_price' => 0.00, // Referral service
                'is_active' => true,
                'is_referral_service' => true,
                'created_at' => Time::now()
            ]);
            echo "  ✓ Custom Song CREADO (servicio de referencia)\n";
        } else {
            echo "  ⚠ Custom Song ya existe\n";
        }

        // 5. Decor (referral service)
        $decor = $db->table('addons')->where('name', 'Decor')->get()->getRow();

        if (!$decor) {
            $db->table('addons')->insert([
                'id' => $this->generateUUID(),
                'name' => 'Decor',
                'description' => 'Transform your party space into a magical wonderland! We partner with professional decorators who can bring your theme to life with stunning decorations, backdrops, and more. This is a referral service - we\'ll connect you with our trusted decor partners.',
                'base_price' => 0.00, // Referral service
                'is_active' => true,
                'is_referral_service' => true,
                'created_at' => Time::now()
            ]);
            echo "  ✓ Decor CREADO (servicio de referencia)\n";
        } else {
            echo "  ⚠ Decor ya existe\n";
        }

        // 6. Actualizar Face Painting, Glitter Tattoos, Balloon Art a referral services
        $referralAddons = ['Face Painting', 'Glitter Tattoos', 'Balloon Art'];
        foreach ($referralAddons as $addonName) {
            $exists = $db->table('addons')->where('name', $addonName)->get()->getRow();
            if ($exists) {
                $db->table('addons')
                    ->where('name', $addonName)
                    ->update([
                        'is_referral_service' => true,
                        'base_price' => 0.00
                    ]);
                echo "  ✓ $addonName marcado como servicio de referencia\n";
            }
        }

        echo "\n";
    }

    private function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
