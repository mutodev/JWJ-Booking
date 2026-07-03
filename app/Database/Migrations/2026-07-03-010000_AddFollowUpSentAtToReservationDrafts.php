<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFollowUpSentAtToReservationDrafts extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reservation_drafts', [
            'follow_up_sent_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'last_activity_at',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('reservation_drafts', 'follow_up_sent_at');
    }
}
