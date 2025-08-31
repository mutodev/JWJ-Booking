<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MetropolitanAreaSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'id'   => 'a3f5c6d2-91b4-4c9f-9b92-1f3a6e4c9d11',
                'name' => 'MIAMI, BOCA AND PALM BEACH',
            ],
            [
                'id'   => 'b4d6e7f3-82c5-4a8e-8c01-2e4b7f5d8c22',
                'name' => 'NEW YORK',
            ],
            [
                'id'   => 'c5e7f8a4-73d6-4b7f-9d12-3f5c8g6e9d33',
                'name' => 'NEW JERSEY',
            ],
            [
                'id'   => 'd6f8a9b5-64e7-4c6f-8e23-4g6d9h7f0e44',
                'name' => 'LOS ANGELES',
            ],
            [
                'id'   => 'e7g9b0c6-55f8-4d5f-9f34-5h7e0i8g1f55',
                'name' => 'WASHINGTON DC',
            ],
            [
                'id'   => 'f8h0c1d7-46g9-4e4f-8a45-6i8f1j9h2g66',
                'name' => 'CHICAGO',
            ],
            [
                'id'   => 'g9i1d2e8-37h0-4f3f-9b56-7j9g2k0i3h77',
                'name' => 'NASHVILLE',
            ],
            [
                'id'   => 'h0j2e3f9-28i1-4g2f-8c67-8k0h3l1j4i88',
                'name' => 'SAN FRANCISCO',
            ],
        ];

        $this->db->table('metropolitan_areas')->insertBatch($data);
    }
}
