<?php

namespace App\Commands;

use App\Services\ReservationService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SendWeekReminders extends BaseCommand
{
    protected $group       = 'Reservations';
    protected $name        = 'reminders:week';
    protected $description = 'Send week-of-event reminder emails to upcoming reservations';

    public function run(array $params)
    {
        CLI::write('[' . date('Y-m-d H:i:s') . '] Running week reminders...', 'yellow');

        $service = new ReservationService();
        $sent    = $service->sendWeekReminders();

        CLI::write("[" . date('Y-m-d H:i:s') . "] Done. {$sent} reminder(s) sent.", 'green');
    }
}
