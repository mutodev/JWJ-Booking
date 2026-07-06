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
                    'intro'          => 'Thank you for choosing Jam with Jamie! Below, you\'ll find your payment link and the opportunity to submit additional information to finalize your booking.<br><br>All Terms and Conditions have been sent to you in a separate email; by submitting payment, you agree to them, so please review everything carefully beforehand.<br><br>The payment link and temporary reservation will be active for the next 3 days. After this period, the link will expire, and your reservation will be automatically cancelled.<br><br>We do our very best to accommodate requests for changes to location or time; however, we cannot guarantee modifications once the booking is finalized.',
                    'button_text'    => 'Continue to Pay',
                    'steps_heading'  => 'Next Steps',
                    'step1'          => '<strong>Complete Event Details</strong> &mdash; Click the button above to fill in your event information',
                    'step2'          => '<strong>Proceed to Payment</strong> &mdash; After completing the details, you\'ll be redirected to a secure payment page powered by Stripe',
                    'important_note' => 'Please complete your event details before the event date to ensure everything is ready for your special day!',
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
                    'intro'          => 'Hi {{customer_name}}, thank you for booking with Jam with Jamie! We\'ve received your reservation and our team will be in touch shortly.',
                    'steps_heading'  => 'What\'s Next?',
                    'step1'          => '<strong>Our team reviews your reservation</strong> &mdash; We\'ll confirm availability and prepare everything for your event',
                    'step2'          => '<strong>You\'ll receive a payment link</strong> &mdash; Once confirmed, we\'ll send you a secure payment link via email',
                    'step3'          => '<strong>Get ready to party!</strong> &mdash; After payment, your event is fully booked and we\'ll see you there!',
                    'question_note'  => 'Feel free to reply to this email or contact us anytime. We\'re happy to help make your event special!',
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

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td align="center">
                                        <div style="display: inline-block; background-color: #FFEF81; border: 2px solid #000000; border-radius: 8px; padding: 12px 24px;">
                                            <span style="font-size: 16px; font-weight: 700; color: #000000;">Reservation ID: {{reservation_id}}</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>

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
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 18px 0 24px; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
    <tr><td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Date</td><td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">{{event_date}}</td></tr>
    <tr><td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Time</td><td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;">{{event_time}}</td></tr>
    <tr><td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Address</td><td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">{{event_address}}</td></tr>
    <tr><td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%;">Service</td><td style="padding: 12px 16px; font-size: 14px; color: #1F2937;">{{service_name}}</td></tr>
</table>';
    }

    private function getPaymentNeededSecureEventMessage(): string
    {
        $summary = $this->getReservationSummaryTable();

        return '<p style="margin: 0 0 16px;">Hi {{customer_name}},</p>
<p style="margin: 0 0 12px; font-weight: 700; color: #1F2937;">Reservation Summary</p>' . $summary . '
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
<p style="margin: 0 0 16px;">You\'ll receive this Friday by the end of the day the payment link, and a short form to submit any additional information or song requests.</p>
<p style="margin: 0;">If you are no longer interested in proceeding with the booking, please reply to this email to let us know.</p>';
    }

    private function getNotAvailableForEventMessage(): string
    {
        return '<p style="margin: 0 0 16px;">Hi {{customer_name}},</p>
<p style="margin: 0 0 16px;">Thank you so much for your patience as we worked on your booking request.</p>
<p style="margin: 0 0 16px;">Unfortunately, we do not have a team available for the requested date and time.</p>
<p style="margin: 0;">We hope to jam with you in the future!</p>';
    }
}
