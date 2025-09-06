<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use Ramsey\Uuid\Uuid;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        $customers = [
            [
                'id' => Uuid::uuid4()->toString(),
                'email' => 'maria.gonzalez@yopmail.com',
                'phone' => '+1 305-123-4567',
                'full_name' => 'Maria Gonzalez',
                'billing_address' => "123 Main St\nMiami, FL 33101",
                'segment' => 'frequent',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'email' => 'john.smith@yopmail.com',
                'phone' => '+1 305-987-6543',
                'full_name' => 'John Smith',
                'billing_address' => "456 Ocean Dr\nMiami Beach, FL 33139",
                'segment' => 'vip',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'email' => 'lisa.rodriguez@yopmail.com',
                'phone' => '+1 786-555-0123',
                'full_name' => 'Lisa Rodriguez',
                'billing_address' => "789 Palm Ave\nCoral Gables, FL 33134",
                'segment' => 'new',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ]
        ];

        $this->db->table('customers')->insertBatch($customers);
    }
}
