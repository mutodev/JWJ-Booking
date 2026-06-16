<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Syncs all DB email template bodies with the current PHP fallback files.
 * Fixes: logo 220px, removes Reservation ID badge, adds T&C to payment_notification,
 * adds {{total_duration}} row to payment_confirmation.
 */
class FixEmailTemplateBodiesSeeder extends Seeder
{
    public function run()
    {
        $this->fixPaymentNotification();
        $this->fixReservationConfirmation();
        $this->fixPaymentConfirmation();
    }

    // ── payment_notification ──────────────────────────────────────────────
    private function fixPaymentNotification(): void
    {
        $body = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Information - Reservation {{reservation_id}}</title>
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
                            <h1 style="margin: 0 0 8px; font-size: 24px; font-weight: 700; color: #1F2937;">{{content_greeting_title}}</h1>
                            <p style="margin: 0 0 28px; font-size: 15px; line-height: 1.6; color: #6b7280;">{{content_intro}}</p>

                            {{description}}

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{confirmation_url}}" style="display: inline-block; background-color: #FF74B7; color: #000000; padding: 16px 40px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 700; letter-spacing: 0.3px;">
                                            {{content_button_text}}
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <h2 style="margin: 0 0 16px; font-size: 17px; font-weight: 700; color: #1F2937;">Reservation Details</h2>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Customer</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">{{customer_name}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Service</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;">{{service_name}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Event Date</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">{{event_date}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Event Time</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;">{{event_time}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Location</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">{{event_address}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Number of Children</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;">{{children_count}}</td>
                                </tr>
                                {{birthday_child_name}}
                                <tr>
                                    <td style="padding: 14px 16px; font-size: 16px; font-weight: 700; color: #1F2937; background-color: #FFF0F6; border-bottom: none;">Total Amount</td>
                                    <td style="padding: 14px 16px; font-size: 20px; font-weight: 700; color: #FF74B7; background-color: #FFF0F6; border-bottom: none;">${{total_amount}}</td>
                                </tr>
                            </table>

                            <h2 style="margin: 0 0 14px; font-size: 17px; font-weight: 700; color: #1F2937;">{{content_steps_heading}}</h2>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                <tr>
                                    <td width="36" valign="top">
                                        <div style="width: 28px; height: 28px; background-color: #FFEF81; border: 2px solid #000000; border-radius: 50%; color: #000000; font-size: 14px; font-weight: 700; text-align: center; line-height: 28px;">1</div>
                                    </td>
                                    <td style="padding-left: 8px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #374151;">{{content_step1}}</p>
                                    </td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td width="36" valign="top">
                                        <div style="width: 28px; height: 28px; background-color: #FFEF81; border: 2px solid #000000; border-radius: 50%; color: #000000; font-size: 14px; font-weight: 700; text-align: center; line-height: 28px;">2</div>
                                    </td>
                                    <td style="padding-left: 8px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #374151;">{{content_step2}}</p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px;">
                                <tr>
                                    <td style="background-color: #FFF9E6; border-left: 4px solid #FFEF81; border-radius: 0 8px 8px 0; padding: 14px 18px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #92400e;"><strong>Important:</strong> {{content_important_note}}</p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                                <tr>
                                    <td style="background-color: #f9fafb; padding: 14px 18px; border-bottom: 1px solid #e5e7eb;">
                                        <p style="margin: 0; font-size: 13px; font-weight: 700; color: #1F2937; text-transform: uppercase; letter-spacing: 0.5px;">Terms &amp; Conditions</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 18px;">
                                        <p style="margin: 0 0 10px; font-size: 12px; line-height: 1.6; color: #374151;">By submitting payment, the client agrees to these Terms &amp; Conditions.</p>
                                        <p style="margin: 0 0 10px; font-size: 12px; line-height: 1.6; color: #374151;"><strong>Payment:</strong> Full payment is required to confirm the service. The payment link includes a 2.9% + $0.30 processing fee. Gratuity is not included and is at the client\'s discretion. We accept credit cards, Apple Pay, Google Pay, and ACH transfers only.</p>
                                        <p style="margin: 0 0 6px; font-size: 12px; line-height: 1.6; color: #374151;"><strong>Rescheduling:</strong> No fee applies if the request is made at least 24 hours before the event and a new date is confirmed within one year. Rescheduling requests made less than 24 hours before the event incur a fee of <strong>$100 per performer booked</strong> ($100 for a solo performer, $200 for a duo, $300 for a trio, etc.).</p>
                                        <p style="margin: 0 0 4px; font-size: 12px; line-height: 1.6; color: #374151;"><strong>Cancellation:</strong></p>
                                        <p style="margin: 0 0 2px; font-size: 12px; line-height: 1.6; color: #374151; padding-left: 12px;">&bull; More than 14 days before the event: 50% refund; remaining 50% issued as a credit valid for one year.</p>
                                        <p style="margin: 0 0 2px; font-size: 12px; line-height: 1.6; color: #374151; padding-left: 12px;">&bull; 14 days to 24 hours before the event: No refund; 100% issued as a credit valid for one year.</p>
                                        <p style="margin: 0 0 2px; font-size: 12px; line-height: 1.6; color: #374151; padding-left: 12px;">&bull; Less than 24 hours before the event: Payment is forfeited and non-transferable, unless the client requests to reschedule.</p>
                                        <p style="margin: 0 0 10px; font-size: 12px; line-height: 1.6; color: #374151; padding-left: 12px;">&bull; If Jam with Jamie cancels or does not provide the agreed services, a full refund will be issued.</p>
                                        <p style="margin: 0 0 10px; font-size: 12px; line-height: 1.6; color: #374151;"><strong>Credits:</strong> Credits are valid for one year from the original event date. Expired credits have no cash value and are forfeited. If pricing increases before the new booking date, the client is responsible for any difference. Any applicable rescheduling fees must be paid before a new date is confirmed.</p>
                                        <p style="margin: 0 0 4px; font-size: 12px; line-height: 1.6; color: #374151;"><strong>Timing, Delays &amp; Additional Time:</strong> Performers may delay the start time by up to 10 minutes if necessary. Client-requested delays beyond that incur the following fees:</p>
                                        <p style="margin: 0 0 2px; font-size: 12px; line-height: 1.6; color: #374151; padding-left: 12px;">&bull; Up to 20 minutes: <strong>$50</strong></p>
                                        <p style="margin: 0 0 2px; font-size: 12px; line-height: 1.6; color: #374151; padding-left: 12px;">&bull; 21&ndash;40 minutes: <strong>$100</strong></p>
                                        <p style="margin: 0 0 10px; font-size: 12px; line-height: 1.6; color: #374151; padding-left: 12px;">&bull; More than 40 minutes: Performance may be forfeited if accommodation is not possible. If performers are able to stay, an additional <strong>$200</strong> fee applies.</p>
                                        <p style="margin: 0 0 4px; font-size: 12px; line-height: 1.6; color: #374151;">Any post-event delay or extension fees will be invoiced and must be paid within 24 hours; otherwise, the card on file may be charged.</p>
                                        <p style="margin: 0 0 4px; font-size: 12px; line-height: 1.6; color: #374151;">The &ldquo;Happy Birthday&rdquo; song is included upon request for birthday parties, with up to 5 minutes of wait time after the final song. Additional time requests are billed as follows:</p>
                                        <p style="margin: 0 0 2px; font-size: 12px; line-height: 1.6; color: #374151; padding-left: 12px;">&bull; Up to 20 additional minutes: <strong>$50</strong></p>
                                        <p style="margin: 0 0 10px; font-size: 12px; line-height: 1.6; color: #374151; padding-left: 12px;">&bull; 21&ndash;30 additional minutes: <strong>$100</strong></p>
                                        <p style="margin: 0 0 10px; font-size: 12px; line-height: 1.6; color: #374151;"><strong>Performer Assignments:</strong> Performer assignments are subject to change based on availability, location, illness, emergencies, and scheduling needs. While we will make every effort to honor performer preferences, specific performers cannot be guaranteed. Performer contact information is not shared. Performer biographies will be provided in the week-of-event confirmation email.</p>
                                        <p style="margin: 0 0 10px; font-size: 12px; line-height: 1.6; color: #374151;"><strong>Safety &amp; Equipment:</strong> The client is responsible for supervising all children and guests. Guests may not handle performer instruments, speakers, cables, microphones, or other equipment. The client is responsible for any damage caused to equipment by attendees and agrees to reimburse repair or replacement costs.</p>
                                        <p style="margin: 0; font-size: 12px; line-height: 1.6; color: #374151;"><strong>Liability:</strong> The client is responsible for the supervision and safety of all event attendees. To the fullest extent permitted by law, the client agrees to release and hold harmless Jam with Jamie, its employees, and contractors from claims arising from the actions of event attendees or conditions outside of Jam with Jamie\'s reasonable control.</p>
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

        $this->db->table('email_templates')
            ->where('slug', 'payment_notification')
            ->update(['body' => $body]);

        echo "payment_notification body updated.\n";
    }

    // ── reservation_confirmation ──────────────────────────────────────────
    private function fixReservationConfirmation(): void
    {
        $body = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Confirmed - {{reservation_id}}</title>
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
                            <h1 style="margin: 0 0 8px; font-size: 24px; font-weight: 700; color: #1F2937;">{{content_title}}</h1>
                            <p style="margin: 0 0 28px; font-size: 15px; line-height: 1.6; color: #6b7280;">{{content_intro}}</p>

                            <h2 style="margin: 0 0 16px; font-size: 17px; font-weight: 700; color: #1F2937;">Your Reservation Details</h2>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Customer</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">{{customer_name}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Service</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;">{{service_name}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Event Date</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">{{event_date}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Event Time</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;">{{event_time}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Location</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">{{event_address}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Number of Children</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;">{{children_count}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 14px 16px; font-size: 16px; font-weight: 700; color: #1F2937; background-color: #FFF0F6; border-bottom: none;">Total Amount</td>
                                    <td style="padding: 14px 16px; font-size: 20px; font-weight: 700; color: #FF74B7; background-color: #FFF0F6; border-bottom: none;">${{total_amount}}</td>
                                </tr>
                            </table>

                            <h2 style="margin: 0 0 14px; font-size: 17px; font-weight: 700; color: #1F2937;">{{content_steps_heading}}</h2>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                <tr>
                                    <td width="36" valign="top">
                                        <div style="width: 28px; height: 28px; background-color: #FFEF81; border: 2px solid #000000; border-radius: 50%; color: #000000; font-size: 14px; font-weight: 700; text-align: center; line-height: 28px;">1</div>
                                    </td>
                                    <td style="padding-left: 8px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #374151;">{{content_step1}}</p>
                                    </td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                <tr>
                                    <td width="36" valign="top">
                                        <div style="width: 28px; height: 28px; background-color: #FFEF81; border: 2px solid #000000; border-radius: 50%; color: #000000; font-size: 14px; font-weight: 700; text-align: center; line-height: 28px;">2</div>
                                    </td>
                                    <td style="padding-left: 8px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #374151;">{{content_step2}}</p>
                                    </td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td width="36" valign="top">
                                        <div style="width: 28px; height: 28px; background-color: #FFEF81; border: 2px solid #000000; border-radius: 50%; color: #000000; font-size: 14px; font-weight: 700; text-align: center; line-height: 28px;">3</div>
                                    </td>
                                    <td style="padding-left: 8px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #374151;">{{content_step3}}</p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 8px;">
                                <tr>
                                    <td style="background-color: #FFF9E6; border-left: 4px solid #FFEF81; border-radius: 0 8px 8px 0; padding: 14px 18px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #92400e;">{{content_question_note}}</p>
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
                            <p style="margin: 0; font-size: 12px; color: rgba(0, 0, 0, 0.6);">&copy; {{current_year}} Jam with Jamie. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

        $this->db->table('email_templates')
            ->where('slug', 'reservation_confirmation')
            ->update(['body' => $body]);

        echo "reservation_confirmation body updated.\n";
    }

    // ── payment_confirmation ──────────────────────────────────────────────
    private function fixPaymentConfirmation(): void
    {
        $body = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmed - Reservation {{reservation_id}}</title>
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
                                        <div style="width: 60px; height: 60px; background-color: #d1fae5; border-radius: 50%; text-align: center; line-height: 60px; font-size: 28px; margin-bottom: 16px;">&#10003;</div>
                                    </td>
                                </tr>
                            </table>
                            <h1 style="margin: 0 0 8px; font-size: 24px; font-weight: 700; color: #1F2937; text-align: center;">{{content_title}}</h1>
                            <p style="margin: 0 0 28px; font-size: 15px; line-height: 1.6; color: #6b7280; text-align: center;">{{content_intro}}</p>

                            <h2 style="margin: 0 0 16px; font-size: 17px; font-weight: 700; color: #1F2937;">Booking Details</h2>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Customer</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">{{customer_name}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Service</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;">{{service_name}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Event Date</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">{{event_date}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Event Time</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;">{{event_time}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Location</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">{{event_address}}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Number of Children</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;">{{children_count}}</td>
                                </tr>
                                {{total_duration_row}}
                                {{gratuity_row}}
                                <tr>
                                    <td style="padding: 14px 16px; font-size: 16px; font-weight: 700; color: #1F2937; background-color: #FFF0F6; border-bottom: none;">Amount Paid</td>
                                    <td style="padding: 14px 16px; font-size: 20px; font-weight: 700; color: #FF74B7; background-color: #FFF0F6; border-bottom: none;">${{total_paid}}</td>
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

        $existing = $this->db->table('email_templates')
            ->where('slug', 'payment_confirmation')
            ->get()
            ->getRow();

        if ($existing) {
            $this->db->table('email_templates')
                ->where('slug', 'payment_confirmation')
                ->update(['body' => $body]);
            echo "payment_confirmation body updated.\n";
        } else {
            $now = date('Y-m-d H:i:s');
            $this->db->table('email_templates')->insert([
                'id'                  => 'et-payment-confirm-000000000005',
                'slug'                => 'payment_confirmation',
                'name'                => 'Payment Confirmation',
                'subject'             => 'Payment Confirmed - Jam with Jamie Reservation #{{reservation_id}}',
                'body'                => $body,
                'available_variables' => json_encode([
                    'customer_name', 'reservation_id', 'service_name',
                    'event_date', 'event_time', 'event_address',
                    'children_count', 'total_duration_row', 'total_amount',
                ]),
                'content'    => json_encode([
                    'title'        => 'Payment Confirmed!',
                    'intro'        => "Hi {{customer_name}}, your payment has been received and your event is officially booked. We're so excited to celebrate with you!",
                    'closing_note' => "If you have any questions before your event, feel free to reply to this email. We can't wait to see you!",
                ]),
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            echo "payment_confirmation template inserted.\n";
        }
    }
}
