<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRolesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'        => ['type' => 'CHAR', 'constraint' => 36],
            'name'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'is_active' => ['type' => 'BOOLEAN', 'default' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('roles');
    }

    public function down()
    {
        $this->forge->dropTable('roles');
    }
}
