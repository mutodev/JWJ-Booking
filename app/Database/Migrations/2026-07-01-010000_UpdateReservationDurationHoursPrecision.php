<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateReservationDurationHoursPrecision extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('reservations', [
            'duration_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '4,2',
                'null'       => false,
                'default'    => '1.00',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('reservations', [
            'duration_hours' => [
                'type'       => 'INT',
                'constraint' => 2,
                'null'       => false,
                'default'    => 1,
            ],
        ]);
    }
}
