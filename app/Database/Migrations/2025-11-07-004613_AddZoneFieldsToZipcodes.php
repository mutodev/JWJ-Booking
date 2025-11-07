<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddZoneFieldsToZipcodes extends Migration
{
    public function up()
    {
        $fields = [
            'zone_type' => [
                'type' => 'ENUM',
                'constraint' => ['standard', 'travel_fee', 'minimum_2h', 'not_available'],
                'default' => 'standard',
                'null' => false,
                'comment' => 'Tipo de zona: standard (sin cargo), travel_fee (con cargo), minimum_2h (requiere mínimo 2h), not_available (no disponible)',
                'after' => 'zipcode'
            ],
            'travel_fee_1_performer' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'default' => null,
                'comment' => 'Cargo de viaje para 1 performer',
                'after' => 'zone_type'
            ],
            'travel_fee_2_performers' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'default' => null,
                'comment' => 'Cargo de viaje para 2 performers',
                'after' => 'travel_fee_1_performer'
            ]
        ];

        $this->forge->addColumn('zipcodes', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('zipcodes', ['zone_type', 'travel_fee_1_performer', 'travel_fee_2_performers']);
    }
}
