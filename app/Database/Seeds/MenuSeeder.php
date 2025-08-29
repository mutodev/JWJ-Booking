<?php namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class MenuSeeder extends Seeder
{
    public function run()
    {
        // Menús principales
        $menus = [
            [
                'id' => 'm1a2b3c4-d5e6-7890-fgh1-234567890123',
                'name' => 'Dashboard',
                'uri' => 'admin/dashboard',
                'icon' => 'bi bi-speedometer2',
                'order' => 1,
                'is_active' => true,
                'parent_id' => null,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'm2b3c4d5-e6f7-8901-ghi2-345678901234',
                'name' => 'Reservas',
                'uri' => 'admin/reservas',
                'icon' => 'bi bi-calendar-check',
                'order' => 2,
                'is_active' => true,
                'parent_id' => null,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'm3c4d5e6-f7g8-9012-hij3-456789012345',
                'name' => 'Clientes',
                'uri' => 'admin/clientes',
                'icon' => 'bi bi-people',
                'order' => 3,
                'is_active' => true,
                'parent_id' => null,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'm4d5e6f7-g8h9-0123-ijk4-567890123456',
                'name' => 'Reportes',
                'uri' => 'admin/reportes',
                'icon' => 'bi bi-bar-chart',
                'order' => 4,
                'is_active' => true,
                'parent_id' => null,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'm5e6f7g8-h9i0-1234-jkl5-678901234567',
                'name' => 'Configuración',
                'uri' => '#',
                'icon' => 'bi bi-gear',
                'order' => 5,
                'is_active' => true,
                'parent_id' => null,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ]
        ];

        // Submenús de Configuración
        $submenus = [
            [
                'id' => 'sm1f7g8h9-i0j1-2345-klm6-789012345678',
                'name' => 'Servicios',
                'uri' => 'admin/config/servicios',
                'icon' => 'bi bi-list',
                'order' => 1,
                'is_active' => true,
                'parent_id' => 'm5e6f7g8-h9i0-1234-jkl5-678901234567',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'sm2g8h9i0-j1k2-3456-lmn7-890123456789',
                'name' => 'Precios',
                'uri' => 'admin/config/precios',
                'icon' => 'bi bi-tag',
                'order' => 2,
                'is_active' => true,
                'parent_id' => 'm5e6f7g8-h9i0-1234-jkl5-678901234567',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'sm3h9i0j1-k2l3-4567-mno8-901234567890',
                'name' => 'Usuarios',
                'uri' => 'admin/config/usuarios',
                'icon' => 'bi bi-person-gear',
                'order' => 3,
                'is_active' => true,
                'parent_id' => 'm5e6f7g8-h9i0-1234-jkl5-678901234567',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'sm4i0j1k2-l3m4-5678-nop9-012345678901',
                'name' => 'Roles',
                'uri' => 'admin/config/roles',
                'icon' => 'bi bi-shield-lock',
                'order' => 4,
                'is_active' => true,
                'parent_id' => 'm5e6f7g8-h9i0-1234-jkl5-678901234567',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'sm5j1k2l3-m4n5-6789-opq0-123456789012',
                'name' => 'Menús',
                'uri' => 'admin/config/menus',
                'icon' => 'bi bi-list-nested',
                'order' => 5,
                'is_active' => true,
                'parent_id' => 'm5e6f7g8-h9i0-1234-jkl5-678901234567',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ]
        ];

        $this->db->table('menus')->insertBatch($menus);
        $this->db->table('menus')->insertBatch($submenus);
    }
}
