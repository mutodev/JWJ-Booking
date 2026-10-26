<?php

namespace Tests\Unit;

use App\Filters\MaintenanceMode;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

final class MaintenanceModeTest extends CIUnitTestCase
{
    private string|false $previous;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previous = getenv('app.maintenanceEnabled');
    }

    protected function tearDown(): void
    {
        putenv($this->previous === false
            ? 'app.maintenanceEnabled'
            : 'app.maintenanceEnabled=' . $this->previous);

        parent::tearDown();
    }

    public function testDisabledModeAllowsRequest(): void
    {
        putenv('app.maintenanceEnabled=false');

        $this->assertNull((new MaintenanceMode())->before($this->requestFor('/')));
    }

    public function testEveryRouteReceivesTheSameStaticMaintenancePageAnd503(): void
    {
        putenv('app.maintenanceEnabled=true');

        $filter = new MaintenanceMode();
        $paths = [
            '/',
            '/book',
            '/admin/reservations',
            '/api/home/services/123',
            '/api/stripe/webhook',
            '/an/unknown/url',
        ];

        foreach ($paths as $path) {
            $response = $filter->before($this->requestFor($path));

            $this->assertSame(503, $response->getStatusCode(), $path);
            $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'), $path);
            $this->assertStringContainsString('We&rsquo;re making things even more magical', $response->getBody(), $path);
        }
    }

    private function requestFor(string $path): IncomingRequest
    {
        return new IncomingRequest(
            new App(),
            new URI('http://example.com' . $path),
            null,
            new UserAgent()
        );
    }
}
