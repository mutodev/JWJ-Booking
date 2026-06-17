<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FixLogoSizeSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // Replace 140px logo with 300px
        $db->query("UPDATE email_templates SET body = REPLACE(body, 'width=\"140\" style=\"display: inline-block; max-width: 140px; height: auto;\"', 'width=\"300\" style=\"display: inline-block; max-width: 300px; height: auto;\"')");

        // Replace 160px logo with 300px
        $db->query("UPDATE email_templates SET body = REPLACE(body, 'width=\"160\" style=\"display: inline-block; max-width: 160px; height: auto;\"', 'width=\"300\" style=\"display: inline-block; max-width: 300px; height: auto;\"')");

        // Replace 220px logo with 300px (in case any template has this size)
        $db->query("UPDATE email_templates SET body = REPLACE(body, 'width=\"220\" style=\"display: inline-block; max-width: 220px; height: auto;\"', 'width=\"300\" style=\"display: inline-block; max-width: 300px; height: auto;\"')");

        echo "Logo size updated to 300px in all email templates.\n";
    }
}
