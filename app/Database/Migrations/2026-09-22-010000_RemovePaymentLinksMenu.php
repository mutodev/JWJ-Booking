<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemovePaymentLinksMenu extends Migration
{
    public function up()
    {
        $menu = $this->db->table('menus')->where('uri', '/admin/payment-links')->get()->getRowArray();
        if (!$menu) {
            return;
        }

        $this->db->table('role_menu_permissions')->where('menu_id', $menu['id'])->delete();
        $this->db->table('menus')->where('id', $menu['id'])->delete();
    }

    public function down()
    {
        // Deliberately no-op: the retired standalone UI is not restored by rollback.
    }
}
