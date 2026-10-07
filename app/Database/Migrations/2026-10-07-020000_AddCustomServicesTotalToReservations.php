<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Subtotal de servicios personalizados de la reserva, separado de addons_total
 * para que el desglose lo muestre en su propia línea.
 */
class AddCustomServicesTotalToReservations extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reservations', [
            'custom_services_total' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
                'default'    => 0.00,
                'after'      => 'addons_total',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('reservations', 'custom_services_total');
    }
}
