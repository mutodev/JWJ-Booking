<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - JamWithJamie</title>
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

                            <!-- Icon + Title -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
                                <tr>
                                    <td align="center">
                                        <div style="width: 56px; height: 56px; background-color: #f0f0ff; border-radius: 50%; line-height: 56px; text-align: center; font-size: 28px; margin-bottom: 16px;">
                                            &#128274;
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <h1 style="margin: 0 0 8px; font-size: 24px; font-weight: 700; color: #1F2937; text-align: center;">Password Reset</h1>
                            <p style="margin: 0 0 28px; font-size: 15px; line-height: 1.6; color: #6b7280; text-align: center;">Your password has been successfully reset. Use the temporary password below to log in to your account.</p>

                            <!-- Password Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="background-color: #f0f0ff; border: 1px solid #e0e0f5; border-radius: 8px; padding: 20px 24px; text-align: center;">
                                        <p style="margin: 0 0 6px; font-size: 13px; font-weight: 600; color: #667eea; text-transform: uppercase; letter-spacing: 0.5px;">Your New Temporary Password</p>
                                        <p style="margin: 0; font-size: 22px; font-weight: 700; color: #1F2937; letter-spacing: 1px; font-family: 'Courier New', Courier, monospace;"><?= esc($password) ?></p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Security Notice -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="background-color: #fef3cd; border-left: 4px solid #f59e0b; border-radius: 0 8px 8px 0; padding: 14px 18px;">
                                        <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #92400e;">
                                            <strong>Security Reminder:</strong> Please change this password immediately after logging in. If you didn't request this reset, contact our support team right away.
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
