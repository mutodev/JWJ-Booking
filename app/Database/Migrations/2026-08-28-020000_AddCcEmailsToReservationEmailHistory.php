<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * B2 — "Agregar opcion de CC en los emails desde la plataforma".
 *
 * Stores the CC recipients actually applied to a reservation email (global
 * default CC + per-send CC, already normalized) so the admin can audit them
 * from the email history detail modal.
 */
class AddCcEmailsToReservationEmailHistory extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reservation_email_history', [
            'cc_emails' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'default'    => null,
                'after'      => 'recipient_email',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('reservation_email_history', 'cc_emails');
    }
}
