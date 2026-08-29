<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * B2 — "Agregar opcion de CC en los emails desde la plataforma".
 *
 * Criterios 6 y 7: los endpoints leen 'cc' del body y lo validan con
 * BrevoEmailService::assertValidClientCc() antes de propagarlo.
 *
 * Los controladores no son unit-testables aqui (necesitan HTTP + BD, prohibidos
 * en esta suite), asi que se verifica el cableado a nivel de fuente. La
 * validacion real de assertValidClientCc() se cubre en BrevoEmailServiceCcTest.
 *
 * @internal
 */
final class EmailCcEndpointWiringTest extends CIUnitTestCase
{
    /**
     * @return array<string,array{0:string,1:string}>
     */
    public static function controllerProvider(): array
    {
        return [
            'ReservationController::sendTemplateEmail' => [
                'Controllers/ReservationController.php',
                'sendTemplateEmail',
            ],
            'EmailTemplateController::sendCustomEmail' => [
                'Controllers/EmailTemplateController.php',
                'sendCustomEmail',
            ],
        ];
    }

    /**
     * @dataProvider controllerProvider
     */
    public function testEndpointReadsAndValidatesCcFromBody(string $relPath, string $method): void
    {
        $source = file_get_contents(APPPATH . $relPath);
        $this->assertNotFalse($source);

        $start = strpos($source, 'public function ' . $method . '(');
        $this->assertNotFalse($start, "no se encontro {$method}()");

        // Fin del metodo: siguiente 'public function' o fin de archivo.
        $next = strpos($source, 'public function ', $start + 10);
        $body = $next === false ? substr($source, $start) : substr($source, $start, $next - $start);

        $this->assertMatchesRegularExpression(
            '/BrevoEmailService::assertValidClientCc\(\s*\$data\[[\'"]cc[\'"]\]/',
            $body,
            "{$method}() debe validar \$data['cc'] con assertValidClientCc()"
        );
        $this->assertStringContainsString(
            'use App\Services\BrevoEmailService;',
            $source,
            'el controlador debe importar BrevoEmailService'
        );
    }

    public function testReservationControllerForwardsCcToService(): void
    {
        $source = file_get_contents(APPPATH . 'Controllers/ReservationController.php');

        $this->assertMatchesRegularExpression(
            '/sendTemplateEmail\([^;]*\$cc\s*\)/s',
            $source,
            'sendTemplateEmail() del controlador debe pasar $cc al servicio'
        );
    }

    public function testEmailTemplateControllerForwardsCcToBrevo(): void
    {
        $source = file_get_contents(APPPATH . 'Controllers/EmailTemplateController.php');

        $this->assertMatchesRegularExpression(
            '/sendEmail\([^;]*\$cc\s*\)/s',
            $source,
            'sendCustomEmail() debe pasar $cc a BrevoEmailService::sendEmail()'
        );
    }

    public function testReservationEmailHistoryModelAllowsCcEmailsColumn(): void
    {
        $model = new \App\Models\ReservationEmailHistoryModel();

        $ref = new \ReflectionProperty($model, 'allowedFields');
        $ref->setAccessible(true);

        $this->assertContains('cc_emails', $ref->getValue($model));
    }

    public function testCcEmailsMigrationCreatesNullableVarchar500(): void
    {
        $file = APPPATH . 'Database/Migrations/2026-08-28-020000_AddCcEmailsToReservationEmailHistory.php';
        $this->assertFileExists($file);

        $source = file_get_contents($file);
        $this->assertStringContainsString("'cc_emails'", $source);
        $this->assertStringContainsString("'type'       => 'VARCHAR'", $source);
        $this->assertStringContainsString("'constraint' => 500", $source);
        $this->assertStringContainsString("'null'       => true", $source);
        $this->assertStringContainsString("dropColumn('reservation_email_history', 'cc_emails')", $source);
    }
}
