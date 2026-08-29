<?php

namespace App\Database\Seeds;

use App\Database\Seeds\Support\EmailTemplateSeedGuard;
use CodeIgniter\Database\Seeder;

class FixPaymentNotificationSubjectSeeder extends Seeder
{
    use EmailTemplateSeedGuard;

    public function run()
    {
        if ($this->safeUpdateTemplate('payment_notification', [
            'subject' => 'Payment Information for Your Event Reservation',
        ])) {
            echo "payment_notification subject updated (removed Reservation ID).\n";
        } else {
            echo "payment_notification skipped — customized in admin panel.\n";
        }
    }
}
