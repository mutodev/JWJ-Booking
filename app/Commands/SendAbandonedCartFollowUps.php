<?php

namespace App\Commands;

use App\Services\ReservationDraftService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Sends the one-time "abandoned cart" follow-up email to reservation drafts
 * that have been inactive for exactly 7 days (the [7, 8) day window) and have
 * never been contacted. Drafts older than 8 days are NOT contacted.
 *
 * CLI only — spark commands are not routable over HTTP.
 *
 * Idempotent: the [7, 8) day window plus the follow_up_sent_at marker
 * guarantee each draft receives at most one follow-up, ever. Running the command
 * twice in a row sends 0 the second time, and it is safe to run when there are
 * no abandoned drafts (it reports 0 and exits cleanly).
 *
 * MUST run once per day (the 24h window relies on it) — crontab at 01:00 server
 * time:
 *
 *   0 1 * * * cd /var/www/jamwithjamie && /usr/bin/php spark carts:followup >> /var/www/jamwithjamie/writable/logs/cron.log 2>&1
 *
 * The cron deployment is not automated from code; register the line above with
 * `crontab -e` on the VPS. See docs/DEPLOYMENT.md ("Scheduled Tasks (Cron)").
 */
class SendAbandonedCartFollowUps extends BaseCommand
{
    protected $group       = 'Reservations';
    protected $name        = 'carts:followup';
    protected $description  = 'Send the one-time follow-up email to carts abandoned exactly 7 days ago';

    public function run(array $params)
    {
        CLI::write('[' . date('Y-m-d H:i:s') . '] Running abandoned cart follow-ups...', 'yellow');

        $service = new ReservationDraftService();
        $sent    = $service->sendAbandonedFollowUps();

        CLI::write("[" . date('Y-m-d H:i:s') . "] Done. {$sent} follow-up(s) sent.", 'green');
    }
}
