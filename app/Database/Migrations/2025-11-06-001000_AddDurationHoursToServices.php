<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDurationHoursToServices extends Migration
{
    public function up()
    {
        $this->forge->addColumn('services', [
            'duration_hours' => [
                'type' => 'DECIMAL',
                'constraint' => '3,1',
                'null' => false,
                'default' => 1.0,
                'comment' => 'Duración fija del servicio en horas',
                'after' => 'description'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('services', 'duration_hours');
    }
}
