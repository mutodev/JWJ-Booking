<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateReservationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false
            ],
            'customer_id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
                'comment' => 'Relación con tabla customers'
            ],
            'service_price_id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
                'comment' => 'Relación con tabla services (Jam Type)'
            ],
            // CAMBIO: county_id por zipcode
            'zipcode_id' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => false,
                'comment' => 'ZIP code del evento (relación con tabla zipcodes)'
            ],
            // CAMBIO: service_price_id eliminado (no necesitamos esta FK)
            'event_address' => [
                'type' => 'TEXT',
                'null' => false
            ],
            'event_date' => [
                'type' => 'DATE',
                'null' => false
            ],
            'event_time' => [
                'type' => 'TIME',
                'null' => false
            ],
            'children_count' => [
                'type' => 'INT',
                'constraint' => 4,
                'null' => false,
                'default' => 0
            ],
            'performers_count' => [
                'type' => 'INT',
                'constraint' => 1,
                'null' => false,
                'default' => 1
            ],
            'duration_hours' => [
                'type' => 'INT',
                'constraint' => 2,
                'null' => false,
                'default' => 1
            ],
            'price_type' => [
                'type' => 'ENUM',
                'constraint' => ['standard', 'jukebox'],
                'default' => 'standard',
                'null' => false
            ],
            'base_price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
                'default' => 0.00
            ],
            'addons_total' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
                'default' => 0.00
            ],
            'expedition_fee' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
                'default' => 0.00
            ],
            'extra_children_fee' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
                'default' => 0.00
            ],
            'total_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
                'default' => 0.00
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['new', 'under_review', 'confirmed', 'cancelled'],
                'default' => 'new',
                'null' => false
            ],
            'is_invoiced' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false
            ],
            'is_paid' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false
            ],
            'arrival_parking_instructions' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'entertainment_start_time' => [
                'type' => 'TIME',
                'null' => true
            ],
            'birthday_child_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'birthday_child_age' => [
                'type' => 'INT',
                'constraint' => 3,
                'null' => true
            ],
            'children_age_range' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true
            ],
            'song_requests' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'sing_happy_birthday' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false
            ],
            'customer_notes' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'internal_notes' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP')
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('service_price_id', 'service_prices', 'id', 'CASCADE', 'CASCADE');
        // CAMBIO: FK a counties por FK a zipcodes
        $this->forge->addForeignKey('zipcode_id', 'zipcodes', 'id', 'CASCADE', 'CASCADE');
        // CAMBIO: Eliminada FK a service_prices (ya no se necesita)
        $this->forge->createTable('reservations');
    }

    public function down()
    {
        $this->forge->dropTable('reservations');
    }
}