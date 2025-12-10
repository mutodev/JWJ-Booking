<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * CleanDatabaseSeeder
 *
 * Elimina todos los datos de las tablas en orden inverso a las dependencias
 * para evitar errores de foreign keys.
 *
 * ADVERTENCIA: Este seeder eliminará TODOS los datos de la base de datos.
 * Usar con precaución, especialmente en producción.
 *
 * Ejecutar con: php spark db:seed CleanDatabaseSeeder
 */
class CleanDatabaseSeeder extends Seeder
{
    public function run()
    {
        echo "🚨 ADVERTENCIA: Este proceso eliminará TODOS los datos de la base de datos.\n";
        echo "⏳ Iniciando limpieza de base de datos...\n\n";

        // Desactivar temporalmente las restricciones de foreign keys
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');

        // Orden de eliminación: de tablas dependientes a tablas padre
        $tables = [
            // Tablas de transacciones y relaciones
            'reservation_addons',
            'reservations',
            'abandoned_carts',

            // Tablas de configuración que dependen de otras
            'promo_codes',
            'role_menu_permissions',
            'service_prices',
            'addons',

            // Tablas de datos operativos
            'customers',
            'users',
            'menus',

            // Tablas de configuración general
            'durations',
            'children_age_ranges',
            'services',

            // Tablas de ubicaciones (jerarquía inversa)
            'zipcodes',
            'cities',
            'counties',
            'metropolitan_areas',

            // Tablas base
            'roles',
        ];

        $deleted = 0;
        $errors = 0;

        foreach ($tables as $table) {
            try {
                // Verificar si la tabla existe
                if ($this->db->tableExists($table)) {
                    $count = $this->db->table($table)->countAllResults();

                    if ($count > 0) {
                        $this->db->table($table)->truncate();
                        echo "✅ Tabla '{$table}' limpiada ({$count} registros eliminados)\n";
                        $deleted++;
                    } else {
                        echo "⏭️  Tabla '{$table}' ya estaba vacía\n";
                    }
                } else {
                    echo "⚠️  Tabla '{$table}' no existe, omitiendo...\n";
                }
            } catch (\Exception $e) {
                echo "❌ Error al limpiar tabla '{$table}': " . $e->getMessage() . "\n";
                $errors++;
            }
        }

        // Reactivar las restricciones de foreign keys
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');

        echo "\n🎉 Limpieza completada!\n";
        echo "   Tablas limpiadas: {$deleted}\n";

        if ($errors > 0) {
            echo "   ⚠️ Errores encontrados: {$errors}\n";
        }

        echo "\n💡 Tip: Puedes ejecutar 'php spark db:seed DatabaseSeeder' para repoblar la base de datos.\n";
    }
}
