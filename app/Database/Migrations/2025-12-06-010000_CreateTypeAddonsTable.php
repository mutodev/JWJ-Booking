<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTypeAddonsTable extends Migration
{
    public function up()
    {
        // Desactivar foreign keys
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

        // Primero eliminar datos de addons
        $this->db->table('reservation_addons')->truncate();
        $this->db->table('addons')->truncate();

        // Reactivar foreign keys
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');

        // Crear tabla type_addons
        $this->forge->addField([
            'id' => [
                'type' => 'CHAR',
                'constraint' => 36,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'image' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('type_addons');
    }

    public function down()
    {
        $this->forge->dropTable('type_addons');
    }
}
