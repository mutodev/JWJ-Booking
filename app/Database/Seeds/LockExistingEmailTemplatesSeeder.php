<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * A2 — OPTIONAL, MANUAL-ONLY.
 *
 * This seeder is deliberately NOT part of DatabaseSeeder and must never be
 * added to it. Run it by hand ONLY if, before deploying A2, you confirm that
 * some email templates were already edited from the admin panel in production
 * and you want those edits protected from the historical Fix/Patch seeders.
 *
 *   php spark db:seed LockExistingEmailTemplatesSeeder
 *
 * It sets is_customized = 1 for an explicit list of slugs. Nothing else is
 * touched: no subject/body/content is modified. Edit $slugs below to match the
 * templates you actually customized before running it.
 *
 * From the moment A2 is live, EmailTemplateService::update() marks templates
 * automatically on the first admin edit, so this seeder is a one-time bridge
 * for edits made *before* the flag existed.
 */
class LockExistingEmailTemplatesSeeder extends Seeder
{
    public function run()
    {
        // Slugs known to have been customized in production before A2 shipped.
        // Keep this list explicit and conservative — locking a template stops
        // every seeder from ever updating it again.
        $slugs = [
            // 'payment_notification',
            // 'reservation_confirmation',
            // 'payment_confirmation',
        ];

        if (empty($slugs)) {
            echo "LockExistingEmailTemplatesSeeder: no slugs configured — nothing to do.\n";
            echo "Edit \$slugs in this file with the templates you customized, then re-run.\n";
            return;
        }

        if (!$this->db->fieldExists('is_customized', 'email_templates')) {
            echo "LockExistingEmailTemplatesSeeder: run the A2 migration first.\n";
            return;
        }

        $now = date('Y-m-d H:i:s');

        foreach ($slugs as $slug) {
            $exists = $this->db->table('email_templates')->where('slug', $slug)->countAllResults() > 0;
            if (!$exists) {
                echo "  - skip '{$slug}': not found.\n";
                continue;
            }

            $this->db->table('email_templates')
                ->where('slug', $slug)
                ->update([
                    'is_customized' => 1,
                    'customized_at' => $now,
                    'customized_by' => 'Locked pre-A2 (manual seeder)',
                ]);

            echo "  - locked '{$slug}'.\n";
        }
    }
}
