<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMfaToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'mfa_secret' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'is_active',
                'comment' => 'Encrypted TOTP secret; may exist while enrollment is pending',
            ],
            'mfa_enabled' => [
                'type' => 'BOOLEAN',
                'default' => false,
                'after' => 'mfa_secret',
            ],
            'mfa_confirmed_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'mfa_enabled',
            ],
            'mfa_recovery_codes' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'mfa_confirmed_at',
                'comment' => 'JSON array of password-hashed, single-use recovery codes',
            ],
            'mfa_last_used_step' => [
                'type' => 'BIGINT',
                'null' => true,
                'after' => 'mfa_recovery_codes',
                'comment' => 'Last accepted RFC 6238 time step, used to prevent replay',
            ],
            'auth_version' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 1,
                'after' => 'mfa_last_used_step',
                'comment' => 'Increment to revoke previously issued access tokens',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', [
            'mfa_secret',
            'mfa_enabled',
            'mfa_confirmed_at',
            'mfa_recovery_codes',
            'mfa_last_used_step',
            'auth_version',
        ]);
    }
}
