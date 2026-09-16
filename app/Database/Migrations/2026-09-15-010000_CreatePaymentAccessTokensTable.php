<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Gateway tokens for the "pasar por nuestra plataforma antes de Stripe" flow.
 *
 * A token stands in for the raw Stripe Checkout URL in outbound emails. It is
 * long-lived (days) while the Stripe Checkout Session created behind it at
 * redemption time is short-lived (hours) — see PaymentAccessService.
 *
 * One active token per (target_type, target_id): issuing a new one for the
 * same target deletes the previous row (sliding renewal on every resend).
 *
 * NOTE: `token` was widened from VARCHAR(64) to VARCHAR(500) by a later
 * migration — see AlterPaymentAccessTokensTokenLength — once the token
 * stopped being a random hex string and became an encrypted payload. This
 * file is left as originally deployed so it stays a correct history of what
 * already ran.
 */
class CreatePaymentAccessTokensTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'token'       => ['type' => 'VARCHAR', 'constraint' => 64],
            'target_type' => ['type' => 'ENUM', 'constraint' => ['reservation', 'custom_payment_link'], 'null' => false],
            'target_id'   => ['type' => 'CHAR', 'constraint' => 36, 'null' => false],
            'expires_at'  => ['type' => 'DATETIME', 'null' => false],
            'created_at'  => ['type' => 'DATETIME', 'null' => false],
        ]);

        $this->forge->addKey('token', true);
        $this->forge->addKey(['target_type', 'target_id']);

        $this->forge->createTable('payment_access_tokens');
    }

    public function down()
    {
        $this->forge->dropTable('payment_access_tokens');
    }
}
