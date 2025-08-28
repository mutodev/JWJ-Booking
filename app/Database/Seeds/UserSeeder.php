<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class UserSeeder extends Seeder
{
    public function run()
    {
        $password = password_hash('Admin123!', PASSWORD_DEFAULT);

        $data = [
            // Administrador
            [
                'id' => 'u1a2b3c4-d5e6-7890-fgh1-234567890123',
                'first_name' => 'Juan',
                'last_name' => 'Administrador',
                'email' => 'admin-jwj@yopmail.com',
                'password' => $password,
                'image' => null,
                'state' => true,
                'role_id' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
                'is_active' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            // Coordinador
            [
                'id' => 'u2b3c4d5-e6f7-8901-ghi2-345678901234',
                'first_name' => 'Maria',
                'last_name' => 'Coordinadora',
                'email' => 'coordinador-jwj@yopmail.com',
                'password' => $password,
                'image' => null,
                'state' => true,
                'role_id' => 'b2c3d4e5-f6g7-8901-bcde-f23456789012',
                'is_active' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            // Visualizador
            [
                'id' => 'u3c4d5e6-f7g8-9012-hij3-456789012345',
                'first_name' => 'Carlos',
                'last_name' => 'Visualizador',
                'email' => 'viewer-jwj@yopmail.com',
                'password' => $password,
                'image' => null,
                'state' => true,
                'role_id' => 'c3d4e5f6-g7h8-9012-cdef-345678901234',
                'is_active' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ]
        ];

        $this->db->table('users')->insertBatch($data);
    }
}
