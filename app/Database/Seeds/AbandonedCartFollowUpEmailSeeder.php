<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AbandonedCartFollowUpEmailSeeder extends Seeder
{
    public function run()
    {
        $content = json_encode([
            'title'       => 'You left something behind!',
            'intro'       => 'Hi {{customer_name}}, we noticed you started booking a Jam with Jamie experience but didn\'t complete your reservation. We\'d love to have you! Your information is saved — just click the button below to pick up where you left off.',
            'cta_label'   => 'Complete My Booking',
            'closing_note' => 'If you have any questions or need help, feel free to reply to this email — we\'re happy to assist!',
        ]);

        $body = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Booking - Jam with Jamie</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f9fafb; font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);">
                    <tr>
                        <td style="background-color: #ffffff; padding: 32px 40px; text-align: center; border-bottom: 3px solid #FF74B7;">
                            <img src="{{logo_url}}" alt="Jam with Jamie" width="220" style="display: inline-block; max-width: 220px; height: auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 40px 20px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
                                <tr>
                                    <td align="center">
                                        <div style="width: 60px; height: 60px; background-color: #FFF0F6; border-radius: 50%; text-align: center; line-height: 60px; font-size: 28px; margin-bottom: 16px;">&#127925;</div>
                                    </td>
                                </tr>
                            </table>
                            <h1 style="margin: 0 0 8px; font-size: 24px; font-weight: 700; color: #1F2937; text-align: center;">{{content_title}}</h1>
                            <p style="margin: 0 0 32px; font-size: 15px; line-height: 1.7; color: #6b7280; text-align: center;">{{content_intro}}</p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{resume_url}}" style="display: inline-block; background-color: #FF74B7; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 700; padding: 14px 36px; border-radius: 8px;">{{content_cta_label}}</a>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 8px;">
                                <tr>
                                    <td style="background-color: #FFF9E6; border-left: 4px solid #FFEF81; border-radius: 0 8px 8px 0; padding: 14px 18px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #92400e;">{{content_closing_note}}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 40px 32px; border-top: 1px solid #f0f0f0;">
                            <p style="margin: 0 0 4px; font-size: 14px; color: #1F2937;">Best regards,</p>
                            <p style="margin: 0; font-size: 14px; font-weight: 600; color: #FF74B7;">The Jam with Jamie Team</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #FF74B7; padding: 16px 40px; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: rgba(0, 0, 0, 0.6);">&copy; {{current_year}} Jam with Jamie LLC. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

        $template = [
            'slug'      => 'abandoned_cart_followup',
            'name'      => 'Abandoned Cart Follow-Up',
            'subject'   => 'Complete your Jam with Jamie booking!',
            'body'      => $body,
            'content'   => $content,
            'is_active' => 1,
        ];

        $existing = $this->db->table('email_templates')
            ->where('slug', 'abandoned_cart_followup')
            ->get()
            ->getRow();

        if ($existing) {
            $this->db->table('email_templates')
                ->where('slug', 'abandoned_cart_followup')
                ->update([
                    'name'      => $template['name'],
                    'subject'   => $template['subject'],
                    'body'      => $template['body'],
                    'content'   => $template['content'],
                    'is_active' => 1,
                ]);
            echo "abandoned_cart_followup template updated.\n";
        } else {
            $this->db->table('email_templates')->insert(array_merge($template, [
                'id'         => esc(bin2hex(random_bytes(9))),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]));
            echo "abandoned_cart_followup template inserted.\n";
        }
    }
}
