<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPaymentUrlToReservations extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reservations', [
            'payment_url' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'after' => 'promo_code'
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('reservations', 'payment_url');
    }
}
