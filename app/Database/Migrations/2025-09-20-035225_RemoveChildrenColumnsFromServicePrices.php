<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveChildrenColumnsFromServicePrices extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('service_prices', ['max_children', 'extra_children']);
    }

    public function down()
    {
        $this->forge->addColumn('service_prices', [
            'max_children' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'notes'
            ],
            'extra_children' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'extra_child_fee'
            ]
        ]);
    }
}
