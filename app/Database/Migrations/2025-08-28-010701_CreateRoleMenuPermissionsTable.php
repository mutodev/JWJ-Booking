<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRoleMenuPermissionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'CHAR', 'constraint' => 36],
            'role_id'    => ['type' => 'CHAR', 'constraint' => 36],
            'menu_id'    => ['type' => 'CHAR', 'constraint' => 36],
            'can_view'   => ['type' => 'BOOLEAN', 'default' => false],
            'can_create' => ['type' => 'BOOLEAN', 'default' => false],
            'can_update' => ['type' => 'BOOLEAN', 'default' => false],
            'can_delete' => ['type' => 'BOOLEAN', 'default' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['role_id', 'menu_id']);
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('menu_id', 'menus', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('role_menu_permissions');
    }

    public function down()
    {
        $this->forge->dropTable('role_menu_permissions');
    }
}
