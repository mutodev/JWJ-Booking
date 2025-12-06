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
            // Menú principal Dashboard
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
            // Menú principal Reservations
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
            // Menú principal Clients
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
            // Menú principal Service Areas (Zonas de Servicio)
            [
                'id' => '5758a89e-08ae-40f7-b98c-aafd0fd68627',
                'name' => 'Service Areas',
                'uri' => '#',
                'icon' => 'bi bi-globe-americas',
                'order' => 4,
                'is_active' => true,
                'parent_id' => null,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            // Menú principal Services (SERVICIOS)
            [
                'id' => 'm5e6f7g8-h9i0-1234-jkl5-678901234ss1',
                'name' => 'Services',
                'uri' => '#',
                'icon' => 'bi bi-music-note-list',
                'order' => 5,
                'is_active' => true,
                'parent_id' => null,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            // Menú principal Configuration
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
            ],
        ];

        // Service Areas submenus
        $serviceAreasSubmenus = [
            [
                'id' => 'u1a2b3c4-d5e6-7890-fgh1-2345678901s1',
                'name' => 'Metropolitan Areas',
                'uri' => '/admin/areas/metropolitan-areas',
                'icon' => 'bi bi-buildings',
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
                'order' => 2,
                'is_active' => true,
                'parent_id' => '5758a89e-08ae-40f7-b98c-aafd0fd68627',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'u1a2b3c4-d5e6-7890-fgh1-2345678901s3',
                'name' => 'Cities',
                'uri' => '/admin/areas/cities',
                'icon' => 'bi bi-geo-alt',
                'order' => 3,
                'is_active' => true,
                'parent_id' => '5758a89e-08ae-40f7-b98c-aafd0fd68627',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'u1a2b3c4-d5e6-7890-fgh1-2345678901s4',
                'name' => 'Postal Codes',
                'uri' => '/admin/areas/postal-codes',
                'icon' => 'bi bi-postcard',
                'order' => 4,
                'is_active' => true,
                'parent_id' => '5758a89e-08ae-40f7-b98c-aafd0fd68627',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
        ];

        // Services submenus (CRÍTICO - Aquí está la Tabla de Precios)
        $servicesSubmenus = [
            [
                'id' => 'sm1f7g8h-i0j1-2345-klm6-789012345678',
                'name' => 'Jam Types',
                'uri' => '/admin/services/jam-types',
                'icon' => 'bi bi-music-note-beamed',
                'order' => 1,
                'is_active' => true,
                'parent_id' => 'm5e6f7g8-h9i0-1234-jkl5-678901234ss1',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'sm2g8h9i-j1k2-3456-lmn7-890123456789',
                'name' => 'Prices',
                'uri' => '/admin/services/prices',
                'icon' => 'bi bi-currency-dollar',
                'order' => 2,
                'is_active' => true,
                'parent_id' => 'm5e6f7g8-h9i0-1234-jkl5-678901234ss1',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'sm2g8h9i-j1k2-3456-lmn7-890123456790',
                'name' => 'Addon Types',
                'uri' => '/admin/services/type-addons',
                'icon' => 'bi bi-tags',
                'order' => 3,
                'is_active' => true,
                'parent_id' => 'm5e6f7g8-h9i0-1234-jkl5-678901234ss1',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'sm3h9i0j-k2l3-4567-mno8-901234567890',
                'name' => 'Add-ons',
                'uri' => '/admin/services/addons',
                'icon' => 'bi bi-plus-circle',
                'order' => 4,
                'is_active' => true,
                'parent_id' => 'm5e6f7g8-h9i0-1234-jkl5-678901234ss1',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
        ];

        // Configuration submenus
        $configSubmenus = [
            [
                'id' => 'sm4i0j1k-l3m4-5678-nop9-012345678901',
                'name' => 'Users',
                'uri' => '/admin/config/users',
                'icon' => 'bi bi-person-circle',
                'order' => 1,
                'is_active' => true,
                'parent_id' => 'm5e6f7g8-h9i0-1234-jkl5-678901234567',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => 'sm5j1k2l-m4n5-6789-opq0-123456789012',
                'name' => 'Roles',
                'uri' => '/admin/config/roles',
                'icon' => 'bi bi-shield-lock',
                'order' => 2,
                'is_active' => true,
                'parent_id' => 'm5e6f7g8-h9i0-1234-jkl5-678901234567',
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
        ];

        // Combinar todos los menús
        $allMenus = array_merge($menus, $serviceAreasSubmenus, $servicesSubmenus, $configSubmenus);

        // Insertar o actualizar cada menú
        foreach ($allMenus as $menu) {
            // Primero buscar por ID (prioridad)
            $existing = $this->db->table('menus')
                ->where('id', $menu['id'])
                ->get()
                ->getRow();

            if ($existing) {
                // Actualizar si existe por ID
                $this->db->table('menus')
                    ->where('id', $menu['id'])
                    ->update($menu);
            } else {
                // Si no existe por ID, buscar por URI + parent_id (para evitar duplicados)
                if ($menu['uri'] !== '#') {
                    $existingByUri = $this->db->table('menus')
                        ->where('uri', $menu['uri'])
                        ->where('parent_id', $menu['parent_id'])
                        ->get()
                        ->getRow();

                    if ($existingByUri) {
                        // Ya existe con otra ID, actualizar
                        unset($menu['id']);
                        $this->db->table('menus')
                            ->where('id', $existingByUri->id)
                            ->update($menu);
                        continue;
                    }
                }

                // Insertar si no existe
                $this->db->table('menus')->insert($menu);
            }
        }
    }
}
