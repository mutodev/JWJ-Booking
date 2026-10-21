<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f9fafb;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:40px 20px;background:#f9fafb;"><tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.06);">
<tr><td style="padding:30px 40px;text-align:center;border-bottom:3px solid #FF74B7;"><img src="<?= esc(base_url('img/logos/JWJ_logo-05.png')) ?>" alt="Jam with Jamie" width="220" style="max-width:220px;height:auto;"></td></tr>
<tr><td style="padding:36px 40px;text-align:center;color:#374151;">
<h1 style="margin:0 0 12px;font-size:24px;color:#1f2937;">Verify your contact information</h1>
<p style="margin:0 0 24px;line-height:1.6;">Hi <?= esc($customerName ?? 'there') ?>, use this code to continue your reservation:</p>
<div style="display:inline-block;padding:16px 28px;background:#FFF0F6;border:2px solid #FF74B7;border-radius:10px;font-size:32px;font-weight:700;letter-spacing:8px;color:#111827;"><?= esc($verificationCode ?? '') ?></div>
<p style="margin:24px 0 0;font-size:14px;color:#6b7280;">This code expires in <?= esc($expiresMinutes ?? '10') ?> minutes. If you did not request it, you can ignore this email.</p>
</td></tr>
<tr><td style="padding:22px 40px;text-align:center;background:#f9fafb;border-top:1px solid #e5e7eb;color:#9ca3af;font-size:12px;">The Jam with Jamie Team</td></tr>
</table></td></tr></table>
</body></html>
