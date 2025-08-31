<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ZipCodeSeeder extends Seeder
{
    public function run()
    {
        $data = [];

        $this->db->table('zipcodes')->insertBatch($data);
    }
}
