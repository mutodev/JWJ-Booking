<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UpdateServicesDurationSeeder extends Seeder
{
    public function run()
    {
        // Update existing services with their duration_hours
        $updates = [
            ['name' => 'Classic Jam', 'duration_hours' => 1.5],
            ['name' => 'Junior Jammer Mashup', 'duration_hours' => 1.0],
            ['name' => 'Eras Jam', 'duration_hours' => 2.0],
            ['name' => 'Big Kids Party', 'duration_hours' => 2.0],
        ];

        foreach ($updates as $update) {
            $this->db->table('services')
                ->where('name', $update['name'])
                ->update(['duration_hours' => $update['duration_hours']]);
        }

        echo "Services duration_hours updated successfully.\n";
    }
}
