<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        echo "Iniciando proceso de seeding...\n";

        // 1. Tablas maestras básicas (sin dependencias)
        $this->call('RoleSeeder');
        echo "✅ Roles creados\n";

        // 2. Estructura geográfica (depende del orden jerárquico)
        $this->call('MetropolitanAreaSeeder');
        echo "✅ Áreas metropolitanas creadas\n";

        $this->call('CountySeeder');
        echo "✅ Condados creados\n";

        $this->call('CitiesSeeder');
        echo "✅ Ciudades creadas\n";

        $this->call('ZipCodeSeeder');
        echo "✅ Códigos postales creados\n";

        // 3. Servicios (depende de estructura geográfica para precios)
        $this->call('ServicesSeeder');
        echo "✅ Servicios (Tipos de Jam) creados\n";

        // 4. Usuarios (depende de roles)
        $this->call('UserSeeder');
        echo "✅ Usuarios creados\n";

        // 5. Menús (puede depender de rutas de servicios/áreas)
        $this->call('MenuSeeder');
        echo "✅ Menús creados\n";

        // 6. Permisos (depende de roles y menús)
        $this->call('RoleMenuPermissionSeeder');
        echo "✅ Permisos de roles creados\n";

        
        $this->call('ServicePriceSeeder');
        $this->call('AddonSeeder');

        echo "🎉 Proceso de seeding completado exitosamente!\n";
    }
}
