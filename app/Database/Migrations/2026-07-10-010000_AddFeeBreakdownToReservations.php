<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFeeBreakdownToReservations extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reservations', [
            'travel_fee' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
                'default' => 0.00,
                'after' => 'expedition_fee',
            ],
            'expedite_fee' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
                'default' => 0.00,
                'after' => 'travel_fee',
            ],
        ]);

        // Legacy reservations stored travel and expedite charges together in expedition_fee.
        // Backfill as travel fee to avoid showing existing travel charges as expedite charges.
        $this->db->table('reservations')
            ->set('travel_fee', 'expedition_fee', false)
            ->where('expedition_fee >', 0)
            ->where('travel_fee', 0)
            ->update();
    }

    public function down()
    {
        $this->forge->dropColumn('reservations', ['travel_fee', 'expedite_fee']);
    }
}
