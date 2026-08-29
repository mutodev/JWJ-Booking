<?php

namespace App\Database\Seeds\Support;

/**
 * A2 — "Editar plantillas de email sin que se reseteen todas".
 *
 * Non-destructive guard shared by every seeder that touches `email_templates`.
 *
 * Rule: a seeder may INSERT a template when its slug is missing, but it must
 * never UPDATE a row that an admin has already customized from the panel
 * (`is_customized = 1`). This trait centralizes that check so the historical
 * Fix/Patch seeders can be wrapped without changing a single byte of the
 * content they seed.
 *
 * Degrades gracefully: if the `is_customized` column does not exist yet (DB
 * seeded before the migration ran), every template is treated as
 * non-customized and the seeders behave exactly as before.
 *
 * This is a TRAIT, not a Seeder. It relies on the host class exposing a
 * `$this->db` connection (CodeIgniter's Seeder base class does) and falls
 * back to the default connection otherwise.
 */
trait EmailTemplateSeedGuard
{
    /**
     * Resolve a DB connection: the seeder's own `$this->db` when present,
     * otherwise the default connection.
     */
    protected function seedGuardDb()
    {
        if (isset($this->db) && $this->db) {
            return $this->db;
        }

        return \Config\Database::connect();
    }

    /**
     * True when `email_templates` has the customization-tracking column.
     * Cached per host class for the duration of the seed run.
     */
    protected function emailTemplatesHaveCustomizationFlag(): bool
    {
        static $exists = null;

        if ($exists === null) {
            try {
                $exists = $this->seedGuardDb()->fieldExists('is_customized', 'email_templates');
            } catch (\Throwable $e) {
                $exists = false;
            }
        }

        return $exists;
    }

    /**
     * True when the template with the given slug was edited from the admin
     * panel and must be left untouched.
     */
    protected function templateIsCustomized(string $slug): bool
    {
        if (!$this->emailTemplatesHaveCustomizationFlag()) {
            return false;
        }

        $row = $this->seedGuardDb()->table('email_templates')
            ->select('is_customized')
            ->where('slug', $slug)
            ->get()
            ->getRowArray();

        return $row !== null && (int) ($row['is_customized'] ?? 0) === 1;
    }

    /**
     * Update a template by slug ONLY if it has not been customized.
     *
     * @return bool true if the update ran, false if it was skipped because the
     *              template is customized.
     */
    protected function safeUpdateTemplate(string $slug, array $data): bool
    {
        if ($this->templateIsCustomized($slug)) {
            log_message(
                'info',
                '[EmailTemplateSeedGuard] Skipped seeder update of "' . $slug
                . '": template was customized from the admin panel.'
            );

            return false;
        }

        $this->seedGuardDb()->table('email_templates')
            ->where('slug', $slug)
            ->update($data);

        return true;
    }

    /**
     * SQL fragment that excludes customized rows from a raw UPDATE.
     * Returns '' when the column is absent so existing SQL is unchanged.
     *
     * @param bool $hasExistingWhere true if the query already has a WHERE clause.
     */
    protected function customizationGuardSql(bool $hasExistingWhere): string
    {
        if (!$this->emailTemplatesHaveCustomizationFlag()) {
            return '';
        }

        return $hasExistingWhere ? ' AND is_customized = 0' : ' WHERE is_customized = 0';
    }
}
