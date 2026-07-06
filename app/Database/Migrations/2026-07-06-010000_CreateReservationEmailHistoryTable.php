<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReservationEmailHistoryTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'CHAR', 'constraint' => 36],
            'reservation_id'  => ['type' => 'CHAR', 'constraint' => 36],
            'template_id'     => ['type' => 'CHAR', 'constraint' => 36],
            'template_name'   => ['type' => 'VARCHAR', 'constraint' => 150],
            'sent_by'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'recipient_email' => ['type' => 'VARCHAR', 'constraint' => 255],
            'email_subject'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'email_body'      => ['type' => 'LONGTEXT'],
            'status'          => ['type' => 'ENUM', 'constraint' => ['Sent', 'Failed', 'Pending'], 'default' => 'Sent'],
            'sent_at'         => ['type' => 'DATETIME', 'null' => false],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('reservation_id');
        $this->forge->addKey('template_id');
        $this->forge->addKey('sent_at');
        $this->forge->addForeignKey('reservation_id', 'reservations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('template_id', 'email_templates', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('reservation_email_history');
    }

    public function down()
    {
        $this->forge->dropTable('reservation_email_history');
    }
}
