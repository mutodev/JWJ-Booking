<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use Ramsey\Uuid\Uuid;

class MenuSeeder extends Seeder
{
    public function run()
    {
        $menus = [
            [
                'id' => 'm1a2b3c4-d5e6-7890-fgh1-234567890123',
                'name' => 'Dashboard',
                'uri' => '/admin/dashboard',
                'icon' => 'bi bi-house-door',
                'order' => 1,
                'is_active' => true,
                'parent_id' => null,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'm2b3c4d5-e6f7-8901-ghi2-345678901234',
                'name' => 'Reservations',
                'uri' => '/admin/reservations',
                'icon' => 'bi bi-calendar-event',
                'order' => 2,
                'is_active' => true,
                'parent_id' => null,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'm3c4d5e6-f7g8-9012-hij3-456789012345',
                'name' => 'Clients',
                'uri' => '/admin/clients',
                'icon' => 'bi bi-people-fill',
                'order' => 3,
                'is_active' => true,
                'parent_id' => null,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'm4d5e6f7-g8h9-0123-ijk4-567890123456',
                'name' => 'Reports',
                'uri' => '/admin/reports',
                'icon' => 'bi bi-graph-up',
                'order' => 4,
                'is_active' => true,
                'parent_id' => null,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => '5758a89e-08ae-40f7-b98c-aafd0fd68627',
                'name' => 'Service Areas',
                'uri' => '#',
                'icon' => 'bi bi-globe-americas',
                'order' => 5,
                'is_active' => true,
                'parent_id' => null,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'm5e6f7g8-h9i0-1234-jkl5-678901234567',
                'name' => 'Configuration',
                'uri' => '#',
                'icon' => 'bi bi-gear',
                'order' => 6,
                'is_active' => true,
                'parent_id' => null,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ]
        ];

        // Configuration submenus
        $submenus = [
            [
                'id' => 'sm1f7g8h9-i0j1-2345-klm6-789012345678',
                'name' => 'Services',
                'uri' => '/admin/config/services',
                'icon' => 'bi bi-wrench-adjustable',
                'order' => 1,
                'is_active' => true,
                'parent_id' => 'm5e6f7g8-h9i0-1234-jkl5-678901234567',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'sm2g8h9i0-j1k2-3456-lmn7-890123456789',
                'name' => 'Prices',
                'uri' => '/admin/config/prices',
                'icon' => 'bi bi-currency-dollar',
                'order' => 2,
                'is_active' => true,
                'parent_id' => 'm5e6f7g8-h9i0-1234-jkl5-678901234567',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'sm3h9i0j1-k2l3-4567-mno8-901234567890',
                'name' => 'Users',
                'uri' => '/admin/config/users',
                'icon' => 'bi bi-person-circle',
                'order' => 3,
                'is_active' => true,
                'parent_id' => 'm5e6f7g8-h9i0-1234-jkl5-678901234567',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'sm4i0j1k2-l3m4-5678-nop9-012345678901',
                'name' => 'Roles',
                'uri' => '/admin/config/roles',
                'icon' => 'bi bi-shield-lock',
                'order' => 4,
                'is_active' => true,
                'parent_id' => 'm5e6f7g8-h9i0-1234-jkl5-678901234567',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'u1a2b3c4-d5e6-7890-fgh1-2345678901s1',
                'name' => 'Metropolitan Areas',
                'uri' => '/admin/areas/metropolitan-areas',
                'icon' => 'bi bi-building',
                'order' => 1,
                'is_active' => true,
                'parent_id' => '5758a89e-08ae-40f7-b98c-aafd0fd68627',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'u1a2b3c4-d5e6-7890-fgh1-2345678901s2',
                'name' => 'Counties',
                'uri' => '/admin/areas/counties',
                'icon' => 'bi bi-pin-map',
                'order' => 1,
                'is_active' => true,
                'parent_id' => '5758a89e-08ae-40f7-b98c-aafd0fd68627',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'u1a2b3c4-d5e6-7890-fgh1-2345678901s3',
                'name' => 'Cities',
                'uri' => '/admin/areas/cities',
                'icon' => 'bi bi-pin-map',
                'order' => 1,
                'is_active' => true,
                'parent_id' => '5758a89e-08ae-40f7-b98c-aafd0fd68627',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'u1a2b3c4-d5e6-7890-fgh1-2345678901s4',
                'name' => 'Postal Codes',
                'uri' => '/admin/areas/postal-codes',
                'icon' => 'bi bi-pin',
                'order' => 1,
                'is_active' => true,
                'parent_id' => '5758a89e-08ae-40f7-b98c-aafd0fd68627',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
        ];

        $this->db->table('menus')->insertBatch($menus);
        $this->db->table('menus')->insertBatch($submenus);
    }
}
