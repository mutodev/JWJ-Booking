<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateReservationStatusOptions extends Migration
{
    public function up()
    {
        $this->db->query("
            ALTER TABLE reservations
            MODIFY status ENUM(
                'new',
                'under_review',
                'confirmed',
                'checking_availability',
                'availability_confirmed',
                'follow_up',
                'ready_for_payment_link',
                'payment_link_sent',
                'payment_reminder',
                'booked',
                'get_ready_to_jam',
                'thank_you_for_jamming',
                'cancelled'
            ) NOT NULL DEFAULT 'new'
        ");

        $this->db->query("UPDATE reservations SET status = 'checking_availability' WHERE status = 'under_review'");
        $this->db->query("UPDATE reservations SET status = 'availability_confirmed' WHERE status = 'confirmed'");

        $this->db->query("
            ALTER TABLE reservations
            MODIFY status ENUM(
                'new',
                'checking_availability',
                'availability_confirmed',
                'follow_up',
                'ready_for_payment_link',
                'payment_link_sent',
                'payment_reminder',
                'booked',
                'get_ready_to_jam',
                'thank_you_for_jamming',
                'cancelled'
            ) NOT NULL DEFAULT 'new'
        ");
    }

    public function down()
    {
        $this->db->query("
            ALTER TABLE reservations
            MODIFY status ENUM(
                'new',
                'under_review',
                'confirmed',
                'checking_availability',
                'availability_confirmed',
                'follow_up',
                'ready_for_payment_link',
                'payment_link_sent',
                'payment_reminder',
                'booked',
                'get_ready_to_jam',
                'thank_you_for_jamming',
                'cancelled'
            ) NOT NULL DEFAULT 'new'
        ");

        $this->db->query("
            UPDATE reservations
            SET status = CASE
                WHEN status IN ('checking_availability', 'follow_up') THEN 'under_review'
                WHEN status IN (
                    'availability_confirmed',
                    'ready_for_payment_link',
                    'payment_link_sent',
                    'payment_reminder',
                    'booked',
                    'get_ready_to_jam',
                    'thank_you_for_jamming'
                ) THEN 'confirmed'
                ELSE status
            END
        ");

        $this->db->query("
            ALTER TABLE reservations
            MODIFY status ENUM('new', 'under_review', 'confirmed', 'cancelled') NOT NULL DEFAULT 'new'
        ");
    }
}
