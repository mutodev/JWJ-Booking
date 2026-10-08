<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Distinguishes a reservation balance collection from a truly supplemental
 * charge. Balance links are already included in reservations.total_amount and
 * therefore must not be added again to the combined total.
 */
class AddPurposeToCustomPaymentLinks extends Migration
{
    public function up()
    {
        $this->forge->addColumn('custom_payment_links', [
            'purpose' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'additional',
                'after'      => 'reservation_id',
            ],
        ]);

        // Backfill legacy links conservatively. The old UI did not record the
        // purpose, but a live link matching the reservation's exact outstanding
        // balance is the balance-collection flow generated after recalculation.
        $links = $this->db->table('custom_payment_links')
            ->select('id, reservation_id, amount')
            ->whereIn('status', ['pending', 'paid'])
            ->where('reservation_id IS NOT NULL', null, false)
            ->get()
            ->getResultArray();

        foreach ($links as $link) {
            $reservation = $this->db->table('reservations')
                ->select('balance_due')
                ->where('id', $link['reservation_id'])
                ->get()
                ->getRowArray();

            $balance = round((float) ($reservation['balance_due'] ?? 0), 2);
            if ($balance > 0 && abs(round((float) $link['amount'], 2) - $balance) < 0.01) {
                $this->db->table('custom_payment_links')
                    ->where('id', $link['id'])
                    ->update(['purpose' => 'balance']);
            }
        }
    }

    public function down()
    {
        $this->forge->dropColumn('custom_payment_links', 'purpose');
    }
}
