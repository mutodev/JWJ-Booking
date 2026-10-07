<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use Ramsey\Uuid\Uuid;

/**
 * Agrega el submenú "Custom Services" dentro de Services con CRUD completo
 * para Administrator y Coordinator. Idempotente.
 *
 * php spark db:seed CustomServicesMenuSeeder
 */
class CustomServicesMenuSeeder extends Seeder
{
    public function run()
    {
        $adminRoleId       = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $coordinatorRoleId = 'b2c3d4e5-f6g7-8901-bcde-f23456789012';

        $servicesParentId = 'm5e6f7g8-h9i0-1234-jkl5-678901234ss1';
        $menuId           = 'custsvc1-menu-4567-8901-234567890abc';

        // Limpiar si ya existe
        $this->db->table('role_menu_permissions')->where('menu_id', $menuId)->delete();
        $this->db->table('menus')->where('id', $menuId)->delete();

        echo "🧹 Datos existentes limpiados\n";

        $this->db->table('menus')->insert([
            'id'         => $menuId,
            'name'       => 'Custom Services',
            'uri'        => '/admin/services/custom-services',
            'icon'       => 'bi bi-stars',
            'order'      => 5,
            'is_active'  => true,
            'parent_id'  => $servicesParentId,
            'created_at' => Time::now(),
            'updated_at' => Time::now(),
        ]);

        echo "✅ Menú creado: Services > Custom Services\n";

        $permissions = [];
        foreach ([$adminRoleId, $coordinatorRoleId] as $roleId) {
            $permissions[] = [
                'id'         => Uuid::uuid4()->toString(),
                'role_id'    => $roleId,
                'menu_id'    => $menuId,
                'can_view'   => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ];
        }

        $this->db->table('role_menu_permissions')->insertBatch($permissions);

        echo "✅ Permisos CRUD asignados a Administrator y Coordinator\n";
    }
}
