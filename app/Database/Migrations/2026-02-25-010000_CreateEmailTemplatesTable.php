<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmailTemplatesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'CHAR', 'constraint' => 36],
            'slug'                => ['type' => 'VARCHAR', 'constraint' => 100],
            'name'                => ['type' => 'VARCHAR', 'constraint' => 150],
            'subject'             => ['type' => 'VARCHAR', 'constraint' => 255],
            'body'                => ['type' => 'LONGTEXT'],
            'available_variables' => ['type' => 'TEXT', 'null' => true],
            'is_active'           => ['type' => 'BOOLEAN', 'default' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('email_templates');
    }

    public function down()
    {
        $this->forge->dropTable('email_templates');
    }
}
