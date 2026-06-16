<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGratuityAmountToReservations extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reservations', [
            'gratuity_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
                'default'    => 0.00,
                'after'      => 'total_amount',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('reservations', 'gratuity_amount');
    }
}
