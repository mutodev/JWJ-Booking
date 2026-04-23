<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateDurationHoursPrecision extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('services', [
            'duration_hours' => [
                'type' => 'DECIMAL',
                'constraint' => '4,2',
                'null' => false,
                'default' => 1.00,
            ]
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('services', [
            'duration_hours' => [
                'type' => 'DECIMAL',
                'constraint' => '3,1',
                'null' => false,
                'default' => 1.0,
            ]
        ]);
    }
}
