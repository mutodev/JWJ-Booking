<?php

namespace App\Database\Seeds;

use App\Database\Seeds\Support\EmailTemplateSeedGuard;
use CodeIgniter\Database\Seeder;

/**
 * Surgical fix (F10): the "reservation_confirmation" email template never
 * showed the add-ons the customer selected because it had no placeholder
 * for them.
 *
 * This seeder does NOT rewrite the template body. It reads whatever body
 * is currently stored (including any manual edits made in production) and
 * inserts the {{addons_row}} placeholder right before {{total_duration_row}},
 * leaving everything else untouched. Safe to re-run: it skips if the
 * placeholder is already present or if the anchor can't be found.
 */
class AddAddonsRowToReservationConfirmationSeeder extends Seeder
{
    use EmailTemplateSeedGuard;

    public function run()
    {
        if ($this->templateIsCustomized('reservation_confirmation')) {
            echo "reservation_confirmation was customized in the admin panel — skipping.\n";
            return;
        }

        $row = $this->db->table('email_templates')
            ->select('id, body')
            ->where('slug', 'reservation_confirmation')
            ->get()
            ->getRow();

        if (!$row) {
            echo "reservation_confirmation template not found — nothing to do.\n";
            return;
        }

        if (strpos($row->body, '{{addons_row}}') !== false) {
            echo "reservation_confirmation already has {{addons_row}} — skipping.\n";
            return;
        }

        if (strpos($row->body, '{{total_duration_row}}') === false) {
            echo "reservation_confirmation has no {{total_duration_row}} anchor — skipping instead of guessing where to insert.\n";
            return;
        }

        $newBody = str_replace(
            '{{total_duration_row}}',
            "{{addons_row}}\n                                {{total_duration_row}}",
            $row->body
        );

        $this->db->table('email_templates')
            ->where('id', $row->id)
            ->update(['body' => $newBody]);

        echo "reservation_confirmation: {{addons_row}} placeholder added.\n";
    }
}
