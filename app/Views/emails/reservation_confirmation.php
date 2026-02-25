<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Confirmed - <?= esc($reservation->id) ?></title>
</head>

<body style="margin: 0; padding: 0; background-color: #f9fafb; font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; -webkit-font-smoothing: antialiased;">

    <!-- Wrapper -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; padding: 40px 20px;">
        <tr>
            <td align="center">

                <!-- Email Container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);">

                    <!-- Header -->
                    <tr>
                        <td style="background-color: #ffffff; padding: 32px 40px; text-align: center; border-bottom: 3px solid #FF74B7;">
                            <img src="<?= base_url('img/logos/JWJ_logo-05.png') ?>" alt="JamWithJamie" width="140" style="display: inline-block; max-width: 140px; height: auto;">
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px 40px 20px;">

                            <!-- Greeting -->
                            <h1 style="margin: 0 0 8px; font-size: 24px; font-weight: 700; color: #1F2937;">Reservation Received!</h1>
                            <p style="margin: 0 0 28px; font-size: 15px; line-height: 1.6; color: #6b7280;">Hi <?= esc($reservation->full_name) ?>, thank you for booking with JamWithJamie! We've received your reservation and our team will be in touch shortly.</p>

                            <!-- Success Badge -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td align="center">
                                        <div style="display: inline-block; background-color: #FFEF81; border: 2px solid #000000; border-radius: 8px; padding: 12px 24px;">
                                            <span style="font-size: 16px; font-weight: 700; color: #000000;">Reservation ID: <?= esc($reservation->id) ?></span>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Reservation Details -->
                            <h2 style="margin: 0 0 16px; font-size: 17px; font-weight: 700; color: #1F2937;">Your Reservation Details</h2>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Customer</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;"><?= esc($reservation->full_name) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Service</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;"><?= esc($reservation->service_name) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Event Date</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;"><?= esc($eventDate) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Event Time</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;"><?= esc($reservation->event_time ?? 'To be confirmed') ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Location</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;"><?= esc($reservation->event_address ?? 'To be confirmed') ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Number of Children</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;"><?= esc($reservation->children_count) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 14px 16px; font-size: 16px; font-weight: 700; color: #1F2937; background-color: #FFF0F6; border-bottom: none;">Total Amount</td>
                                    <td style="padding: 14px 16px; font-size: 20px; font-weight: 700; color: #FF74B7; background-color: #FFF0F6; border-bottom: none;">$<?= esc($totalAmount) ?></td>
                                </tr>
                            </table>

                            <!-- What's Next -->
                            <h2 style="margin: 0 0 14px; font-size: 17px; font-weight: 700; color: #1F2937;">What's Next?</h2>

                            <!-- Step 1 -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                <tr>
                                    <td width="36" valign="top">
                                        <div style="width: 28px; height: 28px; background-color: #FFEF81; border: 2px solid #000000; border-radius: 50%; color: #000000; font-size: 14px; font-weight: 700; text-align: center; line-height: 28px;">1</div>
                                    </td>
                                    <td style="padding-left: 8px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #374151;">
                                            <strong>Our team reviews your reservation</strong> &mdash; We'll confirm availability and prepare everything for your event
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Step 2 -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                <tr>
                                    <td width="36" valign="top">
                                        <div style="width: 28px; height: 28px; background-color: #FFEF81; border: 2px solid #000000; border-radius: 50%; color: #000000; font-size: 14px; font-weight: 700; text-align: center; line-height: 28px;">2</div>
                                    </td>
                                    <td style="padding-left: 8px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #374151;">
                                            <strong>You'll receive a payment link</strong> &mdash; Once confirmed, we'll send you a secure payment link via email
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Step 3 -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td width="36" valign="top">
                                        <div style="width: 28px; height: 28px; background-color: #FFEF81; border: 2px solid #000000; border-radius: 50%; color: #000000; font-size: 14px; font-weight: 700; text-align: center; line-height: 28px;">3</div>
                                    </td>
                                    <td style="padding-left: 8px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #374151;">
                                            <strong>Get ready to party!</strong> &mdash; After payment, your event is fully booked and we'll see you there!
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Notice -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 8px;">
                                <tr>
                                    <td style="background-color: #FFF9E6; border-left: 4px solid #FFEF81; border-radius: 0 8px 8px 0; padding: 14px 18px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #92400e;">
                                            <strong>Questions?</strong> Feel free to reply to this email or contact us anytime. We're happy to help make your event special!
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 40px 32px; border-top: 1px solid #f0f0f0;">
                            <p style="margin: 0 0 4px; font-size: 14px; color: #1F2937;">Best regards,</p>
                            <p style="margin: 0; font-size: 14px; font-weight: 600; color: #FF74B7;">The JamWithJamie Team</p>
                        </td>
                    </tr>

                    <!-- Bottom Bar -->
                    <tr>
                        <td style="background-color: #FF74B7; padding: 16px 40px; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: rgba(0, 0, 0, 0.6);">
                                &copy; <?= date('Y') ?> JamWithJamie. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
