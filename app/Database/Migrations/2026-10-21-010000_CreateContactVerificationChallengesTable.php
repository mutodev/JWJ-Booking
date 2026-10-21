<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContactVerificationChallengesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'CHAR', 'constraint' => 36],
            'session_id'         => ['type' => 'CHAR', 'constraint' => 36],
            'method'             => ['type' => 'ENUM', 'constraint' => ['email', 'sms']],
            'email'              => ['type' => 'VARCHAR', 'constraint' => 254],
            'phone'              => ['type' => 'VARCHAR', 'constraint' => 20],
            'destination_masked' => ['type' => 'VARCHAR', 'constraint' => 254],
            'code_hash'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'verification_hash'  => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'attempts'           => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
            'expires_at'         => ['type' => 'DATETIME'],
            'verified_at'        => ['type' => 'DATETIME', 'null' => true],
            'consumed_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'         => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['session_id', 'created_at']);
        $this->forge->addKey('verification_hash');
        $this->forge->createTable('contact_verification_challenges', true);
    }

    public function down()
    {
        $this->forge->dropTable('contact_verification_challenges', true);
    }
}
