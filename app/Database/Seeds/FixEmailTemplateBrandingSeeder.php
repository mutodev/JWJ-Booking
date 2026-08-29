<?php

namespace App\Database\Seeds;

use App\Database\Seeds\Support\EmailTemplateSeedGuard;
use CodeIgniter\Database\Seeder;

class FixEmailTemplateBrandingSeeder extends Seeder
{
    use EmailTemplateSeedGuard;

    public function run()
    {
        $templates = $this->db->table('email_templates')->get()->getResult();

        foreach ($templates as $template) {
            if ($this->templateIsCustomized($template->slug)) {
                continue;
            }

            $updates = [];

            if (!empty($template->subject) && str_contains($template->subject, 'JamWithJamie')) {
                $updates['subject'] = str_replace('JamWithJamie', 'Jam with Jamie', $template->subject);
            }

            if (!empty($template->body) && str_contains($template->body, 'JamWithJamie')) {
                $updates['body'] = str_replace('JamWithJamie', 'Jam with Jamie', $template->body);
            }

            if (!empty($template->content) && str_contains($template->content, 'JamWithJamie')) {
                $updates['content'] = str_replace('JamWithJamie', 'Jam with Jamie', $template->content);
            }

            if (!empty($updates)) {
                $updates['updated_at'] = date('Y-m-d H:i:s');
                $this->db->table('email_templates')
                    ->where('id', $template->id)
                    ->update($updates);
            }
        }
    }
}
