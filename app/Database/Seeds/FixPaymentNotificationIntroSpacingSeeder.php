<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FixPaymentNotificationIntroSpacingSeeder extends Seeder
{
    public function run()
    {
        $template = $this->db->table('email_templates')
            ->where('slug', 'payment_notification')
            ->get()
            ->getRow();

        if (!$template) {
            echo "payment_notification template not found.\n";
            return;
        }

        $content = json_decode($template->content, true) ?? [];

        $content['intro'] = 'Thank you for choosing Jam with Jamie! Below, you\'ll find your payment link and the opportunity to submit additional information to finalize your booking.<br><br>'
            . 'All Terms and Conditions have been sent to you in a separate email; by submitting payment, you agree to them, so please review everything carefully beforehand.<br><br>'
            . 'The payment link and temporary reservation will be active for the next 3 days. After this period, the link will expire, and your reservation will be automatically cancelled.<br><br>'
            . 'We do our very best to accommodate requests for changes to location or time; however, we cannot guarantee modifications once the booking is finalized.';

        $this->db->table('email_templates')
            ->where('slug', 'payment_notification')
            ->update(['content' => json_encode($content)]);

        echo "payment_notification intro spacing fixed.\n";
    }
}
