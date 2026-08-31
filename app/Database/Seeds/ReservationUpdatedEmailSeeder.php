<?php

namespace App\Database\Seeds;

use App\Database\Seeds\Support\EmailTemplateSeedGuard;
use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * B6 — seeds the `reservation_updated` email template.
 *
 * Insert-only and independent: it never updates an existing row and never
 * touches any other template. If the slug already exists it does nothing, so an
 * admin edit from the panel (A2 `is_customized`) is always safe. The guard
 * trait is kept for consistency with the rest of the family.
 *
 * The email is sent MANUALLY by an admin from the reservation editor after a
 * post-payment recalculation (ReservationService::sendReservationUpdatedEmail).
 * It shows the fresh price breakdown plus, when relevant, a "Balance Due" or
 * "Refund Due" row (injected as {{balance_due_row}} / {{refund_row}}).
 */
class ReservationUpdatedEmailSeeder extends Seeder
{
    use EmailTemplateSeedGuard;

    public function run()
    {
        $existing = $this->db->table('email_templates')
            ->where('slug', 'reservation_updated')
            ->get()
            ->getRow();

        if ($existing) {
            echo "   - reservation_updated template already exists, skipping.\n";
            return;
        }

        $now = Time::now()->toDateTimeString();

        $this->db->table('email_templates')->insert([
            'id'                  => 'et-reservation-updated-0000000001',
            'slug'                => 'reservation_updated',
            'name'                => 'Reservation Updated',
            'subject'             => 'Your reservation has been updated',
            'body'                => $this->getBody(),
            'available_variables' => json_encode([
                'customer_name', 'reservation_id', 'service_name', 'event_date',
                'base_price', 'addons_total', 'extra_children_fee', 'travel_fee',
                'expedite_fee', 'discount_amount', 'total_amount', 'amount_paid',
                'balance_due', 'discount_row', 'gratuity_row', 'balance_due_row',
                'refund_row',
            ]),
            'content' => json_encode([
                'title'        => 'Your reservation has been updated',
                'intro'        => 'Hi {{customer_name}}, we have updated your reservation. Here is the current breakdown.',
                'closing_note' => 'If anything looks off, just reply to this email and we will sort it out.',
            ]),
            'is_active'  => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        echo "   - reservation_updated template inserted.\n";
    }

    private function getBody(): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Updated - Jam with Jamie</title>
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

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Reservation</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;">{{reservation_id}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Service</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">{{service_name}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Event Date</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;">{{event_date}}</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Base Service</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;">${{base_price}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Add-ons</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">${{addons_total}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Additional Children</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;">${{extra_children_fee}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Travel Fee</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">${{travel_fee}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Expedite Fee</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;">${{expedite_fee}}</td>
                                </tr>
                                {{discount_row}}
                                <tr>
                                    <td style="padding: 14px 16px; font-size: 16px; font-weight: 700; color: #1F2937; background-color: #FFF0F6;">Total</td>
                                    <td style="padding: 14px 16px; font-size: 18px; font-weight: 700; color: #FF74B7; background-color: #FFF0F6;">${{total_amount}}</td>
                                </tr>
                                {{gratuity_row}}
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Amount Paid</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">${{amount_paid}}</td>
                                </tr>
                                {{balance_due_row}}
                                {{refund_row}}
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top: 8px;">
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
