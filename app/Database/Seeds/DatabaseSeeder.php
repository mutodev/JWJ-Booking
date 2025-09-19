<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {

        $this->call('RoleSeeder');
        $this->call('MetropolitanAreaSeeder');
        $this->call('CountySeeder');
        $this->call('CitiesSeeder');
        $this->call('ZipCodeSeeder');
        $this->call('ServicesSeeder');
        $this->call('UserSeeder');
        $this->call('MenuSeeder');
        $this->call('RoleMenuPermissionSeeder');
        $this->call('ServicePriceSeeder');
        $this->call('AddonSeeder');
        $this->call('CustomerSeeder');
        $this->call('ChildrenAgeRangesSeeder');
        $this->call('DurationsSeeder');
    }
}
