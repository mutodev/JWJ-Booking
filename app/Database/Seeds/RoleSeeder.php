<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
                'name' => 'Administrador',
                'is_active' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'b2c3d4e5-f6g7-8901-bcde-f23456789012',
                'name' => 'Coordinador',
                'is_active' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'c3d4e5f6-g7h8-9012-cdef-345678901234',
                'name' => 'Visualizador',
                'is_active' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ]
        ];

        $this->db->table('roles')->insertBatch($data);
    }
}
