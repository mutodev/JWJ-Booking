<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPromoCodeFieldsToReservations extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reservations', [
            'discount_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
                'default' => 0.00,
                'after' => 'extra_children_fee',
                'comment' => 'Discount amount from promo code'
            ],
            'promo_code' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'discount_amount',
                'comment' => 'Promo code used for discount'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('reservations', ['discount_amount', 'promo_code']);
    }
}
