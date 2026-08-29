<?php

namespace App\Database\Seeds;

use App\Database\Seeds\Support\EmailTemplateSeedGuard;
use CodeIgniter\Database\Seeder;

class FixBrandingCapitalizationSeeder extends Seeder
{
    use EmailTemplateSeedGuard;

    public function run()
    {
        // Replace "Jam With Jamie" (capital W) → "Jam with Jamie" in all template fields
        $fields = ['subject', 'body', 'content'];
        $guard  = $this->customizationGuardSql(true);

        foreach ($fields as $field) {
            $this->db->query("
                UPDATE email_templates
                SET {$field} = REPLACE({$field}, 'Jam With Jamie', 'Jam with Jamie'),
                    updated_at = NOW()
                WHERE {$field} LIKE '%Jam With Jamie%'{$guard}
            ");
        }
    }
}
