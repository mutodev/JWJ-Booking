<?php

namespace App\Database\Seeds;

use App\Database\Seeds\Support\EmailTemplateSeedGuard;
use CodeIgniter\Database\Seeder;

/**
 * Replaces the fixed Service row in payment_notification with server-rendered
 * rows. This lets zero-priced base services disappear and reservation custom
 * services be listed without putting conditional logic in the email template.
 */
class PaymentNotificationServiceRowsSeeder extends Seeder
{
    use EmailTemplateSeedGuard;

    public function run()
    {
        $row = $this->seedGuardDb()->table('email_templates')
            ->where('slug', 'payment_notification')
            ->get()
            ->getRowArray();

        if (!$row) {
            echo "payment_notification not found.\n";
            return;
        }

        $updates = [];
        $body = (string) ($row['body'] ?? '');

        if (strpos($body, '{{service_row}}') === false) {
            $pattern = '~\s*<tr>\s*<td\b[^>]*>\s*Service\s*</td>\s*<td\b[^>]*>\s*\{\{service_name\}\}\s*</td>\s*</tr>~i';
            $replacement = "\n                                {{service_row}}\n                                {{custom_services_rows}}";
            $body = preg_replace($pattern, $replacement, $body, 1, $count);

            if ($count !== 1) {
                echo "payment_notification Service row was not found; template left unchanged.\n";
                return;
            }

            $updates['body'] = $body;
        } elseif (strpos($body, '{{custom_services_rows}}') === false) {
            $updates['body'] = str_replace(
                '{{service_row}}',
                "{{service_row}}\n                                {{custom_services_rows}}",
                $body
            );
        }

        $available = json_decode((string) ($row['available_variables'] ?? '[]'), true);
        $available = is_array($available) ? $available : [];
        foreach (['service_row', 'custom_services_rows'] as $variable) {
            if (!in_array($variable, $available, true)) {
                $available[] = $variable;
            }
        }
        $encodedAvailable = json_encode(array_values($available));
        if ($encodedAvailable !== ($row['available_variables'] ?? null)) {
            $updates['available_variables'] = $encodedAvailable;
        }

        if ($updates === []) {
            echo "payment_notification already has dynamic service rows.\n";
            return;
        }

        if ($this->safeUpdateTemplate('payment_notification', $updates)) {
            echo "payment_notification dynamic service rows installed.\n";
        } else {
            echo "payment_notification skipped — customized in admin panel.\n";
        }
    }
}
