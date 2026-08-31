<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * B6 — "Modificar servicios / agregar add-ons con la reserva ya creada".
 *
 * Adds the two balance-tracking columns the recalculation flow needs:
 *
 *  - `amount_paid`  DECIMAL(10,2) NULL     — the amount actually charged
 *    (total_amount + gratuity_amount) at the moment Stripe confirmed the
 *    payment. Written only by ReservationService::handlePaymentCompleted().
 *    Historical reservations paid before B6 keep NULL: the recalculation
 *    treats a NULL `amount_paid` on a paid reservation as "they paid exactly
 *    the total_amount that was in effect right before the first recalculation"
 *    and snapshots that value per-row on that first recalculation. There is no
 *    mass backfill.
 *
 *  - `balance_due` DECIMAL(10,2) NOT NULL DEFAULT 0.00 — the outstanding
 *    amount after a post-payment recalculation, computed as
 *    max(0, total_amount - amount_paid). A negative delta (refund owed) is
 *    stored as 0.00 here; the admin UI surfaces the refund separately.
 */
class AddBalanceFieldsToReservations extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reservations', [
            'amount_paid' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'after'      => 'total_amount',
                'comment'    => 'B6: amount actually charged (total_amount + gratuity) at payment time. NULL for pre-B6 rows.',
            ],
            'balance_due' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
                'default'    => 0.00,
                'after'      => 'amount_paid',
                'comment'    => 'B6: outstanding balance after a post-payment recalculation. max(0, total_amount - amount_paid).',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('reservations', ['amount_paid', 'balance_due']);
    }
}
