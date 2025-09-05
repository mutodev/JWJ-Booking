<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServicePricesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false
            ],
            'service_id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false
            ],
            'county_id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false
            ],
            'performers_count' => [
                'type' => 'INT',
                'constraint' => 1,
                'null' => false
            ],
            'price_type' => [
                'type' => 'ENUM',
                'constraint' => ['standard', 'jukebox'],
                'default' => 'standard',
                'null' => false
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false
            ],
            'min_duration_hours' => [
                'type' => 'INT',
                'constraint' => 2,
                'default' => 1,
                'null' => false
            ],
            'is_available' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'null' => false
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'on_update' => 'CURRENT_TIMESTAMP',
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('service_id', 'services', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('county_id', 'counties', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addUniqueKey(['service_id', 'county_id', 'performers_count', 'price_type']);
        $this->forge->createTable('service_prices');
    }

    public function down()
    {
        $this->forge->dropTable('service_prices');
    }
}
