<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FixDurationInEmailsSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        foreach (['payment_notification', 'reservation_confirmation'] as $slug) {
            $row = $db->table('email_templates')->where('slug', $slug)->get()->getRowArray();
            if (!$row) {
                echo "Template '{$slug}' not found.\n";
                continue;
            }

            $body = $row['body'];

            if (strpos($body, '{{total_duration_row}}') !== false) {
                echo "Template '{$slug}' already updated.\n";
                continue;
            }

            // Find the <tr> that contains the Total Amount row (unique: background-color #FFF0F6)
            $marker = 'background-color: #FFF0F6; border-bottom: none;">Total Amount';
            $markerPos = strpos($body, $marker);

            if ($markerPos === false) {
                echo "Cannot find Total Amount row in '{$slug}'.\n";
                continue;
            }

            // Find the opening <tr> of that row
            $trPos = strrpos(substr($body, 0, $markerPos), '<tr>');

            if ($trPos === false) {
                echo "Cannot find <tr> for Total Amount in '{$slug}'.\n";
                continue;
            }

            $inject  = "{{total_duration_row}}\n{{promo_code_row}}\n{{discount_row}}\n";
            $newBody = substr($body, 0, $trPos) . $inject . substr($body, $trPos);

            $db->table('email_templates')->where('slug', $slug)->update(['body' => $newBody]);
            echo "Updated '{$slug}' — duration row injected.\n";
        }
    }
}
