<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class PromoCodesSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // Promo Code 1: WELCOME10 - 10% de descuento
        $db->table('promo_codes')->insert([
            'id' => $this->generateUUID(),
            'code' => 'WELCOME10',
            'description' => 'Welcome discount - 10% off your first booking',
            'discount_type' => 'percentage',
            'discount_value' => 10.00,
            'minimum_purchase' => 100.00,
            'max_uses' => 100,
            'times_used' => 0,
            'valid_from' => Time::now()->format('Y-m-d'),
            'valid_until' => Time::now()->addMonths(3)->format('Y-m-d'),
            'is_active' => true,
            'applies_to_travel_fee' => false,
            'created_at' => Time::now()
        ]);

        // Promo Code 2: PARTY20 - 20% de descuento
        $db->table('promo_codes')->insert([
            'id' => $this->generateUUID(),
            'code' => 'PARTY20',
            'description' => 'Party special - 20% off for events over $300',
            'discount_type' => 'percentage',
            'discount_value' => 20.00,
            'minimum_purchase' => 300.00,
            'max_uses' => 50,
            'times_used' => 0,
            'valid_from' => Time::now()->format('Y-m-d'),
            'valid_until' => Time::now()->addMonths(6)->format('Y-m-d'),
            'is_active' => true,
            'applies_to_travel_fee' => false,
            'created_at' => Time::now()
        ]);

        echo "✓ Inserted 2 promo codes successfully\n";
    }

    /**
     * Generate a UUID v4
     */
    private function generateUUID(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
