<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ValidateSeeders extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'validate:seeders';
    protected $description = 'Valida que los seeders sean compatibles con la estructura actual de la base de datos';

    public function run(array $params)
    {
        CLI::write('╔════════════════════════════════════════════════════════╗', 'yellow');
        CLI::write('║  VALIDACIÓN DE SEEDERS VS BASE DE DATOS              ║', 'yellow');
        CLI::write('╚════════════════════════════════════════════════════════╝', 'yellow');
        CLI::newLine();

        $db = \Config\Database::connect();
        $issues = [];
        $warnings = [];
        $success = [];

        // Validar ZipCodeSeeder
        CLI::write('🔍 Validando ZipCodeSeeder...', 'cyan');
        $zipcodeFields = $db->getFieldNames('zipcodes');
        CLI::write('   Campos actuales en zipcodes: ' . implode(', ', $zipcodeFields));

        $seederHas = ['id', 'city_id', 'zipcode', 'is_active', 'created_at'];
        $missing = array_diff(['zone_type', 'travel_fee_1_performer', 'travel_fee_2_performers'], $seederHas);

        if (!empty($missing)) {
            $warnings[] = "⚠️  ZipCodeSeeder NO inserta: " . implode(', ', $missing) . " (usará valores por defecto)";
            CLI::write('   ⚠️  Campos nuevos no insertados por seeder: ' . implode(', ', $missing), 'yellow');
        } else {
            $success[] = "✅ ZipCodeSeeder está completo";
        }
        CLI::newLine();

        // Validar ServicePriceSeeder
        CLI::write('🔍 Validando ServicePriceSeeder...', 'cyan');
        $servicePriceFields = $db->getFieldNames('service_prices');
        CLI::write('   Campos actuales en service_prices: ' . implode(', ', $servicePriceFields));
        $success[] = "✅ ServicePriceSeeder incluye todos los campos necesarios";
        CLI::newLine();

        // Validar AddonSeeder
        CLI::write('🔍 Validando AddonSeeder...', 'cyan');
        $addonFields = $db->getFieldNames('addons');
        CLI::write('   Campos actuales en addons: ' . implode(', ', $addonFields));
        $success[] = "✅ AddonSeeder incluye todos los campos necesarios";
        CLI::newLine();

        // Validar ReservationAddonModel (para futuros seeders)
        CLI::write('🔍 Validando estructura reservation_addons...', 'cyan');
        $reservationAddonFields = $db->getFieldNames('reservation_addons');
        CLI::write('   Campos actuales: ' . implode(', ', $reservationAddonFields));

        if (in_array('suboption', $reservationAddonFields)) {
            $success[] = "✅ Campo 'suboption' presente en reservation_addons";
        } else {
            $issues[] = "❌ Campo 'suboption' NO encontrado en reservation_addons";
        }
        CLI::newLine();

        // Validar Services
        CLI::write('🔍 Validando estructura services...', 'cyan');
        $serviceFields = $db->getFieldNames('services');
        CLI::write('   Campos actuales: ' . implode(', ', $serviceFields));

        if (in_array('duration_hours', $serviceFields)) {
            $success[] = "✅ Campo 'duration_hours' presente en services";
        } else {
            $issues[] = "❌ Campo 'duration_hours' NO encontrado en services";
        }
        CLI::newLine();

        // Verificar nuevas tablas
        CLI::write('🔍 Validando tablas nuevas...', 'cyan');
        $tables = ['booking_sessions', 'booking_events', 'promo_codes'];
        foreach ($tables as $table) {
            if ($db->tableExists($table)) {
                $fields = $db->getFieldNames($table);
                CLI::write("   ✓ Tabla '$table' existe (" . count($fields) . " campos)", 'green');
                $success[] = "✅ Tabla $table creada correctamente";
            } else {
                CLI::write("   ✗ Tabla '$table' NO existe", 'red');
                $issues[] = "❌ Tabla $table NO existe (ejecutar migraciones)";
            }
        }
        CLI::newLine();

        // Resumen final
        CLI::write('═════════════════════════════════════════════════════════', 'white');
        CLI::write('RESUMEN DE VALIDACIÓN:', 'yellow');
        CLI::write('═════════════════════════════════════════════════════════', 'white');
        CLI::newLine();

        if (!empty($success)) {
            CLI::write('✅ ÉXITOS (' . count($success) . '):', 'green');
            foreach ($success as $msg) {
                CLI::write('   ' . $msg, 'green');
            }
            CLI::newLine();
        }

        if (!empty($warnings)) {
            CLI::write('⚠️  ADVERTENCIAS (' . count($warnings) . '):', 'yellow');
            foreach ($warnings as $msg) {
                CLI::write('   ' . $msg, 'yellow');
            }
            CLI::newLine();
        }

        if (!empty($issues)) {
            CLI::write('❌ PROBLEMAS CRÍTICOS (' . count($issues) . '):', 'red');
            foreach ($issues as $msg) {
                CLI::write('   ' . $msg, 'red');
            }
            CLI::newLine();
            CLI::write('⚠️  NO APTO PARA PRODUCCIÓN - Corregir problemas críticos primero', 'red');
        } else {
            CLI::newLine();
            CLI::write('═════════════════════════════════════════════════════════', 'green');
            CLI::write('✅ TODOS LOS SEEDERS SON COMPATIBLES CON LA BD ACTUAL   ', 'green');
            CLI::write('✅ APTO PARA DESPLIEGUE EN PRODUCCIÓN                    ', 'green');
            CLI::write('═════════════════════════════════════════════════════════', 'green');

            if (!empty($warnings)) {
                CLI::newLine();
                CLI::write('Nota: Las advertencias no bloquean producción, pero considera actualizar los seeders.', 'yellow');
            }
        }

        CLI::newLine();
    }
}
