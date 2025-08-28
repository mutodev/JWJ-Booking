<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMenusTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'CHAR', 'constraint' => 36],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'uri'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'icon'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'order'      => ['type' => 'INT', 'default' => 0],
            'is_active'  => ['type' => 'BOOLEAN', 'default' => true],
            'parent_id'  => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('parent_id', 'menus', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('menus');
    }

    public function down()
    {
        $this->forge->dropTable('menus');
    }
}
