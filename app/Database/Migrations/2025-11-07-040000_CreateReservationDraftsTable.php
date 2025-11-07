<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateReservationDraftsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false
            ],
            'session_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'comment' => 'Session ID del navegador para identificar usuario anónimo'
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Email del cliente (si ya llegó a Step 1)'
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'comment' => 'Teléfono del cliente (si ya llegó a Step 1)'
            ],
            'current_step' => [
                'type' => 'INT',
                'constraint' => 2,
                'null' => false,
                'default' => 1,
                'comment' => 'Último paso completado por el cliente'
            ],
            'form_data' => [
                'type' => 'JSON',
                'null' => false,
                'comment' => 'Datos completos del formulario en formato JSON'
            ],
            'completed' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
                'comment' => '1 si se completó la reservación, 0 si está abandonado'
            ],
            'reservation_id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => true,
                'comment' => 'ID de la reservación creada si se completó'
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
                'comment' => 'IP del cliente'
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'User agent del navegador'
            ],
            'last_activity_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
                'comment' => 'Última vez que el cliente actualizó el draft'
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP')
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('session_id');
        $this->forge->addKey('email');
        $this->forge->addKey('completed');
        $this->forge->addKey('current_step');
        $this->forge->addKey('last_activity_at');
        $this->forge->createTable('reservation_drafts');
    }

    public function down()
    {
        $this->forge->dropTable('reservation_drafts');
    }
}
