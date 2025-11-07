<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEventTypeToReservations extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reservations', [
            'event_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'customer_id',
                'comment' => 'Type of event: Birthday Party, Event, or One Time Jam Session'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('reservations', 'event_type');
    }
}
