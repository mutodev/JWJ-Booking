<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateChildrenAgeRanges extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'service_price_id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'min_age' => [
                'type'       => 'INT',
                'null'       => false,
            ],
            'max_age' => [
                'type'       => 'INT',
                'null'       => false,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
                'comment'    => '0 = Inactivo, 1 = Activo',
            ],
            'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        // Llave primaria
        $this->forge->addKey('id', true);

        // Llave foránea
        $this->forge->addForeignKey(
            'service_price_id',
            'service_prices',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Crear tabla
        $this->forge->createTable('children_age_ranges');
    }

    public function down()
    {
        $this->forge->dropTable('children_age_ranges');
    }
}
