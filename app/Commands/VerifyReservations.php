<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Repositories\ReservationRepository;

class VerifyReservations extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'verify:reservations';
    protected $description = 'Verifica las reservas en la base de datos';

    public function run(array $params)
    {
        $repo = new ReservationRepository();
        $reservations = $repo->getAll();

        CLI::write('Total de reservas: ' . count($reservations), 'green');
        CLI::newLine();

        if (!empty($reservations)) {
            CLI::write('Últimas 5 reservas (con detalles completos):', 'yellow');
            CLI::write('==========================================================', 'yellow');

            $db = \Config\Database::connect();

            foreach (array_slice($reservations, 0, 5) as $idx => $reservation) {
                CLI::write(($idx + 1) . '. Cliente: ' . ($reservation->full_name ?? 'N/A'), 'cyan');
                CLI::write('   Email: ' . ($reservation->email ?? 'N/A'));
                CLI::write('   Fecha evento: ' . ($reservation->event_date ?? 'N/A'));
                CLI::write('   Hora: ' . ($reservation->event_time ?? 'N/A'));
                CLI::write('   Niños: ' . ($reservation->children_count ?? '0'));
                CLI::write('   Duración: ' . ($reservation->duration_hours ?? '0') . ' horas');
                CLI::write('   Performers: ' . ($reservation->performers_count ?? '0'));

                // Desglose de precios
                CLI::write('   -----------');
                CLI::write('   Base precio: $' . number_format($reservation->base_price ?? 0, 2));
                CLI::write('   Addons: $' . number_format($reservation->addons_total ?? 0, 2));
                CLI::write('   Extra niños: $' . number_format($reservation->extra_children_fee ?? 0, 2));
                CLI::write('   Surcharge: $' . number_format($reservation->expedition_fee ?? 0, 2));
                CLI::write('   TOTAL: $' . number_format($reservation->total_amount ?? 0, 2), 'green');
                CLI::write('   Status: ' . ($reservation->status ?? 'N/A'));

                // Obtener addons asociados
                $addonsQuery = $db->table('reservation_addons')
                    ->select('reservation_addons.*, addons.name')
                    ->join('addons', 'addons.id = reservation_addons.addon_id')
                    ->where('reservation_id', $reservation->id)
                    ->get();

                $addons = $addonsQuery->getResult();

                if (!empty($addons)) {
                    CLI::write('   Addons seleccionados:', 'yellow');
                    foreach ($addons as $addon) {
                        $suboptionText = $addon->suboption ? ' (' . $addon->suboption . ')' : '';
                        CLI::write('     - ' . $addon->name . $suboptionText . ': $' . number_format($addon->price_at_time, 2) . ' x ' . $addon->quantity);
                    }
                } else {
                    CLI::write('   Sin addons');
                }

                CLI::newLine();
            }

            CLI::write('==========================================================', 'yellow');
        }
    }
}
