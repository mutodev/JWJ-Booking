<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FixBrandingCapitalizationSeeder extends Seeder
{
    public function run()
    {
        // Replace "Jam With Jamie" (capital W) → "Jam with Jamie" in all template fields
        $fields = ['subject', 'body', 'content'];

        foreach ($fields as $field) {
            $this->db->query("
                UPDATE email_templates
                SET {$field} = REPLACE({$field}, 'Jam With Jamie', 'Jam with Jamie'),
                    updated_at = NOW()
                WHERE {$field} LIKE '%Jam With Jamie%'
            ");
        }
    }
}
