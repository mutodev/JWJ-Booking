<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCustomerConfirmedToReservations extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reservations', [
            'customer_confirmed' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
                'after'      => 'is_paid',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('reservations', 'customer_confirmed');
    }
}
