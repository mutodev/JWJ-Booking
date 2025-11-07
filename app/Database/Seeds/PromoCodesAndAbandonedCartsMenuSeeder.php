<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use Ramsey\Uuid\Uuid;

class PromoCodesAndAbandonedCartsMenuSeeder extends Seeder
{
    public function run()
    {
        // IDs de roles
        $adminRoleId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890'; // Administrador
        $coordinatorRoleId = 'b2c3d4e5-f6g7-8901-bcde-f23456789012'; // Coordinador

        // IDs de menús
        $promoMenuId = 'promo001-menu-4567-8901-234567890abc';
        $cartsMenuId = 'carts001-menu-4567-8901-234567890def';

        // =====================================================
        // 0. LIMPIAR DATOS EXISTENTES (si existen)
        // =====================================================

        // Eliminar permisos existentes
        $this->db->table('role_menu_permissions')
            ->whereIn('menu_id', [$promoMenuId, $cartsMenuId])
            ->delete();

        // Eliminar menús existentes
        $this->db->table('menus')
            ->whereIn('id', [$promoMenuId, $cartsMenuId])
            ->delete();

        echo "🧹 Datos existentes limpiados\n";

        // =====================================================
        // 1. CREAR MENÚS
        // =====================================================

        $menus = [
            // Menú principal: Promo Codes
            [
                'id' => $promoMenuId,
                'name' => 'Promo Codes',
                'uri' => '/admin/promo-codes',
                'icon' => 'bi bi-ticket-perforated',
                'order' => 7, // Después de Configuration
                'is_active' => true,
                'parent_id' => null,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            // Menú principal: Abandoned Carts
            [
                'id' => $cartsMenuId,
                'name' => 'Abandoned Carts',
                'uri' => '/admin/abandoned-carts',
                'icon' => 'bi bi-cart-x',
                'order' => 8, // Después de Promo Codes
                'is_active' => true,
                'parent_id' => null,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
        ];

        // Insertar menús
        $this->db->table('menus')->insertBatch($menus);

        echo "✅ Menús creados: Promo Codes y Abandoned Carts\n";

        // =====================================================
        // 2. ASIGNAR PERMISOS A ROLES
        // =====================================================

        $permissions = [
            // Permisos para Administrator (acceso completo)
            [
                'id' => Uuid::uuid4()->toString(),
                'role_id' => $adminRoleId,
                'menu_id' => $promoMenuId,
                'can_view' => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'role_id' => $adminRoleId,
                'menu_id' => $cartsMenuId,
                'can_view' => true,
                'can_create' => false, // Abandoned carts son solo lectura (no se crean desde admin)
                'can_update' => false,
                'can_delete' => false,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            // Permisos para Coordinator (acceso completo)
            [
                'id' => Uuid::uuid4()->toString(),
                'role_id' => $coordinatorRoleId,
                'menu_id' => $promoMenuId,
                'can_view' => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'role_id' => $coordinatorRoleId,
                'menu_id' => $cartsMenuId,
                'can_view' => true,
                'can_create' => false, // Abandoned carts son solo lectura (no se crean desde admin)
                'can_update' => false,
                'can_delete' => false,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ],
        ];

        // Insertar permisos
        $this->db->table('role_menu_permissions')->insertBatch($permissions);

        echo "✅ Permisos asignados a Administrator y Coordinator\n";
        echo "\n";
        echo "📋 RESUMEN:\n";
        echo "   - Promo Codes: CRUD completo para Admin y Coordinator (view, create, update, delete)\n";
        echo "   - Abandoned Carts: Solo lectura para Admin y Coordinator (view only)\n";
        echo "\n";
        echo "🎉 Seeder completado exitosamente!\n";
    }
}
