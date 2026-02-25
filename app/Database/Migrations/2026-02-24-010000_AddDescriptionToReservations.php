<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDescriptionToReservations extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reservations', [
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'internal_notes',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('reservations', ['description']);
    }
}
