<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use Ramsey\Uuid\Uuid;

class CleanReservationsMenuSeeder extends Seeder
{
    public function run()
    {
        $adminRoleId       = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $coordinatorRoleId = 'b2c3d4e5-f6g7-8901-bcde-f23456789012';

        $menuId = 'clean001-menu-4567-8901-234567890abc';

        // Limpiar si ya existe
        $this->db->table('role_menu_permissions')->where('menu_id', $menuId)->delete();
        $this->db->table('menus')->where('id', $menuId)->delete();

        echo "🧹 Datos existentes limpiados\n";

        // Crear menú
        $this->db->table('menus')->insert([
            'id'         => $menuId,
            'name'       => 'Clean Reservations',
            'uri'        => '/admin/maintenance/clean-reservations',
            'icon'       => 'bi bi-trash3',
            'order'      => 9,
            'is_active'  => true,
            'parent_id'  => null,
            'created_at' => Time::now(),
            'updated_at' => Time::now(),
        ]);

        echo "✅ Menú creado: Clean Reservations\n";

        // Asignar permisos solo a Administrator (operación destructiva)
        $this->db->table('role_menu_permissions')->insertBatch([
            [
                'id'         => Uuid::uuid4()->toString(),
                'role_id'    => $adminRoleId,
                'menu_id'    => $menuId,
                'can_view'   => true,
                'can_create' => false,
                'can_update' => false,
                'can_delete' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ],
            [
                'id'         => Uuid::uuid4()->toString(),
                'role_id'    => $coordinatorRoleId,
                'menu_id'    => $menuId,
                'can_view'   => true,
                'can_create' => false,
                'can_update' => false,
                'can_delete' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ],
        ]);

        echo "✅ Permisos asignados a Administrator y Coordinator\n";
        echo "🎉 Seeder completado!\n";
    }
}
