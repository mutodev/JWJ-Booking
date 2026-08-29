<?php

namespace Tests\Unit;

use App\Commands\SendAbandonedCartFollowUps;
use App\Commands\SendWeekReminders;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * B4 — criterio 1 (nuevo comando, espejo estructural de SendWeekReminders) y
 * criterio 9 (linea de crontab documentada).
 *
 * El comando NO se ejecuta aqui: run() emite por CLI::write y la suite corre con
 * beStrictAboutOutputDuringTests=true. Ademas el comando instancia
 * ReservationDraftService con `new` (no inyectable), asi que un end-to-end real
 * exigiria base de datos. Se valida la forma de la clase por reflexion y la
 * documentacion del cron por inspeccion de archivos — la logica de negocio ya
 * esta cubierta en ReservationDraftServiceFollowUpTest.
 *
 * @internal
 */
final class SendAbandonedCartFollowUpsCommandTest extends CIUnitTestCase
{
    private function prop(string $name): mixed
    {
        return (new \ReflectionProperty(SendAbandonedCartFollowUps::class, $name))->getDefaultValue();
    }

    public function testExtendsBaseCommand(): void
    {
        $this->assertTrue(is_subclass_of(SendAbandonedCartFollowUps::class, BaseCommand::class));
    }

    public function testGroupIsReservations(): void
    {
        $this->assertSame('Reservations', $this->prop('group'));
    }

    public function testCommandNameIsCartsFollowup(): void
    {
        $this->assertSame('carts:followup', $this->prop('name'));
    }

    public function testCommandHasNonEmptyDescription(): void
    {
        $this->assertIsString($this->prop('description'));
        $this->assertNotEmpty($this->prop('description'));
    }

    public function testMirrorsWeekRemindersStructure(): void
    {
        // mismo grupo que el recordatorio semanal
        $this->assertSame(
            (new \ReflectionProperty(SendWeekReminders::class, 'group'))->getDefaultValue(),
            $this->prop('group')
        );

        // misma firma publica run(array $params)
        $run = new \ReflectionMethod(SendAbandonedCartFollowUps::class, 'run');
        $this->assertTrue($run->isPublic());
        $this->assertSame(1, $run->getNumberOfParameters());
        $this->assertSame('array', (string) $run->getParameters()[0]->getType());
    }

    public function testRunDelegatesToReservationDraftServiceFollowUp(): void
    {
        $src = file_get_contents(APPPATH . 'Commands/SendAbandonedCartFollowUps.php');

        $this->assertStringContainsString('ReservationDraftService', $src);
        $this->assertStringContainsString('sendAbandonedFollowUps(', $src);
    }

    // -------------------------------------------------------------------------
    // criterio 9 — crontab documentado
    // -------------------------------------------------------------------------

    public function testCrontabLineIsDocumentedInCommandDocblock(): void
    {
        $src = file_get_contents(APPPATH . 'Commands/SendAbandonedCartFollowUps.php');

        $this->assertStringContainsString('crontab', $src);
        $this->assertStringContainsString('spark carts:followup', $src);
    }

    public function testCrontabLineIsDocumentedInDeploymentDoc(): void
    {
        $src = file_get_contents(ROOTPATH . 'docs/DEPLOYMENT.md');

        $this->assertStringContainsString('Scheduled Tasks (Cron)', $src);
        $this->assertStringContainsString('spark carts:followup', $src);
        $this->assertMatchesRegularExpression('/\d+ \d+ \* \* \* .*spark carts:followup/', $src);
    }
}
