<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSuboptionToReservationAddons extends Migration
{
    public function up()
    {
        $fields = [
            'suboption' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Sub-opción seleccionada para add-ons como Jukebox Live (ej: 1h-1p, 2h-2p)',
                'after' => 'quantity'
            ]
        ];

        $this->forge->addColumn('reservation_addons', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('reservation_addons', 'suboption');
    }
}
