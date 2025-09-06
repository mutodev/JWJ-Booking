<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateReservationAddonsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false
            ],
            'reservation_id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false
            ],
            'addon_id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false
            ],
            'quantity' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 1,
                'null' => false
            ],
            'price_at_time' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
                'comment' => 'Precio en el momento de la reserva (snapshot)'
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP')
            ]
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('reservation_id', 'reservations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('addon_id', 'addons', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addUniqueKey(['reservation_id', 'addon_id']); // CORRECCIÓN: addUniqueKey en lugar de addUniqueKey
        $this->forge->createTable('reservation_addons');
    }

    public function down()
    {
        $this->forge->dropTable('reservation_addons');
    }
}
