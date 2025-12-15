<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MoveImgToServices extends Migration
{
    public function up()
    {
        // Agregar campo img a services
        $this->forge->addColumn('services', [
            'img' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'description'
            ]
        ]);

        // Eliminar campo img de service_prices
        $this->forge->dropColumn('service_prices', 'img');
    }

    public function down()
    {
        // Agregar campo img de vuelta a service_prices
        $this->forge->addColumn('service_prices', [
            'img' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'performers_count'
            ]
        ]);

        // Eliminar campo img de services
        $this->forge->dropColumn('services', 'img');
    }
}
