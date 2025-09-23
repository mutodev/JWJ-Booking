<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use Ramsey\Uuid\Uuid;

class RoleMenuPermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [];

        // IDs de roles (deben coincidir con los del RoleSeeder)
        $adminRoleId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $coordRoleId = 'b2c3d4e5-f6g7-8901-bcde-f23456789012';
        $viewerRoleId = 'c3d4e5f6-g7h8-9012-cdef-345678901234';

        // IDs de menús principales (deben coincidir con MenuSeeder)
        $dashboardMenuId = 'm1a2b3c4-d5e6-7890-fgh1-234567890123';
        $reservasMenuId = 'm2b3c4d5-e6f7-8901-ghi2-345678901234';
        $clientesMenuId = 'm3c4d5e6-f7g8-9012-hij3-456789012345';
        $configMenuId = 'm5e6f7g8-h9i0-1234-jkl5-678901234567';
        $serviceAreasId = '5758a89e-08ae-40f7-b98c-aafd0fd68627';
        $servicesMenuId = 'm5e6f7g8-h9i0-1234-jkl5-678901234ss1'; // Menú principal de SERVICIOS

        // IDs de submenús de Service Areas
        $metropolitanAreas = 'u1a2b3c4-d5e6-7890-fgh1-2345678901s1';
        $counties = 'u1a2b3c4-d5e6-7890-fgh1-2345678901s2';
        $cities = 'u1a2b3c4-d5e6-7890-fgh1-2345678901s3';
        $postalCodes = 'u1a2b3c4-d5e6-7890-fgh1-2345678901s4';

        // IDs de submenús de SERVICIOS (CRÍTICO)
        $jamTypesMenuId = 'sm1f7g8h9-i0j1-2345-klm6-789012345678';     // Jam Types
        $priceTableMenuId = 'sm2g8h9i0-j1k2-3456-lmn7-890123456789';   // Price Table
        $addonsMenuId = 'sm3h9i0j1-k2l3-4567-mno8-901234567890';       // Add-ons

        // IDs de submenús de Configuración
        $usuariosMenuId = 'sm4i0j1k2-l3m4-5678-nop9-012345678901';     // Users
        $rolesMenuId = 'sm5j1k2l3-m4n5-6789-opq0-123456789012';        // Roles

        // ===== PERMISOS PARA ADMINISTRADOR (Acceso completo) =====
        $adminMenus = [
            // Menús principales
            $dashboardMenuId,
            $reservasMenuId,
            $clientesMenuId,
            $configMenuId,
            $serviceAreasId,
            $servicesMenuId,

            // Submenús de Service Areas
            $metropolitanAreas,
            $counties,
            $cities,
            $postalCodes,

            // Submenús de SERVICIOS
            $jamTypesMenuId,
            $priceTableMenuId,
            $addonsMenuId,

            // Submenús de Configuración
            $usuariosMenuId,
            $rolesMenuId
        ];

        foreach ($adminMenus as $menuId) {
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
        $coordMenus = [
            // Menús principales
            $dashboardMenuId,
            $reservasMenuId,
            $clientesMenuId,

            // Submenús de Service Areas (solo lectura)
            $serviceAreasId,
            $metropolitanAreas,
            $counties,
            $cities,
            $postalCodes,

            // Submenús de SERVICIOS (permisos específicos)
            $servicesMenuId,
            $jamTypesMenuId,
            $priceTableMenuId,
            $addonsMenuId
        ];

        foreach ($coordMenus as $menuId) {
            $canCreate = false;
            $canUpdate = false;

            // Permisos especiales para Coordinador
            if ($menuId === $reservasMenuId) {
                $canCreate = true;
                $canUpdate = true;
            } elseif ($menuId === $priceTableMenuId) {
                $canCreate = true;  // Puede crear entradas en tabla de precios
                $canUpdate = true;  // Puede actualizar precios
            } elseif ($menuId === $addonsMenuId) {
                $canUpdate = true;  // Puede actualizar add-ons existentes
            }

            $permissions[] = [
                'id' => Uuid::uuid4()->toString(),
                'role_id' => $coordRoleId,
                'menu_id' => $menuId,
                'can_view' => true,
                'can_create' => $canCreate,
                'can_update' => $canUpdate,
                'can_delete' => false, // Nunca puede eliminar
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ];
        }

        // ===== PERMISOS PARA VISUALIZADOR (Solo Dashboard) =====
        $viewerMenus = [
            $dashboardMenuId
        ];

        foreach ($viewerMenus as $menuId) {
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

        // Insertar todos los permisos
        $this->db->table('role_menu_permissions')->insertBatch($permissions);
    }
}
