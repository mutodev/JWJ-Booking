<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Tasks\Scheduler as TaskScheduler;

class Scheduler extends BaseConfig
{
    public function __invoke(TaskScheduler $schedule)
    {
        // Envía recordatorio semanal cada día a las 9am
        $schedule->command('reminders:week')->daily('09:00');
    }
}
