<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DeactivateCountiesSeeder extends Seeder
{
    public function run()
    {
        $counties = [
            'Burlington County',
            'Hunterdon County',
            'Ocean County',
            'Mercer County',
            'Riverside County',
            'Trousdale County',
            'DeKalb County',
            'Grundy County',
            'McHenry County',
            'Kankakee County',
        ];

        $this->db->table('counties')
            ->whereIn('name', $counties)
            ->update(['is_active' => 0]);
    }
}
