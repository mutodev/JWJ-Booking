<?php

namespace App\Database\Seeds;

use App\Database\Seeds\Support\EmailTemplateSeedGuard;
use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * B5 — seeds the `custom_payment_link` email template.
 *
 * Insert-only and independent: it never updates an existing row and never
 * touches any other template. If the slug already exists it does nothing, so
 * an admin edit from the panel (A2 `is_customized`) is always safe. The guard
 * trait is kept for consistency with the rest of the family.
 */
class CustomPaymentLinkEmailSeeder extends Seeder
{
    use EmailTemplateSeedGuard;

    public function run()
    {
        $existing = $this->db->table('email_templates')
            ->where('slug', 'custom_payment_link')
            ->get()
            ->getRow();

        if ($existing) {
            echo "   - custom_payment_link template already exists, skipping.\n";
            return;
        }

        $now = Time::now()->toDateTimeString();

        $this->db->table('email_templates')->insert([
            'id'                  => 'et-custom-payment-link-000000001',
            'slug'                => 'custom_payment_link',
            'name'                => 'Custom Payment Link',
            'subject'             => 'Payment Request from Jam with Jamie',
            'body'                => $this->getBody(),
            'available_variables' => json_encode([
                'customer_name', 'description', 'amount', 'payment_url',
            ]),
            'content' => json_encode([
                'title'        => 'Payment Request',
                'intro'        => 'Hi {{customer_name}}, here is your secure payment link. Click the button below to complete your payment.',
                'closing_note' => 'If you have any questions about this charge, just reply to this email.',
            ]),
            'is_active'  => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        echo "   - custom_payment_link template inserted.\n";
    }

    private function getBody(): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Request - Jam with Jamie</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f9fafb; font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);">
                    <tr>
                        <td style="background-color: #ffffff; padding: 32px 40px; text-align: center; border-bottom: 3px solid #FF74B7;">
                            <img src="{{logo_url}}" alt="Jam with Jamie" width="140" style="display: inline-block; max-width: 140px; height: auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 40px 20px;">
                            <h1 style="margin: 0 0 8px; font-size: 24px; font-weight: 700; color: #1F2937; text-align: center;">{{content_title}}</h1>
                            <p style="margin: 0 0 28px; font-size: 15px; line-height: 1.6; color: #6b7280; text-align: center;">{{content_intro}}</p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Description</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">{{description}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 14px 16px; font-size: 16px; font-weight: 700; color: #1F2937; background-color: #FFF0F6;">Amount Due</td>
                                    <td style="padding: 14px 16px; font-size: 20px; font-weight: 700; color: #FF74B7; background-color: #FFF0F6;">${{amount}}</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{payment_url}}" style="display: inline-block; background-color: #FF74B7; color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; padding: 14px 36px; border-radius: 50px;">Pay Now</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 8px; font-size: 13px; line-height: 1.5; color: #9ca3af; text-align: center;">If the button does not work, copy and paste this link into your browser:<br><span style="color: #6b7280; word-break: break-all;">{{payment_url}}</span></p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
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
    }
}
