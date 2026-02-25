<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Information - Reservation <?= esc($reservation->id) ?></title>
</head>

<body style="margin: 0; padding: 0; background-color: #f4f5f7; font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; -webkit-font-smoothing: antialiased;">

    <!-- Wrapper -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f5f7; padding: 40px 20px;">
        <tr>
            <td align="center">

                <!-- Email Container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 36px 40px; text-align: center;">
                            <img src="<?= base_url('img/logos/JWJ_logo-05.png') ?>" alt="JamWithJamie" width="160" style="display: inline-block; max-width: 160px; height: auto;">
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px 40px 20px;">

                            <!-- Greeting -->
                            <h1 style="margin: 0 0 8px; font-size: 24px; font-weight: 700; color: #1F2937;">Hi <?= esc($reservation->full_name) ?>!</h1>
                            <p style="margin: 0 0 28px; font-size: 15px; line-height: 1.6; color: #6b7280;">Thank you for choosing JamWithJamie for your event! Please complete your event details and proceed to payment.</p>

                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px;">
                                <tr>
                                    <td align="center">
                                        <a href="<?= esc($confirmationUrl) ?>"
                                            style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; padding: 16px 40px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 700; letter-spacing: 0.3px;">
                                            Continue to Pay
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Reservation Details -->
                            <h2 style="margin: 0 0 16px; font-size: 17px; font-weight: 700; color: #1F2937;">Reservation Details</h2>

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
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;"><?= esc($reservation->event_time) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Location</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;"><?= esc($reservation->event_address) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Number of Children</td>
                                    <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;"><?= esc($reservation->children_count) ?></td>
                                </tr>
                                <?php if (!empty($reservation->birthday_child_name)): ?>
                                    <tr>
                                        <td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Birthday Child</td>
                                        <td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;"><?= esc($reservation->birthday_child_name) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <td style="padding: 14px 16px; font-size: 16px; font-weight: 700; color: #1F2937; background-color: #f0fdf4; border-bottom: none;">Total Amount</td>
                                    <td style="padding: 14px 16px; font-size: 20px; font-weight: 700; color: #10B981; background-color: #f0fdf4; border-bottom: none;">$<?= esc($totalAmount) ?></td>
                                </tr>
                            </table>

                            <!-- Next Steps -->
                            <h2 style="margin: 0 0 14px; font-size: 17px; font-weight: 700; color: #1F2937;">Next Steps</h2>

                            <!-- Step 1 -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                <tr>
                                    <td width="36" valign="top">
                                        <div style="width: 28px; height: 28px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; color: #ffffff; font-size: 14px; font-weight: 700; text-align: center; line-height: 28px;">1</div>
                                    </td>
                                    <td style="padding-left: 8px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #374151;">
                                            <strong>Complete Event Details</strong> &mdash; Click the button above to fill in your event information (address, times, birthday details, etc.)
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Step 2 -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td width="36" valign="top">
                                        <div style="width: 28px; height: 28px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; color: #ffffff; font-size: 14px; font-weight: 700; text-align: center; line-height: 28px;">2</div>
                                    </td>
                                    <td style="padding-left: 8px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #374151;">
                                            <strong>Proceed to Payment</strong> &mdash; After completing the details, you'll be redirected to a secure payment page powered by Stripe
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Important Notice -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 8px;">
                                <tr>
                                    <td style="background-color: #fef3cd; border-left: 4px solid #f59e0b; border-radius: 0 8px 8px 0; padding: 14px 18px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #92400e;">
                                            <strong>Important:</strong> Please complete your event details before the event date to ensure everything is ready for your special day!
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
                            <p style="margin: 0; font-size: 14px; font-weight: 600; color: #667eea;">The JamWithJamie Team</p>
                        </td>
                    </tr>

                    <!-- Bottom Bar -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 16px 40px; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: rgba(255, 255, 255, 0.8);">
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
