<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMarketingConversionClaimsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'event_key'    => ['type' => 'CHAR', 'constraint' => 64],
            'event_type'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'reference_id' => ['type' => 'VARCHAR', 'constraint' => 255],
            'amount'       => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'currency'     => ['type' => 'CHAR', 'constraint' => 3, 'default' => 'USD'],
            'created_at'   => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('event_key', true);
        $this->forge->addKey(['event_type', 'created_at']);
        $this->forge->createTable('marketing_conversion_claims', true);
    }

    public function down()
    {
        $this->forge->dropTable('marketing_conversion_claims', true);
    }
}
