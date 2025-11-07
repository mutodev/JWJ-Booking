<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateBookingSessionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => false
            ],
            'session_token' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'unique' => true,
                'null' => false,
                'comment' => 'Token único para identificar la sesión del usuario'
            ],
            'customer_email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Email del cliente si fue proporcionado'
            ],
            'customer_phone' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'comment' => 'Teléfono del cliente si fue proporcionado'
            ],
            'step_reached' => [
                'type' => 'INT',
                'constraint' => 2,
                'default' => 1,
                'null' => false,
                'comment' => 'Paso más lejano alcanzado en el wizard'
            ],
            'form_data' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Datos del formulario guardados automáticamente'
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'completed', 'abandoned'],
                'default' => 'active',
                'null' => false,
                'comment' => 'Estado de la sesión'
            ],
            'reservation_id' => [
                'type' => 'CHAR',
                'constraint' => 36,
                'null' => true,
                'comment' => 'ID de la reserva completada (si aplica)'
            ],
            'last_activity_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
                'comment' => 'Última actividad registrada'
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => new RawSql('CURRENT_TIMESTAMP')
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => null,
                'on_update' => new RawSql('CURRENT_TIMESTAMP')
            ],
            'deleted_at' => [
                'type' => 'TIMESTAMP',
                'null' => true
            ]
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('customer_email');
        $this->forge->addKey('status');
        $this->forge->addKey('last_activity_at');
        $this->forge->addForeignKey('reservation_id', 'reservations', 'id', 'SET NULL', 'SET NULL');

        $this->forge->createTable('booking_sessions');
    }

    public function down()
    {
        $this->forge->dropTable('booking_sessions');
    }
}
