<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * B5 — "Reservas / links de pago personalizados (monto libre y descripcion)".
 *
 * Lightweight standalone entity for arbitrary-amount payment links (late fees,
 * custom events, ...). A link MAY reference a reservation (nullable FK) so B6
 * can charge differences, but it never affects reservation totals.
 *
 * Schema frozen by acceptance criterion 1 of the B5 plan.
 */
class CreateCustomPaymentLinksTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                       => ['type' => 'CHAR', 'constraint' => 36],
            'reservation_id'           => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'customer_name'            => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'customer_email'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'description'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'amount'                   => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false],
            'currency'                 => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false, 'default' => 'usd'],
            'status'                   => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'pending'],
            'stripe_session_id'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'stripe_payment_intent_id' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'payment_url'              => ['type' => 'TEXT', 'null' => true],
            'paid_at'                  => ['type' => 'DATETIME', 'null' => true],
            'expires_at'              => ['type' => 'DATETIME', 'null' => true],
            'created_by'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'               => ['type' => 'DATETIME', 'null' => true],
            'updated_at'               => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->addKey('stripe_session_id');
        $this->forge->addKey('reservation_id');
        $this->forge->addForeignKey('reservation_id', 'reservations', 'id', 'SET NULL', 'RESTRICT');

        $this->forge->createTable('custom_payment_links');
    }

    public function down()
    {
        $this->forge->dropTable('custom_payment_links');
    }
}
