<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class EmailTemplateSeeder extends Seeder
{
    public function run()
    {
        $now = Time::now()->toDateTimeString();

        $data = [
            [
                'id'                  => 'et-payment-notification-00000000001',
                'slug'                => 'payment_notification',
                'name'                => 'Payment Notification',
                'subject'             => 'Payment Information for Your Event Reservation',
                'body'                => $this->getPaymentNotificationBody(),
                'available_variables' => json_encode([
                    'customer_name', 'reservation_id', 'service_name',
                    'event_date', 'event_time', 'event_address',
                    'children_count', 'birthday_child_name',
                    'total_amount', 'description', 'confirmation_url'
                ]),
                'content'     => json_encode([
                    'greeting_title' => 'Hi {{customer_name}}!',
                    'intro'          => 'Thank you for choosing Jam with Jamie!<br><br>Here is your payment link. To finalize your booking, click &ldquo;Continue to Pay,&rdquo; and complete the requested additional information.<br><br><strong>All Terms and Conditions are included below. Submitting payment confirms accepting the Terms and Conditions, so please review carefully.</strong><br><br>The payment link will be active for the next 3 days. After this period, the link will expire, and your reservation will be cancelled.<br><br>We do our very best to accommodate requests for changes to location or time; however, we cannot guarantee modifications once the booking is finalized.<br><br>Please feel free to reach out to us with any questions.',
                    'button_text'    => 'Continue to Pay',
                    'steps_heading'  => 'Next Steps',
                    'step1'          => '<strong>Complete your payment to secure the booking</strong> &mdash; Click the button above to be redirected to the payment platform. You&rsquo;ll have the opportunity to submit any additional information or song requests.',
                    'step2'          => '<strong>Get Ready to Jam!</strong> &mdash; After payment is settled, your event will be officially booked and we&rsquo;ll reach out again the week of your event to re-confirm all the details.',
                    'important_note' => 'Please complete your event details prior to submitting payment, as this is your contract.',
                ]),
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'                  => 'et-reservation-confirm-00000000002',
                'slug'                => 'reservation_confirmation',
                'name'                => 'Reservation Confirmation',
                'subject'             => 'Reservation Received - Jam with Jamie',
                'body'                => $this->getReservationConfirmationBody(),
                'available_variables' => json_encode([
                    'customer_name', 'reservation_id', 'service_name',
                    'event_date', 'event_time', 'event_address',
                    'children_count', 'total_amount'
                ]),
                'content'     => json_encode([
                    'title'          => 'Reservation Received!',
                    'intro'          => 'Hi {{customer_name}},<br><br>Thank you for choosing Jam with Jamie!<br><br>We&rsquo;ve received your reservation request.<br><br>Our Operations Team will review availability and be in touch via email within the next 24-48 hours.<br><br>Our office hours are Monday-Friday, 9:00 a.m. to 5:00 p.m. EST. If your request was submitted over the weekend, our team will begin reviewing it on the next business day.<br><br><strong>Please note this is a reservation request only and does not confirm your booking.</strong> Once availability has been secured, we&rsquo;ll follow up with next steps and a payment link to finalize your reservation.',
                    'steps_heading'  => 'What\'s Next?',
                    'step1'          => '<strong>Our team reviews your reservation</strong> &mdash; We&rsquo;ll be in touch within the next 24-48 business hours.',
                    'step2'          => '<strong>If we are able to accommodate your event</strong> &mdash; You&rsquo;ll receive a confirmation email along with payment information to secure the booking.',
                    'step3'          => '<strong>Get ready to Jam!</strong> &mdash; After payment is settled, your event will be officially booked and we&rsquo;ll reach out again the week of your event to re-confirm all the details.',
                    'question_note'  => 'Feel free to schedule a call with us <a href="https://dfaebhb.r.af.d.sendibt2.com/tr/cl/BA0zw6FLJY27_DS_GQTLBD26ofunR0fxnaGmBdLWueGkwpQobIkkoebewhQEXdEuiNEAzUwxxNJQFHIndl7cnHDCTqYjLxO5NJMXPw7YXliVkYKgcrFlOH-j2hWj5uinw8gQ-yO_5wmpYu6OXO_seYY1KFMcny7einTn0pW2u6n5bnck0fMvGW6TYKtCDatOqsEgZ6L1Pgdb4a16vTjs4lLaew2skswUMLiRwl530SF3uVFgVuKohaNEMRj6Y3A1OsV_EJxj-voGSvaEWxZrB78HPsdHBph2fvcPQtdd2N9nStIiIjiHyZ-lZA" target="_blank" rel="noopener noreferrer">HERE.</a>',
                ]),
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'                  => 'et-welcome-000000000000000000003',
                'slug'                => 'welcome',
                'name'                => 'Welcome',
                'subject'             => 'Welcome to Jam with Jamie',
                'body'                => $this->getWelcomeBody(),
                'available_variables' => json_encode(['password']),
                'content'     => json_encode([
                    'title'             => 'Welcome aboard!',
                    'intro'             => 'Your account has been created successfully. We\'re thrilled to have you as part of the Jam with Jamie team.',
                    'security_reminder' => 'Please change your password after your first login to keep your account secure.',
                ]),
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'                  => 'et-reset-password-0000000000000004',
                'slug'                => 'reset_password',
                'name'                => 'Reset Password',
                'subject'             => 'Password Reset - Jam with Jamie',
                'body'                => $this->getResetPasswordBody(),
                'available_variables' => json_encode(['password']),
                'content'     => json_encode([
                    'title'             => 'Password Reset',
                    'intro'             => 'Your password has been successfully reset. Use the temporary password below to log in to your account.',
                    'security_reminder' => 'Please change this password immediately after logging in. If you didn\'t request this reset, contact our support team right away.',
                ]),
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'                  => 'et-payment-needed-secure-event-01',
                'slug'                => 'payment_needed_secure_event',
                'name'                => 'Payment Needed to Secure Your Event',
                'subject'             => 'Payment Needed to Secure Your Event',
                'body'                => $this->getReservationMessageBody(),
                'available_variables' => $this->getReservationEmailVariables(),
                'content'             => json_encode([
                    'message' => $this->getPaymentNeededSecureEventMessage(),
                ]),
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'                  => 'et-reservation-canceled-no-pay-02',
                'slug'                => 'reservation_cancelled_no_payment',
                'name'                => 'Your Jam with Jamie Reservation has been Canceled',
                'subject'             => 'Your Jam with Jamie Reservation has been Canceled',
                'body'                => $this->getReservationMessageBody(),
                'available_variables' => $this->getReservationEmailVariables(),
                'content'             => json_encode([
                    'message' => $this->getReservationCancelledNoPaymentMessage(),
                ]),
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'                  => 'et-thank-you-for-jamming-000003',
                'slug'                => 'thank_you_for_jamming',
                'name'                => 'Thank you for jamming with us!',
                'subject'             => 'Thank you for jamming with us!',
                'body'                => $this->getReservationMessageBody(),
                'available_variables' => $this->getReservationEmailVariables(),
                'content'             => json_encode([
                    'message' => $this->getThankYouForJammingMessage(),
                ]),
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'                  => 'et-availability-next-steps-0004',
                'slug'                => 'availability_confirmed_next_steps',
                'name'                => 'Confirmation of availability, next steps',
                'subject'             => 'Confirmation of availability, next steps',
                'body'                => $this->getReservationMessageBody(),
                'available_variables' => $this->getReservationEmailVariables(),
                'content'             => json_encode([
                    'message' => $this->getAvailabilityConfirmedNextStepsMessage(),
                ]),
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'                  => 'et-not-available-event-000005',
                'slug'                => 'not_available_for_event',
                'name'                => 'We are not available for your event',
                'subject'             => 'We are not available for your event',
                'body'                => $this->getReservationMessageBody(),
                'available_variables' => $this->getReservationEmailVariables(),
                'content'             => json_encode([
                    'message' => $this->getNotAvailableForEventMessage(),
                ]),
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'id'                  => 'et-week-of-event-reminder-0006',
                'slug'                => 'week_reminder',
                'name'                => 'Week-of-Event Reminder',
                'subject'             => 'Week-of-Event Reminder',
                'body'                => $this->getReservationMessageBody(),
                'available_variables' => $this->getWeekReminderEmailVariables(),
                'content'             => json_encode([
                    'message' => $this->getWeekReminderMessage(),
                ]),
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        foreach ($data as $template) {
            $existing = $this->db->table('email_templates')
                ->where('slug', $template['slug'])
                ->get()
                ->getRow();

            if ($existing) {
                unset($template['id'], $template['created_at']);
                $this->db->table('email_templates')
                    ->where('slug', $existing->slug)
                    ->update($template);
            } else {
                $this->db->table('email_templates')->insert($template);
            }
        }
    }

    private function getPaymentNotificationBody(): string
    {
        return '<!DOCTYPE html>
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
                            <img src="{{logo_url}}" alt="Jam with Jamie" width="300" style="display: inline-block; max-width: 300px; height: auto;">
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

                             <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                                 <tr>
                                     <td style="background-color: #f9fafb; padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
                                         <p style="margin: 0; font-size: 12px; font-weight: 700; color: #1F2937; text-transform: uppercase; letter-spacing: 0.5px;">Terms &amp; Conditions</p>
                                     </td>
                                 </tr>
                                 <tr>
                                     <td style="padding: 14px 16px;">
                                         <p style="margin: 0 0 9px; font-size: 11px; line-height: 1.55; color: #374151;">By submitting payment, the client agrees to these Terms &amp; Conditions.</p>
                                         <p style="margin: 0 0 9px; font-size: 11px; line-height: 1.55; color: #374151;"><strong>Payment:</strong> Full payment is required to confirm the service. The payment link includes a 2.9% + $0.30 processing fee. Gratuity is not included and is at the client&apos;s discretion. We accept credit cards, Apple Pay, Google Pay, and ACH transfers only.</p>
                                         <p style="margin: 0 0 6px; font-size: 11px; line-height: 1.55; color: #374151;"><strong>Rescheduling:</strong> No fee applies if the request is made at least 24 hours before the event and a new date is confirmed within one year. Rescheduling requests made less than 24 hours before the event incur a fee of <strong>$100 per performer booked</strong> ($100 for a solo performer, $200 for a duo, $300 for a trio, etc.).</p>
                                         <p style="margin: 0 0 4px; font-size: 11px; line-height: 1.55; color: #374151;"><strong>Cancellation:</strong></p>
                                         <p style="margin: 0 0 2px; font-size: 11px; line-height: 1.55; color: #374151; padding-left: 12px;">&bull; More than 14 days before the event: 50% refund; remaining 50% issued as a credit valid for one year.</p>
                                         <p style="margin: 0 0 2px; font-size: 11px; line-height: 1.55; color: #374151; padding-left: 12px;">&bull; 14 days to 24 hours before the event: No refund; 100% issued as a credit valid for one year.</p>
                                         <p style="margin: 0 0 2px; font-size: 11px; line-height: 1.55; color: #374151; padding-left: 12px;">&bull; Less than 24 hours before the event: Payment is forfeited and non-transferable, unless the client requests to reschedule.</p>
                                         <p style="margin: 0 0 9px; font-size: 11px; line-height: 1.55; color: #374151; padding-left: 12px;">&bull; If Jam with Jamie cancels or does not provide the agreed services, a full refund will be issued.</p>
                                         <p style="margin: 0 0 9px; font-size: 11px; line-height: 1.55; color: #374151;"><strong>Credits:</strong> Credits are valid for one year from the original event date. Expired credits have no cash value and are forfeited. If pricing increases before the new booking date, the client is responsible for any difference. Any applicable rescheduling fees must be paid before a new date is confirmed.</p>
                                         <p style="margin: 0 0 4px; font-size: 11px; line-height: 1.55; color: #374151;"><strong>Timing, Delays &amp; Additional Time:</strong> Performers may delay the start time by up to 10 minutes if necessary. Client-requested delays beyond that incur the following fees:</p>
                                         <p style="margin: 0 0 2px; font-size: 11px; line-height: 1.55; color: #374151; padding-left: 12px;">&bull; Up to 20 minutes: <strong>$50</strong></p>
                                         <p style="margin: 0 0 2px; font-size: 11px; line-height: 1.55; color: #374151; padding-left: 12px;">&bull; 21&ndash;40 minutes: <strong>$100</strong></p>
                                         <p style="margin: 0 0 9px; font-size: 11px; line-height: 1.55; color: #374151; padding-left: 12px;">&bull; More than 40 minutes: Performance may be forfeited if accommodation is not possible. If performers are able to stay, an additional <strong>$200</strong> fee applies.</p>
                                         <p style="margin: 0 0 4px; font-size: 11px; line-height: 1.55; color: #374151;">Any post-event delay or extension fees will be invoiced and must be paid within 24 hours; otherwise, the card on file may be charged.</p>
                                         <p style="margin: 0 0 4px; font-size: 11px; line-height: 1.55; color: #374151;">The &ldquo;Happy Birthday&rdquo; song is included upon request for birthday parties, with up to 5 minutes of wait time after the final song. Additional time requests are billed as follows:</p>
                                         <p style="margin: 0 0 2px; font-size: 11px; line-height: 1.55; color: #374151; padding-left: 12px;">&bull; Up to 20 additional minutes: <strong>$50</strong></p>
                                         <p style="margin: 0 0 9px; font-size: 11px; line-height: 1.55; color: #374151; padding-left: 12px;">&bull; 21&ndash;30 additional minutes: <strong>$100</strong></p>
                                         <p style="margin: 0 0 9px; font-size: 11px; line-height: 1.55; color: #374151;"><strong>Performer Assignments:</strong> Performer assignments are subject to change based on availability, location, illness, emergencies, and scheduling needs. While we will make every effort to honor performer preferences, specific performers cannot be guaranteed. Performer contact information is not shared. Performer biographies will be provided in the week-of-event confirmation email.</p>
                                         <p style="margin: 0 0 9px; font-size: 11px; line-height: 1.55; color: #374151;"><strong>Safety &amp; Equipment:</strong> The client is responsible for supervising all children and guests. Guests may not handle performer instruments, speakers, cables, microphones, or other equipment. The client is responsible for any damage caused to equipment by attendees and agrees to reimburse repair or replacement costs.</p>
                                         <p style="margin: 0; font-size: 11px; line-height: 1.55; color: #374151;"><strong>Liability:</strong> The client is responsible for the supervision and safety of all event attendees. To the fullest extent permitted by law, the client agrees to release and hold harmless Jam with Jamie, its employees, and contractors from claims arising from the actions of event attendees or conditions outside of Jam with Jamie&apos;s reasonable control.</p>
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
                                {{total_duration_row}}
                                {{promo_code_row}}
                                {{discount_row}}
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

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 8px;">
                                <tr>
                                    <td style="background-color: #FFF9E6; border-left: 4px solid #FFEF81; border-radius: 0 8px 8px 0; padding: 14px 18px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #92400e;"><strong>Important:</strong> {{content_important_note}}</p>
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

    private function getReservationConfirmationBody(): string
    {
        return '<!DOCTYPE html>
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
                            <img src="{{logo_url}}" alt="Jam with Jamie" width="300" style="display: inline-block; max-width: 300px; height: auto;">
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
                                {{total_duration_row}}
                                {{promo_code_row}}
                                {{discount_row}}
                                <tr>
                                    <td style="padding: 14px 16px; font-size: 16px; font-weight: 700; color: #1F2937; background-color: #FFF0F6; border-bottom: none;">Total Amount</td>
                                    <td style="padding: 14px 16px; font-size: 20px; font-weight: 700; color: #FF74B7; background-color: #FFF0F6; border-bottom: none;">${{total_amount}}</td>
                                </tr>
                            </table>

                            <h2 style="margin: 0 0 14px; font-size: 17px; font-weight: 700; color: #1F2937;">{{content_steps_heading}}</h2>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                <tr>
                                    <td width="36" valign="top"><div style="width: 28px; height: 28px; background-color: #FFEF81; border: 2px solid #000000; border-radius: 50%; color: #000000; font-size: 14px; font-weight: 700; text-align: center; line-height: 28px;">1</div></td>
                                    <td style="padding-left: 8px;"><p style="margin: 0; font-size: 14px; line-height: 1.5; color: #374151;">{{content_step1}}</p></td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                <tr>
                                    <td width="36" valign="top"><div style="width: 28px; height: 28px; background-color: #FFEF81; border: 2px solid #000000; border-radius: 50%; color: #000000; font-size: 14px; font-weight: 700; text-align: center; line-height: 28px;">2</div></td>
                                    <td style="padding-left: 8px;"><p style="margin: 0; font-size: 14px; line-height: 1.5; color: #374151;">{{content_step2}}</p></td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td width="36" valign="top"><div style="width: 28px; height: 28px; background-color: #FFEF81; border: 2px solid #000000; border-radius: 50%; color: #000000; font-size: 14px; font-weight: 700; text-align: center; line-height: 28px;">3</div></td>
                                    <td style="padding-left: 8px;"><p style="margin: 0; font-size: 14px; line-height: 1.5; color: #374151;">{{content_step3}}</p></td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 8px;">
                                <tr>
                                    <td style="background-color: #FFF9E6; border-left: 4px solid #FFEF81; border-radius: 0 8px 8px 0; padding: 14px 18px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #92400e;"><strong>Questions?</strong> {{content_question_note}}</p>
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

    private function getWelcomeBody(): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Jam with Jamie</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f5f7; font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f5f7; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 36px 40px; text-align: center;">
                            <img src="{{logo_url}}" alt="Jam with Jamie" width="300" style="display: inline-block; max-width: 300px; height: auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 40px 20px;">
                            <h1 style="margin: 0 0 8px; font-size: 24px; font-weight: 700; color: #1F2937;">{{content_title}}</h1>
                            <p style="margin: 0 0 24px; font-size: 15px; line-height: 1.6; color: #6b7280;">{{content_intro}}</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="background-color: #f0f0ff; border: 1px solid #e0e0f5; border-radius: 8px; padding: 20px 24px;">
                                        <p style="margin: 0 0 6px; font-size: 13px; font-weight: 600; color: #667eea; text-transform: uppercase; letter-spacing: 0.5px;">Your Temporary Password</p>
                                        <p style="margin: 0; font-size: 22px; font-weight: 700; color: #1F2937; letter-spacing: 1px; font-family: \'Courier New\', Courier, monospace;">{{password}}</p>
                                    </td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="background-color: #fef3cd; border-left: 4px solid #f59e0b; border-radius: 0 8px 8px 0; padding: 14px 18px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #92400e;"><strong>Security Reminder:</strong> {{content_security_reminder}}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 40px 32px; border-top: 1px solid #f0f0f0;">
                            <p style="margin: 0 0 4px; font-size: 14px; color: #1F2937;">Best regards,</p>
                            <p style="margin: 0; font-size: 14px; font-weight: 600; color: #667eea;">The Jam with Jamie Team</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 16px 40px; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: rgba(255, 255, 255, 0.8);">&copy; {{current_year}} Jam with Jamie LLC. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    private function getResetPasswordBody(): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - Jam with Jamie</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f5f7; font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f5f7; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 36px 40px; text-align: center;">
                            <img src="{{logo_url}}" alt="Jam with Jamie" width="300" style="display: inline-block; max-width: 300px; height: auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 40px 20px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
                                <tr>
                                    <td align="center">
                                        <div style="width: 56px; height: 56px; background-color: #f0f0ff; border-radius: 50%; line-height: 56px; text-align: center; font-size: 28px; margin-bottom: 16px;">&#128274;</div>
                                    </td>
                                </tr>
                            </table>
                            <h1 style="margin: 0 0 8px; font-size: 24px; font-weight: 700; color: #1F2937; text-align: center;">{{content_title}}</h1>
                            <p style="margin: 0 0 28px; font-size: 15px; line-height: 1.6; color: #6b7280; text-align: center;">{{content_intro}}</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="background-color: #f0f0ff; border: 1px solid #e0e0f5; border-radius: 8px; padding: 20px 24px; text-align: center;">
                                        <p style="margin: 0 0 6px; font-size: 13px; font-weight: 600; color: #667eea; text-transform: uppercase; letter-spacing: 0.5px;">Your New Temporary Password</p>
                                        <p style="margin: 0; font-size: 22px; font-weight: 700; color: #1F2937; letter-spacing: 1px; font-family: \'Courier New\', Courier, monospace;">{{password}}</p>
                                    </td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="background-color: #fef3cd; border-left: 4px solid #f59e0b; border-radius: 0 8px 8px 0; padding: 14px 18px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #92400e;"><strong>Security Reminder:</strong> {{content_security_reminder}}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 40px 32px; border-top: 1px solid #f0f0f0;">
                            <p style="margin: 0 0 4px; font-size: 14px; color: #1F2937;">Best regards,</p>
                            <p style="margin: 0; font-size: 14px; font-weight: 600; color: #667eea;">The Jam with Jamie Team</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 16px 40px; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: rgba(255, 255, 255, 0.8);">&copy; {{current_year}} Jam with Jamie LLC. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    private function getReservationEmailVariables(): string
    {
        return json_encode([
            'customer_name', 'reservation_id', 'service_name',
            'event_date', 'event_time', 'event_address',
            'children_count', 'total_amount', 'confirmation_url',
            'payment_url', 'payment_link'
        ]);
    }

    private function getWeekReminderEmailVariables(): string
    {
        return json_encode([
            'customer_name', 'reservation_id', 'service_name',
            'event_date', 'event_time', 'event_address',
            'entertainment_start_time', 'performers_count',
            'performers_names', 'event_contact_name', 'event_contact_phone',
            'performer_venmo_handles',
            'entertainment_start_time_row', 'performers_row',
        ]);
    }

    private function getReservationMessageBody(): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jam with Jamie</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f9fafb; font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);">
                    <tr>
                        <td style="background-color: #ffffff; padding: 32px 40px; text-align: center; border-bottom: 3px solid #FF74B7;">
                            <img src="{{logo_url}}" alt="Jam with Jamie" width="300" style="display: inline-block; max-width: 300px; height: auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 40px 32px; font-size: 15px; line-height: 1.7; color: #374151;">
                            {{content_message}}
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

    private function getReservationSummaryTable(): string
    {
        return '<p style="margin: 18px 0 8px;"><strong>Date:</strong> {{event_date}}</p>
<p style="margin: 0 0 8px;"><strong>Time:</strong> {{event_time}}</p>
<p style="margin: 0 0 8px;"><strong>Address:</strong> {{event_address}}</p>
<p style="margin: 0 0 24px;"><strong>Service:</strong> {{service_name}}</p>';
    }

    private function getWeekReminderSummaryTable(): string
    {
        return '<p style="margin: 18px 0 8px;"><strong>Event Date:</strong> {{event_date}}</p>
<p style="margin: 0 0 8px;"><strong>Event Time:</strong> {{event_time}}</p>
<p style="margin: 0 0 8px;"><strong>Entertainment Start Time:</strong> {{entertainment_start_time}}</p>
<p style="margin: 0 0 8px;"><strong>Address:</strong> {{event_address}}</p>
<p style="margin: 0 0 8px;"><strong>Service:</strong> {{service_name}}</p>
<p style="margin: 0 0 24px;"><strong>Performer(s):</strong> {{performers_count}}</p>';
    }

    private function getSummaryTable(array $rows): string
    {
        $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 18px 0 24px; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">';

        foreach ($rows as $row) {
            if (is_string($row)) {
                $html .= "\n    " . $row;
                continue;
            }

            [$label, $value, $shaded] = $row;
            $background = $shaded ? ' background-color: #f9fafb;' : '';
            $html .= "\n    " . '<tr><td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280;' . $background . ' width: 40%; border-bottom: 1px solid #e5e7eb;">' . $label . '</td><td style="padding: 12px 16px; font-size: 14px; color: #1F2937;' . $background . ' border-bottom: 1px solid #e5e7eb;">' . $value . '</td></tr>';
        }

        return $html . "\n</table>";
    }

    private function getWeekReminderMessage(): string
    {
        return '<p style="margin: 0 0 16px;">Hi {{customer_name}},</p>
<p style="margin: 0 0 12px;">We\'re so excited to jam with you soon! The details for your upcoming event are below, please be sure to review them carefully.</p>
<p style="margin: 0 0 12px;">Your performer(s) are: {{performers_names}}, and they will arrive 10&ndash;15 minutes prior to the entertainment start time to get set up.</p>' . $this->getWeekReminderSummaryTable() . '
<p style="margin: 0 0 12px;">&#11088; <strong><u>Important Information and Reminders</u></strong> &#11088;</p>
<ol style="margin: 0 0 16px; padding-left: 22px;">
<li>The Jam with Jamie contact person on the day of your event is {{event_contact_name}}, and can be reached at {{event_contact_phone}}.</li>
<li>Performer(s) may be able to accommodate a delay of up to 10 minutes past the scheduled start time. If the performer(s) are unable to, and are asked to start late, the set will be shortened accordingly and will still end at the originally scheduled time. Additional wait time will incur late fees as outlined in the Terms &amp; Conditions.</li>
<li>Gratuity is never required, but is greatly appreciated. For your convenience, the performers\' Venmo handles are: {{performer_venmo_handles}}</li>
<li>If outdoors, please reserve a shaded area away from major distractions such as pools or inflatables. If indoors, please choose a space away from toys and play areas.</li>
<li>Space setup: Allow approximately 15-20 ft of space for the jam session, so there is room for the kids to engage with the movement and activities comfortably. Avoid hallways, corners, or any spot that may restrict movement.</li>
<li>We encourage grown-ups to join in! The kids love seeing everyone sing, dance, and jam together!</li>
</ol>
<p style="margin: 0 0 16px;">For everyone\'s safety, we ask that children stay clear of the performer instruments and sound equipment. Props will be passed out and collected by the performers during the set to maximize interaction!</p>
<p style="margin: 0;"><strong>Please reply to this email to confirm receipt and that all reservation details are correct.</strong> If there is anything you\'d like to add or change, please let us know as soon as possible so we can review and update your booking accordingly. If we do not receive a response, we will assume that all details have been checked and are correct.</p>
<p style="margin: 0;"><br></p>
<p style="margin: 0;">We can\'t wait to celebrate with you!</p>';
    }

    private function getPaymentNeededSecureEventMessage(): string
    {
        $summary = $this->getReservationSummaryTable();

        return '<p style="margin: 0 0 16px;">Hi {{customer_name}},</p>
<p style="margin: 0 0 16px;">This is a friendly reminder that we have not yet received payment for your upcoming Jam with Jamie booking.</p>
<p style="margin: 0 0 16px;">To secure your event date and finalize your reservation, please complete the payment within 24 hours or your reservation request will be cancelled.</p>
<p style="margin: 0 0 16px;">If you have any questions, please let us know.</p>
<p style="margin: 0 0 12px; font-weight: 700; color: #1F2937;">Reservation Summary</p>' . $summary . '
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 24px 0;">
    <tr><td align="center"><a href="{{payment_url}}" style="display: inline-block; background-color: #FF74B7; color: #000000; padding: 16px 40px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 700; letter-spacing: 0.3px;">Complete Payment</a></td></tr>
</table>';
    }

    private function getReservationCancelledNoPaymentMessage(): string
    {
        return '<p style="margin: 0 0 16px;">Hi {{customer_name}},</p>
<p style="margin: 0 0 16px;">Since we did not receive payment to confirm your booking, the reservation request has been canceled and the payment link has been disabled.</p>
<p style="margin: 0 0 16px;">However, if you\'re still interested in securing the booking, let us know and we\'ll re-send the payment link.</p>
<p style="margin: 0;">We hope to have the opportunity to jam with you in the future!</p>';
    }

    private function getThankYouForJammingMessage(): string
    {
        return '<p style="margin: 0 0 16px;">Hi {{customer_name}},</p>
<p style="margin: 0 0 16px;">Thank you so much for having Jam with Jamie! We had the best time jamming with you!</p>
<p style="margin: 0 0 16px;">In the event you weren\'t able to do so, and would still like to tip the performers, here are their personal Venmo handles:</p>
<p style="margin: 0 0 8px;"><strong>Performer Venmo:</strong></p>
<ul style="margin: 0 0 16px; padding-left: 22px;"><li>Performer 1: </li><li>Performer 2: </li></ul>
<p style="margin: 0 0 16px;">If for any reason you are not completely satisfied, please reply back to this email and let us know. We work hard to provide the best experience for our clients.</p>
<p style="margin: 0 0 16px;">If you enjoyed the entertainment, we would love it if you could leave a quick review on <a href="https://www.google.com/search?q=Jam+with+Jamie+review">Google</a>! Many parents and companies use this resource to find us and it\'s always helpful for people to hear real, third party reviews of our company and entertainment.</p>
<p style="margin: 0 0 16px;">We have an offer to share! Rebook with us in the future and receive 15% off your booking anytime in the next year.</p>
<p style="margin: 0 0 16px;">If you have any pictures from your event that you are willing to share, we would love to see them! Please reply to this email with any selected photos or share and tag us @jamwithjamie.</p>
<p style="margin: 0;">Thanks again and we look forward to jamming with you at future events!</p>';
    }

    private function getAvailabilityConfirmedNextStepsMessage(): string
    {
        return '<p style="margin: 0 0 16px;">Hi {{customer_name}},</p>
<p style="margin: 0 0 16px;">We\'re happy to confirm availability for your event!</p>
<p style="margin: 0;"><strong>You\'ll receive your payment link by the end of the day on Friday,</strong> along with a short form to submit any additional event details and song requests.</p>
<p style="margin: 0;"><br></p>
<p style="margin: 0;">If you have any questions in the meantime, feel free to reply to this email or schedule a call with us <a href="https://dfaebhb.r.tsp1-brevo.net/tr/cl/uFGTqCh_pLs021c6GgWNVqxe_WZ3pAHB2IWFWAo7WZW6ZDUEzEvyPh32XVbgqIOQcq_cx-bFTCnORptoL06fOvcpWe3mn5KwN3qh-HOC1HbCpfah36OZZOsLS8K5TA1CFCs9xvWrjFU3xrmJTxYI5gtX9aktvwdjGbbF735bP6-6ku49yA90ng5bOd14V31ua7f3vDKv-0LPl7hbKs2PkxiUKIBxUqzR_PkLXOXAhrlb-lm2F1V0nTgD2qS2tvdcHvNaW_ONFxJIRzuah8Q5YrI_Ydgn2w1oRiYMPWFhMKdgnYrSsCl5Bg" rel="noopener noreferrer" target="_blank">HERE.</a></p>
<p style="margin: 0;"><br></p>
<p style="margin: 0;"><strong>If you are no longer interested in moving forward with your booking, please reply to this email to let us know so we can release the team currently being held for you and cancel the invoice process.</strong></p>
<p style="margin: 0;"><br></p>
<p style="margin: 0;"><strong><u>Your Reservation Request:</u></strong></p>
<p style="margin: 0;"><br></p>
<p style="margin: 0;">{{event_date}}</p>
<p style="margin: 0;">{{event_time}}</p>
<p style="margin: 0;">{{event_address}}</p>
<p style="margin: 0;">{{total_amount}}</p>';
    }

    private function getNotAvailableForEventMessage(): string
    {
        return '<p style="margin: 0 0 16px;">Hi {{customer_name}},</p>
<p style="margin: 0 0 16px;">Thank you so much for your patience as we worked on your booking request.</p>
<p style="margin: 0 0 16px;">Unfortunately, we do not have a team available for the requested date and time.</p>
<p style="margin: 0;">We hope to jam with you in the future!</p>
<p style="margin: 0;"><br></p>
<p style="margin: 0;">In the meantime, we invite you to follow us on <a href="https://dfaebhb.r.bh.d.sendibt3.com/tr/cl/0GjFjGTTteY_7yIhxTTHMzKK7wi99QB__sKIaGUPdRqBYTrYgp4bfwCq448kdoQVImXq-Yc-3qGSqusGEmRtTjR14SgS2UCPwLGwq4UVNFXPpzaberxCAQUepP2GIew1sArGcvcf9jsuCfX5Xl8sNuK0UWHjqkUxhU1zKGrCQ_j0aMQJRgPqSd46MMUdRRmTPvJxiw6ewT0DtEG5h6mq6mKGqF2cgzAuZdAl-XD-ZjCHFdDwkwrGXwaxu5WudfcD3fuqqtBfrdeyTAVv56LuRwFUCImFKx-90otfvRNCjcDCqZc" rel="noopener noreferrer" target="_blank">social media</a> and <a href="https://dfaebhb.r.bh.d.sendibt3.com/tr/cl/gLfX4bpCT6zg4P0BMOOC2nxdGm2EOT0QYJS_0aq2v4cewdpFhH2Ig6aYfDBA7Z7qNOOdHQ5ivax1-R7pVMcdKBU5GXV0ZUyFKxxzIIMBg6DVvBtQB3NnxTsv0CdPIXOzL9LM05ZadxsbgA5gCI2zyuxLr5QDQqsgomF-VfZ3pQO-96iUjVasEiAcV91iZMeemGLbuzJsgvvTq2FRIOqhELBpVYca7EYcTWDW2jD83tw60_UZbDEgKKaZwucNaa1lQjdA_uYlzTZZO6u41zoRLH9eutlC6Nko-Mm1CRad1w" rel="noopener noreferrer" target="_blank">subscribe to our newsletter</a> to stay up to date on special offers, events, and all things Jam with Jamie.</p>';
    }
}
