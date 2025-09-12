<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterServicePricesTableAddChildFields extends Migration
{
    public function up()
    {
        $fields = [
            'max_children' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'amount',
                'comment' => 'Límite de niños para precio base'
            ],
            'extra_child_fee' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'after' => 'max_children',
                'comment' => 'Cargo por niño extra después del límite'
            ]
        ];

        $this->forge->addColumn('service_prices', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('service_prices', ['max_children', 'extra_child_fee']);
    }
}
