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

    <p>Dear <?= esc($reservation->customer_name) ?>,</p>

    <p>Thank you for choosing our entertainment services! Here are the details of your reservation:</p>

    <h3 style="color: #27ae60; margin-top: 30px;">Reservation Details:</h3>
    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <tr>
            <td style="padding: 10px; border: 1px solid #dee2e6; font-weight: bold;">Customer:</td>
            <td style="padding: 10px; border: 1px solid #dee2e6;"><?= esc($reservation->customer_name) ?></td>
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

    <h3 style="color: #e74c3c; margin-top: 30px;">Payment Information:</h3>
    <p>To complete your reservation, please proceed with the payment using the following link:</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="<?= esc($paymentUrl) ?>"
            style="background-color: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-size: 18px; font-weight: bold;">
            💳 Pay Now - $<?= esc($totalAmount) ?>
        </a>
    </div>

    <p style="margin-top: 20px;">
        <strong>Payment URL:</strong>
        <a href="<?= esc($paymentUrl) ?>" style="color: #3498db; word-break: break-all;"><?= esc($paymentUrl) ?></a>
    </p>

</body>

</html>