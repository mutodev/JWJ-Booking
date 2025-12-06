<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTravelFeeToServicePrices extends Migration
{
    public function up()
    {
        $fields = [
            'travel_fee' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'default' => 0,
                'after' => 'amount',
                'comment' => 'Tarifa de viaje según ubicación'
            ]
        ];

        $this->forge->addColumn('service_prices', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('service_prices', ['travel_fee']);
    }
}
