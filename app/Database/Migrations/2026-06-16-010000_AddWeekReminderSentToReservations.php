<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWeekReminderSentToReservations extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reservations', [
            'week_reminder_sent' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'null'    => false,
                'default' => 0,
                'after'   => 'customer_confirmed',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('reservations', 'week_reminder_sent');
    }
}
