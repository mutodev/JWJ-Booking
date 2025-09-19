<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateDurationsTable extends Migration
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
            'minutes' => [
                'type'       => 'INT',
                'constraint' => 4,
                'null'       => false,
                'comment'    => 'Duración en minutos (30, 60, 90, etc.)',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
                'comment'    => '0 = Inactivo, 1 = Activo',
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP')
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
            ],
        ]);

        // Llave primaria
        $this->forge->addPrimaryKey('id');

        // Llave foránea
        $this->forge->addForeignKey(
            'service_price_id',
            'service_prices',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Índice compuesto para evitar duplicados
        $this->forge->addUniqueKey(['service_price_id', 'minutes']);

        // Crear tabla
        $this->forge->createTable('durations');
    }

    public function down()
    {
        $this->forge->dropTable('durations');
    }
}
