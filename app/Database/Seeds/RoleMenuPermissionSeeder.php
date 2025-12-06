<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use Ramsey\Uuid\Uuid;

class RoleMenuPermissionSeeder extends Seeder
{
    /**
     * Obtiene el ID de un menú por su URI
     */
    private function getMenuIdByUri(string $uri): ?string
    {
        $menu = $this->db->table('menus')
            ->where('uri', $uri)
            ->where('deleted_at', null)
            ->get()
            ->getRow();

        return $menu ? $menu->id : null;
    }

    public function run()
    {
        $permissions = [];

        // IDs de roles (deben coincidir con los del RoleSeeder)
        $adminRoleId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $coordRoleId = 'b2c3d4e5-f6g7-8901-bcde-f23456789012';
        $viewerRoleId = 'c3d4e5f6-g7h8-9012-cdef-345678901234';

        // Obtener IDs de menús por URI (más robusto que hardcodear IDs)
        $menuUris = [
            // Menús principales
            'dashboard' => '/admin/dashboard',
            'reservations' => '/admin/reservations',
            'clients' => '/admin/clients',
            'serviceAreas' => '#',  // Se buscará el correcto por nombre
            'services' => '#',       // Se buscará el correcto por nombre
            'config' => '#',         // Se buscará el correcto por nombre

            // Submenús de Service Areas
            'metropolitanAreas' => '/admin/areas/metropolitan-areas',
            'counties' => '/admin/areas/counties',
            'cities' => '/admin/areas/cities',
            'postalCodes' => '/admin/areas/postal-codes',

            // Submenús de SERVICIOS
            'jamTypes' => '/admin/services/jam-types',
            'prices' => '/admin/services/prices',
            'typeAddons' => '/admin/services/type-addons',
            'addons' => '/admin/services/addons',

            // Submenús de Configuración
            'users' => '/admin/config/users',
            'roles' => '/admin/config/roles',

            // Otros menús
            'promoCodes' => '/admin/promo-codes',
            'abandonedCarts' => '/admin/abandoned-carts',
        ];

        // Obtener menús con URI '#' por nombre
        $serviceAreasMenu = $this->db->table('menus')
            ->where('name', 'Service Areas')
            ->where('deleted_at', null)
            ->get()->getRow();

        $servicesMenu = $this->db->table('menus')
            ->where('name', 'Services')
            ->where('deleted_at', null)
            ->get()->getRow();

        $configMenu = $this->db->table('menus')
            ->where('name', 'Configuration')
            ->where('deleted_at', null)
            ->get()->getRow();

        // Mapeo de URIs a IDs
        $menuIds = [];
        foreach ($menuUris as $key => $uri) {
            if ($uri !== '#') {
                $menuIds[$key] = $this->getMenuIdByUri($uri);
            }
        }

        // Agregar menús con URI '#'
        $menuIds['serviceAreas'] = $serviceAreasMenu ? $serviceAreasMenu->id : null;
        $menuIds['services'] = $servicesMenu ? $servicesMenu->id : null;
        $menuIds['config'] = $configMenu ? $configMenu->id : null;

        // ===== PERMISOS PARA ADMINISTRADOR (Acceso completo) =====
        $adminMenuKeys = [
            'dashboard', 'reservations', 'clients', 'config', 'serviceAreas', 'services',
            'metropolitanAreas', 'counties', 'cities', 'postalCodes',
            'jamTypes', 'prices', 'typeAddons', 'addons',
            'users', 'roles', 'promoCodes', 'abandonedCarts'
        ];

        foreach ($adminMenuKeys as $key) {
            $menuId = $menuIds[$key] ?? null;
            if (!$menuId) continue;

            $permissions[] = [
                'id' => Uuid::uuid4()->toString(),
                'role_id' => $adminRoleId,
                'menu_id' => $menuId,
                'can_view' => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ];
        }

        // ===== PERMISOS PARA COORDINADOR =====
        $coordMenuKeys = [
            'dashboard', 'reservations', 'clients',
            'serviceAreas', 'metropolitanAreas', 'counties', 'cities', 'postalCodes',
            'services', 'jamTypes', 'prices', 'typeAddons', 'addons'
        ];

        // Permisos especiales para coordinador
        $coordSpecialPermissions = [
            'reservations' => ['can_create' => true, 'can_update' => true],
            'prices' => ['can_create' => true, 'can_update' => true],
            'typeAddons' => ['can_update' => true],
            'addons' => ['can_update' => true],
        ];

        foreach ($coordMenuKeys as $key) {
            $menuId = $menuIds[$key] ?? null;
            if (!$menuId) continue;

            $special = $coordSpecialPermissions[$key] ?? [];

            $permissions[] = [
                'id' => Uuid::uuid4()->toString(),
                'role_id' => $coordRoleId,
                'menu_id' => $menuId,
                'can_view' => true,
                'can_create' => $special['can_create'] ?? false,
                'can_update' => $special['can_update'] ?? false,
                'can_delete' => false,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ];
        }

        // ===== PERMISOS PARA VISUALIZADOR (Solo Dashboard) =====
        $viewerMenuKeys = ['dashboard'];

        foreach ($viewerMenuKeys as $key) {
            $menuId = $menuIds[$key] ?? null;
            if (!$menuId) continue;

            $permissions[] = [
                'id' => Uuid::uuid4()->toString(),
                'role_id' => $viewerRoleId,
                'menu_id' => $menuId,
                'can_view' => true,
                'can_create' => false,
                'can_update' => false,
                'can_delete' => false,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ];
        }

        // Insertar o actualizar permisos
        foreach ($permissions as $permission) {
            $existing = $this->db->table('role_menu_permissions')
                ->where('role_id', $permission['role_id'])
                ->where('menu_id', $permission['menu_id'])
                ->get()
                ->getRow();

            if ($existing) {
                // Actualizar si existe
                unset($permission['id']); // No cambiar el ID existente
                $this->db->table('role_menu_permissions')
                    ->where('role_id', $existing->role_id)
                    ->where('menu_id', $existing->menu_id)
                    ->update($permission);
            } else {
                // Insertar si no existe
                $this->db->table('role_menu_permissions')->insert($permission);
            }
        }
    }
}
