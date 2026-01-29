<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStripeFieldsToReservations extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reservations', [
            'stripe_session_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'payment_url',
            ],
            'stripe_payment_intent_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'stripe_session_id',
            ],
            'paid_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'stripe_payment_intent_id',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('reservations', ['stripe_session_id', 'stripe_payment_intent_id', 'paid_at']);
    }
}
