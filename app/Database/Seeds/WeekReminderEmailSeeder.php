<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class WeekReminderEmailSeeder extends Seeder
{
    public function run()
    {
        $content = json_encode([
            'title'        => 'Your event is coming up!',
            'intro'        => 'Hi {{customer_name}}, just a friendly reminder that your Jam with Jamie event is one week away. We\'re so excited to celebrate with you!',
            'closing_note' => 'If you need to make any changes or have questions before the event, please reply to this email and we\'ll be happy to help.',
        ]);

        $body = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Event is Coming Up!</title>
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
                                        <div style="width: 60px; height: 60px; background-color: #FFF0F6; border-radius: 50%; text-align: center; line-height: 60px; font-size: 28px; margin-bottom: 16px;">&#127881;</div>
                                    </td>
                                </tr>
                            </table>
                            <h1 style="margin: 0 0 8px; font-size: 24px; font-weight: 700; color: #1F2937; text-align: center;">{{content_title}}</h1>
                            <p style="margin: 0 0 28px; font-size: 15px; line-height: 1.7; color: #6b7280; text-align: center;">{{content_intro}}</p>

                            <h2 style="margin: 0 0 16px; font-size: 17px; font-weight: 700; color: #1F2937;">Event Details</h2>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Service</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">{{service_name}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Date</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;">{{event_date}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Time</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">{{event_time}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%;">Location</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937;">{{event_address}}</td>
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
            'slug'      => 'week_reminder',
            'name'      => 'Week-of-Event Reminder',
            'subject'   => 'Your Jam with Jamie event is one week away! 🎉',
            'body'      => $body,
            'content'   => $content,
            'is_active' => 1,
        ];

        $existing = $this->db->table('email_templates')
            ->where('slug', 'week_reminder')
            ->get()
            ->getRow();

        if ($existing) {
            $this->db->table('email_templates')
                ->where('slug', 'week_reminder')
                ->update([
                    'name'      => $template['name'],
                    'subject'   => $template['subject'],
                    'body'      => $template['body'],
                    'content'   => $template['content'],
                    'is_active' => 1,
                ]);
            echo "week_reminder template updated.\n";
        } else {
            $this->db->table('email_templates')->insert(array_merge($template, [
                'id'         => esc(bin2hex(random_bytes(9))),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]));
            echo "week_reminder template inserted.\n";
        }
    }
}
