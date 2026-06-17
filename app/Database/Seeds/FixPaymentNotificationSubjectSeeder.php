<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FixPaymentNotificationSubjectSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('email_templates')
            ->where('slug', 'payment_notification')
            ->update(['subject' => 'Payment Information for Your Event Reservation']);

        echo "payment_notification subject updated (removed Reservation ID).\n";
    }
}
