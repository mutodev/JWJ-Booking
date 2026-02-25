<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use Ramsey\Uuid\Uuid;

class EmailTemplatesMenuSeeder extends Seeder
{
    public function run()
    {
        // IDs de roles
        $adminRoleId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $coordinatorRoleId = 'b2c3d4e5-f6g7-8901-bcde-f23456789012';

        // ID del menú padre Configuration
        $configParentId = 'm5e6f7g8-h9i0-1234-jkl5-678901234567';

        // ID del nuevo submenú
        $emailTemplatesMenuId = 'sm6k2l3m-n5o6-7890-pqr1-emailtmpl001';

        // =====================================================
        // 0. LIMPIAR DATOS EXISTENTES (si existen)
        // =====================================================
        $this->db->table('role_menu_permissions')
            ->where('menu_id', $emailTemplatesMenuId)
            ->delete();

        $this->db->table('menus')
            ->where('id', $emailTemplatesMenuId)
            ->delete();

        // =====================================================
        // 1. CREAR SUBMENÚ bajo Configuration
        // =====================================================
        $this->db->table('menus')->insert([
            'id' => $emailTemplatesMenuId,
            'name' => 'Email Templates',
            'uri' => '/admin/config/email-templates',
            'icon' => 'bi bi-envelope-paper',
            'order' => 3,
            'is_active' => true,
            'parent_id' => $configParentId,
            'created_at' => Time::now(),
            'updated_at' => Time::now(),
        ]);

        echo "Menu creado: Email Templates (bajo Configuration)\n";

        // =====================================================
        // 2. ASIGNAR PERMISOS A ROLES
        // =====================================================
        $permissions = [
            // Administrator: view + update (no create/delete)
            [
                'id' => Uuid::uuid4()->toString(),
                'role_id' => $adminRoleId,
                'menu_id' => $emailTemplatesMenuId,
                'can_view' => true,
                'can_create' => false,
                'can_update' => true,
                'can_delete' => false,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ],
            // Coordinator: view + update (no create/delete)
            [
                'id' => Uuid::uuid4()->toString(),
                'role_id' => $coordinatorRoleId,
                'menu_id' => $emailTemplatesMenuId,
                'can_view' => true,
                'can_create' => false,
                'can_update' => true,
                'can_delete' => false,
                'created_at' => Time::now(),
                'updated_at' => Time::now(),
            ],
        ];

        $this->db->table('role_menu_permissions')->insertBatch($permissions);

        echo "Permisos asignados a Administrator y Coordinator (view + update only)\n";
    }
}
