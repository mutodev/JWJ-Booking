<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyAddonsTable extends Migration
{
    public function up()
    {
        // Agregar columna type_addon_id
        $this->forge->addColumn('addons', [
            'type_addon_id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => true,
                'after' => 'id',
            ],
        ]);

        // Eliminar columnas que ahora están en type_addons
        $this->forge->dropColumn('addons', ['image', 'description', 'price_type']);

        // Agregar foreign key
        $this->db->query('ALTER TABLE addons ADD CONSTRAINT fk_addons_type_addon FOREIGN KEY (type_addon_id) REFERENCES type_addons(id) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        // Eliminar foreign key
        $this->db->query('ALTER TABLE addons DROP FOREIGN KEY fk_addons_type_addon');

        // Eliminar columna type_addon_id
        $this->forge->dropColumn('addons', 'type_addon_id');

        // Restaurar columnas eliminadas
        $this->forge->addColumn('addons', [
            'image' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'price_type' => [
                'type' => 'ENUM',
                'constraint' => ['standard', 'jukebox'],
                'default' => 'standard',
            ],
        ]);
    }
}
