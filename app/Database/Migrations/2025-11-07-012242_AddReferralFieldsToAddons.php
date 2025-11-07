<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReferralFieldsToAddons extends Migration
{
    public function up()
    {
        $this->forge->addColumn('addons', [
            'is_referral_service' => [
                'type' => 'BOOLEAN',
                'default' => false,
                'null' => false,
                'after' => 'is_active',
                'comment' => 'If true, this is a referral service (not directly bookable)'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('addons', 'is_referral_service');
    }
}
