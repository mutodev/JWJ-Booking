<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * A2 — "Editar plantillas de email sin que se reseteen todas".
 *
 * Adds an audit/lock flag to email_templates so the historical Fix and Patch
 * seeders (and EmailTemplateSeeder) never overwrite a template that an admin
 * edited from the panel.
 */
class AddCustomizationFlagsToEmailTemplates extends Migration
{
    public function up()
    {
        $this->forge->addColumn('email_templates', [
            'is_customized' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
                'after'      => 'is_active',
            ],
            'customized_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'after'   => 'is_customized',
            ],
            'customized_by' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'customized_at',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('email_templates', ['is_customized', 'customized_at', 'customized_by']);
    }
}
