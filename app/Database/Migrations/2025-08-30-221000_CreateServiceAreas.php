<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLocations extends Migration
{
    public function up()
    {
        // Metropolitan Areas
        $this->forge->addField([
            'id'         => ['type' => 'CHAR', 'constraint' => 36],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 255, 'unique' => true],
            'is_active'  => ['type' => 'BOOLEAN', 'default' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('metropolitan_areas');

        // Counties
        $this->forge->addField([
            'id'                   => ['type' => 'CHAR', 'constraint' => 36],
            'metropolitan_area_id' => ['type' => 'CHAR', 'constraint' => 36],
            'name'                 => ['type' => 'VARCHAR', 'constraint' => 255],
            'is_active'            => ['type' => 'BOOLEAN', 'default' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['metropolitan_area_id', 'name']);
        $this->forge->addForeignKey('metropolitan_area_id', 'metropolitan_areas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('counties');

        // Cities
        $this->forge->addField([
            'id'         => ['type' => 'CHAR', 'constraint' => 36],
            'county_id'  => ['type' => 'CHAR', 'constraint' => 36],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'is_active'  => ['type' => 'BOOLEAN', 'default' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['county_id', 'name']);
        $this->forge->addForeignKey('county_id', 'counties', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('cities');

        // Zipcodes
        $this->forge->addField([
            'id'         => ['type' => 'CHAR', 'constraint' => 36],
            'city_id'    => ['type' => 'CHAR', 'constraint' => 36],
            'zipcode'    => ['type' => 'VARCHAR', 'constraint' => 10],
            'is_active'  => ['type' => 'BOOLEAN', 'default' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['city_id', 'zipcode']);
        $this->forge->addForeignKey('city_id', 'cities', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('zipcodes');
    }

    public function down()
    {
        $this->forge->dropTable('zipcodes');
        $this->forge->dropTable('cities');
        $this->forge->dropTable('counties');
        $this->forge->dropTable('metropolitan_areas');
    }
}
