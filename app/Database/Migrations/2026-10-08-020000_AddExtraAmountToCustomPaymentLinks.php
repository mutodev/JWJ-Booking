<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cargo manual adicional ("Other charge") de un link con ítems:
 * amount = suma de ítems + extra_amount. NULL en links sin ítems (monto manual).
 */
class AddExtraAmountToCustomPaymentLinks extends Migration
{
    public function up()
    {
        $this->forge->addColumn('custom_payment_links', [
            'extra_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'after'      => 'amount',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('custom_payment_links', 'extra_amount');
    }
}
