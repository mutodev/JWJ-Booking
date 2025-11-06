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
            CLI::write('Últimas 10 reservas:', 'yellow');
            CLI::newLine();

            foreach (array_slice($reservations, 0, 10) as $idx => $reservation) {
                CLI::write(($idx + 1) . '. Cliente: ' . ($reservation->full_name ?? 'N/A'));
                CLI::write('   Email: ' . ($reservation->email ?? 'N/A'));
                CLI::write('   Fecha: ' . ($reservation->event_date ?? 'N/A'));
                CLI::write('   Niños: ' . ($reservation->children_count ?? '0'));
                CLI::write('   Total: $' . number_format($reservation->total_amount ?? 0, 2));
                CLI::write('   Status: ' . ($reservation->status ?? 'N/A'));
                CLI::newLine();
            }
        }
    }
}
