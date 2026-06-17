<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Surgical patch seeder — modifies ONLY the specific values that need fixing.
 * Does NOT replace full template bodies or overwrite admin edits.
 * Safe to run multiple times (idempotent).
 */
class PatchEmailTemplatesSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // ─────────────────────────────────────────────────────────────────
        // 1. SUBJECT — Remove "- ID: {{reservation_id}}" from subject
        // ─────────────────────────────────────────────────────────────────
        $row = $db->table('email_templates')->where('slug', 'payment_notification')->get()->getRowArray();

        if (!$row) {
            echo "[SKIP] payment_notification template not found.\n";
        } else {
            $currentSubject = $row['subject'];
            $correctSubject = 'Payment Information for Your Event Reservation';

            if ($currentSubject !== $correctSubject) {
                $db->table('email_templates')
                    ->where('slug', 'payment_notification')
                    ->update(['subject' => $correctSubject]);
                echo "[OK]   payment_notification subject fixed.\n";
            } else {
                echo "[SKIP] payment_notification subject already correct.\n";
            }
        }

        // ─────────────────────────────────────────────────────────────────
        // 2. INTRO SPACING — Fix paragraph spacing in payment_notification intro
        //    Only modifies the 'intro' key inside the content JSON column.
        // ─────────────────────────────────────────────────────────────────
        $row = $db->table('email_templates')->where('slug', 'payment_notification')->get()->getRowArray();

        if ($row) {
            $content = json_decode($row['content'], true) ?? [];
            $correctIntro = 'Thank you for choosing Jam with Jamie! Below, you\'ll find your payment link and the opportunity to submit additional information to finalize your booking.<br><br>'
                . 'All Terms and Conditions have been sent to you in a separate email; by submitting payment, you agree to them, so please review everything carefully beforehand.<br><br>'
                . 'The payment link and temporary reservation will be active for the next 3 days. After this period, the link will expire, and your reservation will be automatically cancelled.<br><br>'
                . 'We do our very best to accommodate requests for changes to location or time; however, we cannot guarantee modifications once the booking is finalized.';

            if (($content['intro'] ?? '') !== $correctIntro) {
                $content['intro'] = $correctIntro;
                $db->table('email_templates')
                    ->where('slug', 'payment_notification')
                    ->update(['content' => json_encode($content)]);
                echo "[OK]   payment_notification intro spacing fixed.\n";
            } else {
                echo "[SKIP] payment_notification intro already correct.\n";
            }
        }

        // ─────────────────────────────────────────────────────────────────
        // 3. LOGO SIZE — Replace small logo widths with 300px across all templates
        //    Uses SQL REPLACE which only changes that specific substring.
        // ─────────────────────────────────────────────────────────────────
        $logoSizes = [140, 160, 220];
        $target    = 300;
        $changed   = false;

        foreach ($logoSizes as $size) {
            $old = "width=\"{$size}\" style=\"display: inline-block; max-width: {$size}px; height: auto;\"";
            $new = "width=\"{$target}\" style=\"display: inline-block; max-width: {$target}px; height: auto;\"";

            $db->query(
                "UPDATE email_templates SET body = REPLACE(body, ?, ?) WHERE body LIKE ?",
                [$old, $new, "%width=\"{$size}\"%"]
            );

            if ($db->affectedRows() > 0) {
                echo "[OK]   Logo size {$size}px → {$target}px updated.\n";
                $changed = true;
            }
        }

        if (!$changed) {
            echo "[SKIP] Logo sizes already at 300px in all templates.\n";
        }

        // ─────────────────────────────────────────────────────────────────
        // 4. DURATION ROWS — Inject {{total_duration_row}}, {{promo_code_row}},
        //    {{discount_row}} before the Total Amount row in body HTML.
        //    Only modifies templates where the placeholder is missing.
        // ─────────────────────────────────────────────────────────────────
        $slugsForDuration = ['payment_notification', 'reservation_confirmation'];

        foreach ($slugsForDuration as $slug) {
            $row = $db->table('email_templates')->where('slug', $slug)->get()->getRowArray();

            if (!$row) {
                echo "[SKIP] Template '{$slug}' not found.\n";
                continue;
            }

            $body = $row['body'];

            if (strpos($body, '{{total_duration_row}}') !== false) {
                echo "[SKIP] '{$slug}' already has duration row.\n";
                continue;
            }

            // Locate the Total Amount row by its unique background color #FFF0F6
            $marker    = 'background-color: #FFF0F6; border-bottom: none;">Total Amount';
            $markerPos = strpos($body, $marker);

            if ($markerPos === false) {
                echo "[WARN] Cannot find Total Amount row in '{$slug}' — skipping.\n";
                continue;
            }

            // Find the <tr> that opens the Total Amount row
            $trPos = strrpos(substr($body, 0, $markerPos), '<tr>');

            if ($trPos === false) {
                echo "[WARN] Cannot find <tr> for Total Amount in '{$slug}' — skipping.\n";
                continue;
            }

            $inject  = "{{total_duration_row}}\n{{promo_code_row}}\n{{discount_row}}\n";
            $newBody = substr($body, 0, $trPos) . $inject . substr($body, $trPos);

            $db->table('email_templates')->where('slug', $slug)->update(['body' => $newBody]);
            echo "[OK]   '{$slug}' — duration/promo/discount rows injected.\n";
        }

        echo "\nDone. All patches applied.\n";
    }
}
