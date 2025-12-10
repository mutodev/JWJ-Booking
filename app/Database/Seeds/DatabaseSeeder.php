<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. Datos base
        $this->call('RoleSeeder');
        $this->call('MetropolitanAreaSeeder');
        $this->call('CountySeeder');
        $this->call('CitiesSeeder');
        $this->call('ZipCodeSeeder');
        $this->call('AllMissingDataSeeder');

        // 2. Servicios y precios (ServicesSeeder incluye servicios y precios por county)
        $this->call('ServicesSeeder');

        // 3. Addons
        $this->call('AddonSeeder');

        // 4. Usuarios y clientes
        $this->call('UserSeeder');
        $this->call('CustomerSeeder');

        // 5. Menús y permisos
        $this->call('MenuSeeder');
        $this->call('RoleMenuPermissionSeeder');
        $this->call('PromoCodesAndAbandonedCartsMenuSeeder');

        // 6. Promociones
        $this->call('PromoCodesSeeder');

        // 7. Configuraciones dependientes de precios (al final)
        $this->call('ChildrenAgeRangesSeeder');
        $this->call('DurationsSeeder');
    }
}
