<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Adds {{promo_code_row}} and {{discount_row}} placeholders to email templates
 * and fixes payment_notification subject (removes "- ID: {{reservation_id}}").
 * Safe to re-run: uses str_replace so it only modifies if placeholder not already present.
 */
class FixEmailPromoDiscountSeeder extends Seeder
{
    public function run()
    {
        $this->fixPaymentNotification();
        $this->fixReservationConfirmation();
        $this->fixPaymentConfirmation();
    }

    private function fixPaymentNotification(): void
    {
        $row = $this->db->table('email_templates')
            ->where('slug', 'payment_notification')
            ->get()->getRow();

        if (!$row) {
            echo "payment_notification not found.\n";
            return;
        }

        $updates = [];

        // Fix subject: remove " - ID: {{reservation_id}}"
        $fixedSubject = str_replace(' - ID: {{reservation_id}}', '', $row->subject);
        if ($fixedSubject !== $row->subject) {
            $updates['subject'] = $fixedSubject;
            echo "payment_notification subject fixed.\n";
        }

        // Insert promo/discount rows before Total Amount
        $body = $row->body;
        if (strpos($body, '{{promo_code_row}}') === false) {
            $body = str_replace(
                "{{birthday_child_name}}\n                                <tr>
                                    <td style=\"padding: 14px 16px; font-size: 16px; font-weight: 700; color: #1F2937; background-color: #FFF0F6; border-bottom: none;\">Total Amount</td>",
                "{{birthday_child_name}}\n                                {{promo_code_row}}{{discount_row}}\n                                <tr>
                                    <td style=\"padding: 14px 16px; font-size: 16px; font-weight: 700; color: #1F2937; background-color: #FFF0F6; border-bottom: none;\">Total Amount</td>",
                $body
            );
            $updates['body'] = $body;
            echo "payment_notification promo rows added.\n";
        }

        if (!empty($updates)) {
            $this->db->table('email_templates')
                ->where('slug', 'payment_notification')
                ->update($updates);
        }
    }

    private function fixReservationConfirmation(): void
    {
        $row = $this->db->table('email_templates')
            ->where('slug', 'reservation_confirmation')
            ->get()->getRow();

        if (!$row) {
            echo "reservation_confirmation not found.\n";
            return;
        }

        $body = $row->body;
        if (strpos($body, '{{promo_code_row}}') !== false) {
            echo "reservation_confirmation already has promo rows.\n";
            return;
        }

        // Insert before Total Amount row
        $anchor = '<tr>
                                    <td style="padding: 14px 16px; font-size: 16px; font-weight: 700; color: #1F2937; background-color: #FFF0F6; border-bottom: none;">Total Amount</td>';

        $body = str_replace(
            $anchor,
            '{{promo_code_row}}{{discount_row}}' . "\n                                " . $anchor,
            $body
        );

        $this->db->table('email_templates')
            ->where('slug', 'reservation_confirmation')
            ->update(['body' => $body]);

        echo "reservation_confirmation promo rows added.\n";
    }

    private function fixPaymentConfirmation(): void
    {
        $row = $this->db->table('email_templates')
            ->where('slug', 'payment_confirmation')
            ->get()->getRow();

        if (!$row) {
            echo "payment_confirmation not found.\n";
            return;
        }

        $body = $row->body;
        if (strpos($body, '{{promo_code_row}}') !== false) {
            echo "payment_confirmation already has promo rows.\n";
            return;
        }

        // Insert before Amount Paid row
        $anchor = '<tr>
                                    <td style="padding: 14px 16px; font-size: 16px; font-weight: 700; color: #1F2937; background-color: #FFF0F6; border-bottom: none;">Amount Paid</td>';

        $body = str_replace(
            $anchor,
            '{{promo_code_row}}{{discount_row}}' . "\n                                " . $anchor,
            $body
        );

        $this->db->table('email_templates')
            ->where('slug', 'payment_confirmation')
            ->update(['body' => $body]);

        echo "payment_confirmation promo rows added.\n";
    }
}
