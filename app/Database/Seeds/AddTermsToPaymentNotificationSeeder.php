<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AddTermsToPaymentNotificationSeeder extends Seeder
{
    public function run()
    {
        $termsHtml = '
                    <!-- Terms & Conditions -->
                    <tr>
                        <td style="padding: 0 40px 32px;">
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

                    <!-- Footer -->';

        // Find the current body and insert T&C before the footer section
        $template = $this->db->table('email_templates')
            ->where('slug', 'payment_notification')
            ->get()
            ->getRow();

        if (!$template) {
            echo "Template payment_notification not found.\n";
            return;
        }

        // Check if T&C already applied
        if (strpos($template->body, 'Terms &amp; Conditions') !== false) {
            echo "Terms & Conditions already present in payment_notification template.\n";
            return;
        }

        $newBody = str_replace('<!-- Footer -->', $termsHtml, $template->body);

        $this->db->table('email_templates')
            ->where('slug', 'payment_notification')
            ->update(['body' => $newBody]);

        echo "Terms & Conditions added to payment_notification email template.\n";
    }
}
