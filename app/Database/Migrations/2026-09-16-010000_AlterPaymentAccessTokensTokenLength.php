<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * `token` stopped being a random 64-char hex string and became the
 * base64url-encoded output of CodeIgniter's Encryption service (AES-256-CTR +
 * HMAC-SHA512) over `{"t":target_type,"i":target_id}` — see
 * PaymentAccessTokenModel::encodeToken(). That output (64-byte HMAC + 16-byte
 * IV + ciphertext, base64url'd) comfortably exceeds 255 chars for our longest
 * target_type, hence VARCHAR(500).
 */
class AlterPaymentAccessTokensTokenLength extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('payment_access_tokens', [
            'token' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => false,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('payment_access_tokens', [
            'token' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
            ],
        ]);
    }
}
