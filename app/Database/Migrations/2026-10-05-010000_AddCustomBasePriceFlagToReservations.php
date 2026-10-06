<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Marks admin-created reservations whose service price was intentionally
 * overridden. Recalculation can preserve that reservation-specific amount
 * without changing existing rows or the public booking flow.
 */
class AddCustomBasePriceFlagToReservations extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reservations', [
            'is_base_price_custom' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
                'after'      => 'base_price',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('reservations', 'is_base_price_custom');
    }
}
