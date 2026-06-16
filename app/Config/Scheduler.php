<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Tasks\Scheduler;

class Scheduler extends BaseConfig
{
    public function __invoke(Scheduler $schedule)
    {
        // Envía recordatorio semanal cada día a las 9am
        $schedule->command('reminders:week')->daily('09:00');
    }
}
