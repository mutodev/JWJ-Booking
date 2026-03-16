<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContentToEmailTemplates extends Migration
{
    public function up()
    {
        $this->forge->addColumn('email_templates', [
            'content' => [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
                'after'   => 'body',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('email_templates', 'content');
    }
}
