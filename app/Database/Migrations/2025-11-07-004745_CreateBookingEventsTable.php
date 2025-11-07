<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateBookingEventsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false
            ],
            'booking_session_id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false,
                'comment' => 'ID de la sesión de reserva'
            ],
            'event_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'comment' => 'Tipo de evento: step_entered, field_changed, step_completed, form_submitted, session_abandoned'
            ],
            'step_number' => [
                'type' => 'INT',
                'constraint' => 2,
                'null' => true,
                'comment' => 'Número del paso si aplica'
            ],
            'field_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Nombre del campo modificado si aplica'
            ],
            'field_value' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Valor del campo (para análisis, encriptado si es sensible)'
            ],
            'event_data' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Datos adicionales del evento'
            ],
            'user_agent' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'User agent del navegador'
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
                'comment' => 'Dirección IP del usuario'
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => new RawSql('CURRENT_TIMESTAMP')
            ]
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('booking_session_id');
        $this->forge->addKey('event_type');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('booking_session_id', 'booking_sessions', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('booking_events');
    }

    public function down()
    {
        $this->forge->dropTable('booking_events');
    }
}
