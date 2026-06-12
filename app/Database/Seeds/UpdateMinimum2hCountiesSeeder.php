<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UpdateMinimum2hCountiesSeeder extends Seeder
{
    public function run()
    {
        $counties = [
            'Camden County',
            'Somerset County',
            'Morris County',
            'Dutchess County',
            'Suffolk County',
            'Kendall County',
            'Kane County',
        ];

        foreach ($counties as $countyName) {
            // Update all zipcodes belonging to this county via cities join
            $this->db->query("
                UPDATE zipcodes z
                INNER JOIN cities ci ON ci.id = z.city_id
                INNER JOIN counties co ON co.id = ci.county_id
                SET z.zone_type = 'minimum_2h'
                WHERE co.name = ?
                  AND z.zone_type = 'standard'
            ", [$countyName]);
        }
    }
}
