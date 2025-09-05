<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Ramsey\Uuid\Uuid;

class ServicesSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Classic Jam',
                'description' => 'Servicio clásico de entretenimiento musical infantil con nuestros artistas en vivo.',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Junior Jammer Mashup',
                'description' => 'Experiencia especial diseñada para los más pequeños, con música y actividades apropiadas para su edad.',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Eras Jam',
                'description' => 'Viaje musical a través de las décadas. Un show temático que recorre las diferentes épocas de la música popular.',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Big Kids Party',
                'description' => 'Fiesta diseñada específicamente para niños mayores, con música y actividades más complejas y engaging.',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        // Using Query Builder
        $this->db->table('services')->insertBatch($data);

        echo "Seeder ejecutado exitosamente. 4 servicios insertados.\n";
    }
}
