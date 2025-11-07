<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAgeRangeToServices extends Migration
{
    public function up()
    {
        $this->forge->addColumn('services', [
            'age_range' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'description',
                'comment' => 'Age range for the service (e.g., "0-5 years old")'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('services', 'age_range');
    }
}
