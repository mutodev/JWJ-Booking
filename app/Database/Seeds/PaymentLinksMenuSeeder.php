<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use Ramsey\Uuid\Uuid;

/**
 * B5 — sidebar menu item + role permissions for "Payment Links".
 *
 * Independent and idempotent: it only touches its own menu id and the
 * permission rows for that menu. It never edits MenuSeeder or
 * RoleMenuPermissionSeeder. Follows PromoCodesAndAbandonedCartsMenuSeeder.
 *
 * Without these rows the new admin screen is invisible (the router guard and
 * the sidebar are both driven by the `menus` / `role_menu_permissions` tables).
 */
class PaymentLinksMenuSeeder extends Seeder
{
    public function run()
    {
        // Role ids (from RoleSeeder)
        $adminRoleId       = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890'; // Administrador
        $coordinatorRoleId = 'b2c3d4e5-f6g7-8901-bcde-f23456789012'; // Coordinador

        $menuId = 'paylink1-menu-4567-8901-234567890abc';

        // 0. Clean existing rows for this menu (idempotent re-run).
        $this->db->table('role_menu_permissions')->where('menu_id', $menuId)->delete();
        $this->db->table('menus')->where('id', $menuId)->delete();

        // 1. Menu item.
        $this->db->table('menus')->insert([
            'id'         => $menuId,
            'name'       => 'Payment Links',
            'uri'        => '/admin/payment-links',
            'icon'       => 'bi bi-link-45deg',
            'order'      => 9, // after Abandoned Carts (8)
            'is_active'  => true,
            'parent_id'  => null,
            'created_at' => Time::now(),
            'updated_at' => Time::now(),
        ]);

        echo "✅ Menu created: Payment Links\n";

        // 2. Permissions — full CRUD for Administrator and Coordinator, matching
        //    the Promo Codes treatment (staff create/manage payment links).
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

        echo "✅ Permissions assigned to Administrator and Coordinator\n";
        echo "🎉 PaymentLinksMenuSeeder completed\n";
    }
}
