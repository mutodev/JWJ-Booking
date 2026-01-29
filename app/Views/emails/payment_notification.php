<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Information - Reservation <?= esc($reservation->id) ?></title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

    <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;">
        Payment Information for Your Event Reservation
    </h2>

    <p>Dear <?= esc($reservation->full_name) ?>,</p>

    <p>Thank you for choosing our entertainment services! Before proceeding with payment, please complete your event details.</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="<?= esc($confirmationUrl) ?>"
            style="background-color: #3b82f6; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-size: 16px; font-weight: bold;">
            Continue to Pay
        </a>
    </div>

    <p>Here are the details of your reservation:</p>

    <h3 style="color: #27ae60; margin-top: 30px;">Reservation Details:</h3>
    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <tr>
            <td style="padding: 10px; border: 1px solid #dee2e6; font-weight: bold;">Customer:</td>
            <td style="padding: 10px; border: 1px solid #dee2e6;"><?= esc($reservation->full_name) ?></td>
        </tr>
        <tr style="background-color: #f8f9fa;">
            <td style="padding: 10px; border: 1px solid #dee2e6; font-weight: bold;">Service:</td>
            <td style="padding: 10px; border: 1px solid #dee2e6;"><?= esc($reservation->service_name) ?></td>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #dee2e6; font-weight: bold;">Event Date:</td>
            <td style="padding: 10px; border: 1px solid #dee2e6;"><?= esc($eventDate) ?></td>
        </tr>
        <tr style="background-color: #f8f9fa;">
            <td style="padding: 10px; border: 1px solid #dee2e6; font-weight: bold;">Event Time:</td>
            <td style="padding: 10px; border: 1px solid #dee2e6;"><?= esc($reservation->event_time) ?></td>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #dee2e6; font-weight: bold;">Location:</td>
            <td style="padding: 10px; border: 1px solid #dee2e6;"><?= esc($reservation->event_address) ?></td>
        </tr>
        <tr style="background-color: #f8f9fa;">
            <td style="padding: 10px; border: 1px solid #dee2e6; font-weight: bold;">Number of Children:</td>
            <td style="padding: 10px; border: 1px solid #dee2e6;"><?= esc($reservation->children_count) ?></td>
        </tr>
        <?php if (!empty($reservation->birthday_child_name)): ?>
            <tr>
                <td style="padding: 10px; border: 1px solid #dee2e6; font-weight: bold;">Birthday Child:</td>
                <td style="padding: 10px; border: 1px solid #dee2e6;"><?= esc($reservation->birthday_child_name) ?></td>
            </tr>
        <?php endif; ?>
        <tr style="background-color: #e8f5e8;">
            <td style="padding: 10px; border: 1px solid #dee2e6; font-weight: bold; font-size: 18px;">Total Amount:</td>
            <td style="padding: 10px; border: 1px solid #dee2e6; font-weight: bold; font-size: 18px; color: #27ae60;">$<?= esc($totalAmount) ?></td>
        </tr>
    </table>

    <h3 style="color: #e74c3c; margin-top: 30px;">Next Steps:</h3>
    <ol style="line-height: 2;">
        <li><strong>Complete Event Details:</strong> Click the blue button above to fill in your event information (address, times, birthday details, etc.)</li>
        <li><strong>Proceed to Payment:</strong> After completing the details, you'll be automatically redirected to a secure payment page powered by Stripe</li>
    </ol>

    <p style="background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; border-radius: 5px; margin-top: 20px;">
        <strong>⚠️ Important:</strong> Please complete your event details before the event date to ensure everything is ready for your special day!
    </p>

</body>

</html>