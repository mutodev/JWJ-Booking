<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOverrideCountyToZipcodes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('zipcodes', [
            'override_county_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 36,
                'null'       => true,
                'default'    => null,
                'after'      => 'zone_type',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('zipcodes', 'override_county_id');
    }
}
