<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Servicios personalizados relacionados a una reserva (siempre cantidad 1).
 * name/detail/catalog_price son un snapshot del catálogo al momento de
 * relacionarlo, para que cambios posteriores en custom_services no alteren
 * reservas existentes. price_at_time es el valor cobrado en esta reserva.
 */
class CreateReservationCustomServicesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'CHAR',
                'constraint' => 36,
            ],
            'reservation_id' => [
                'type' => 'CHAR',
                'constraint' => 36,
            ],
            'custom_service_id' => [
                'type' => 'CHAR',
                'constraint' => 36,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'detail' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'catalog_price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'price_at_time' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'created_by' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'updated_by' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('reservation_id');
        $this->forge->addKey('custom_service_id');
        $this->forge->createTable('reservation_custom_services', true);
    }

    public function down()
    {
        $this->forge->dropTable('reservation_custom_services', true);
    }
}
