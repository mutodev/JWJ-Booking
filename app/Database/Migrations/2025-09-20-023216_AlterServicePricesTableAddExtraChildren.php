<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterServicePricesTableAddExtraChildren extends Migration
{
    public function up()
    {
        $fields = [
            'extra_children' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => 0,
                'after' => 'extra_child_fee',
                'comment' => 'Número de niños adicionales incluidos en el precio base'
            ]
        ];

        $this->forge->addColumn('service_prices', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('service_prices', ['extra_children']);
    }
}
