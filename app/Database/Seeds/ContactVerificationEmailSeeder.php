<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class ContactVerificationEmailSeeder extends Seeder
{
    public function run()
    {
        if ($this->db->table('email_templates')->where('slug', 'contact_verification')->countAllResults() > 0) {
            return;
        }
        $now = Time::now()->toDateTimeString();
        $this->db->table('email_templates')->insert([
            'id' => 'et-contact-verification-000000001',
            'slug' => 'contact_verification',
            'name' => 'Contact Verification',
            'subject' => 'Your Jam with Jamie verification code',
            'body' => $this->body(),
            'available_variables' => json_encode(['customer_name', 'verification_code', 'expires_minutes']),
            'content' => json_encode([
                'title' => 'Verify your contact information',
                'intro' => 'Hi {{customer_name}}, use this code to continue your reservation:',
                'footer' => 'This code expires in {{expires_minutes}} minutes. If you did not request it, you can ignore this email.',
            ]),
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    private function body(): string
    {
        return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="margin:0;padding:0;background:#f9fafb;font-family:Arial,Helvetica,sans-serif"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:40px 20px;background:#f9fafb"><tr><td align="center"><table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.06)"><tr><td style="padding:30px 40px;text-align:center;border-bottom:3px solid #FF74B7"><img src="{{logo_url}}" alt="Jam with Jamie" width="220" style="max-width:220px;height:auto"></td></tr><tr><td style="padding:36px 40px;text-align:center;color:#374151"><h1 style="margin:0 0 12px;font-size:24px;color:#1f2937">{{content_title}}</h1><p style="margin:0 0 24px;line-height:1.6">{{content_intro}}</p><div style="display:inline-block;padding:16px 28px;background:#FFF0F6;border:2px solid #FF74B7;border-radius:10px;font-size:32px;font-weight:700;letter-spacing:8px;color:#111827">{{verification_code}}</div><p style="margin:24px 0 0;font-size:14px;color:#6b7280">{{content_footer}}</p></td></tr><tr><td style="padding:22px 40px;text-align:center;background:#f9fafb;border-top:1px solid #e5e7eb;color:#9ca3af;font-size:12px">The Jam with Jamie Team</td></tr></table></td></tr></table></body></html>';
    }
}
