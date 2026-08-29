<?php

namespace Tests\Unit;

use App\Repositories\EmailTemplateRepository;
use App\Services\AuthService;
use App\Services\EmailTemplateService;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * A2 — "Editar plantillas de email sin que se reseteen todas".
 *
 * Cubre EmailTemplateService::update(): validación de subject/content/is_active
 * y el bloque $systemFields (is_customized / customized_at / customized_by) que
 * marca la plantilla como editada por un admin para que los seeders no la pisen.
 *
 * Sin base de datos: el EmailTemplateRepository se sustituye por un doble PHPUnit
 * vía Reflection y se capturan los argumentos con los que se invoca update().
 * El servicio 'auth' se sustituye con un AuthService real + setUser().
 *
 * @internal
 */
final class EmailTemplateServiceUpdateTest extends CIUnitTestCase
{
    private EmailTemplateService $service;

    /** @var \PHPUnit\Framework\MockObject\MockObject&EmailTemplateRepository */
    private $repo;

    /** Objeto devuelto por repository::getById(). null = plantilla inexistente. */
    private $existing;

    /** Valor de retorno simulado de repository::update(). */
    private bool $updateReturn = true;

    /** @var array{id:string,data:array,systemFields:array}|null */
    private $capturedUpdate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->existing       = (object) ['id' => 'tpl-1', 'slug' => 'welcome', 'name' => 'Welcome'];
        $this->updateReturn   = true;
        $this->capturedUpdate = null;

        $this->repo = $this->createMock(EmailTemplateRepository::class);
        $this->repo->method('getById')->willReturnCallback(fn ($id) => $this->existing);
        $this->repo->method('update')->willReturnCallback(function ($id, $data, $systemFields = []) {
            $this->capturedUpdate = ['id' => $id, 'data' => $data, 'systemFields' => $systemFields];

            return $this->updateReturn;
        });

        $this->service = new EmailTemplateService();
        $ref = new \ReflectionProperty(EmailTemplateService::class, 'repository');
        $ref->setAccessible(true);
        $ref->setValue($this->service, $this->repo);

        $this->setAuthUser((object) ['first_name' => 'Jamie', 'last_name' => 'Admin']);
    }

    protected function tearDown(): void
    {
        \Config\Services::reset(true);
        parent::tearDown();
    }

    private function setAuthUser($user): void
    {
        $auth = new AuthService();
        $auth->setUser($user);
        \Config\Services::injectMock('auth', $auth);
    }

    private function assertHttpException(int $expectedCode, callable $fn): void
    {
        try {
            $fn();
            $this->fail('Expected HTTPException with code ' . $expectedCode);
        } catch (HTTPException $e) {
            $this->assertSame($expectedCode, $e->getCode());
        }
    }

    // -------------------------------------------------------------------------
    // Validación: subject
    // -------------------------------------------------------------------------

    public function testEmptySubjectThrows400(): void
    {
        $this->assertHttpException(400, fn () => $this->service->update('tpl-1', ['subject' => '']));
        $this->assertNull($this->capturedUpdate, 'no debe llegar al repositorio');
    }

    public function testWhitespaceOnlySubjectThrows400(): void
    {
        $this->assertHttpException(400, fn () => $this->service->update('tpl-1', ['subject' => "   \t\n"]));
    }

    public function testNonStringSubjectThrows400(): void
    {
        $this->assertHttpException(400, fn () => $this->service->update('tpl-1', ['subject' => 123]));
    }

    public function testSubjectOf255CharsIsAccepted(): void
    {
        $result = $this->service->update('tpl-1', ['subject' => str_repeat('a', 255)]);

        $this->assertTrue($result);
        $this->assertNotNull($this->capturedUpdate);
        $this->assertSame(str_repeat('a', 255), $this->capturedUpdate['data']['subject']);
    }

    public function testSubjectOf256CharsThrows400(): void
    {
        $this->assertHttpException(400, fn () => $this->service->update('tpl-1', ['subject' => str_repeat('a', 256)]));
    }

    public function testSubjectLengthIsCountedInCharactersNotBytes(): void
    {
        // 255 multibyte chars = 255 for mb_strlen, 510 for strlen.
        $result = $this->service->update('tpl-1', ['subject' => str_repeat('é', 255)]);
        $this->assertTrue($result);

        // 256 multibyte chars must still be rejected.
        $this->assertHttpException(400, fn () => $this->service->update('tpl-1', ['subject' => str_repeat('é', 256)]));
    }

    public function testSubjectKeyAbsentSkipsSubjectValidation(): void
    {
        // Sin la clave 'subject' no se valida longitud/no-vacío.
        $result = $this->service->update('tpl-1', ['body' => 'Just a body change']);
        $this->assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // Validación: content
    // -------------------------------------------------------------------------

    public function testValidJsonContentIsAccepted(): void
    {
        $result = $this->service->update('tpl-1', ['content' => '{"intro":"x"}']);

        $this->assertTrue($result);
        $this->assertSame('{"intro":"x"}', $this->capturedUpdate['data']['content']);
    }

    public function testInvalidJsonContentThrows400(): void
    {
        $this->assertHttpException(400, fn () => $this->service->update('tpl-1', ['content' => '{no-json']));
    }

    public function testWhitespaceContentThrows400(): void
    {
        $this->assertHttpException(400, fn () => $this->service->update('tpl-1', ['content' => '   ']));
    }

    public function testNonStringContentThrows400(): void
    {
        $this->assertHttpException(400, fn () => $this->service->update('tpl-1', ['content' => ['intro' => 'x']]));
    }

    // -------------------------------------------------------------------------
    // Normalización: is_active
    // -------------------------------------------------------------------------

    public function testTruthyIsActiveIsPersistedAsInteger1(): void
    {
        foreach ([true, 1, '1', 'on'] as $truthy) {
            $this->service->update('tpl-1', ['is_active' => $truthy]);
            $this->assertSame(1, $this->capturedUpdate['data']['is_active'], 'value: ' . var_export($truthy, true));
        }
    }

    public function testFalsyIsActiveIsPersistedAsInteger0(): void
    {
        foreach ([false, 0, '0', ''] as $falsy) {
            $this->service->update('tpl-1', ['is_active' => $falsy]);
            $this->assertSame(0, $this->capturedUpdate['data']['is_active'], 'value: ' . var_export($falsy, true));
        }
    }

    // -------------------------------------------------------------------------
    // $systemFields — marca de personalización
    // -------------------------------------------------------------------------

    public function testSuccessfulUpdatePassesCustomizationSystemFields(): void
    {
        $this->service->update('tpl-1', ['subject' => 'Hello there']);

        $sys = $this->capturedUpdate['systemFields'];
        $this->assertSame(1, $sys['is_customized']);
        $this->assertSame('Jamie Admin', $sys['customized_by']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $sys['customized_at']);
    }

    public function testCustomizedByFallsBackToEmailWhenNoName(): void
    {
        $this->setAuthUser((object) ['email' => 'ops@jamwithjamie.com']);

        $this->service->update('tpl-1', ['subject' => 'Hello']);

        $this->assertSame('ops@jamwithjamie.com', $this->capturedUpdate['systemFields']['customized_by']);
    }

    public function testCustomizedByFallsBackToSystemWhenNoAuthenticatedUser(): void
    {
        $this->setAuthUser(null);

        $this->service->update('tpl-1', ['subject' => 'Hello']);

        $this->assertSame('System', $this->capturedUpdate['systemFields']['customized_by']);
    }

    public function testInputCannotFalsifyIsCustomizedFlag(): void
    {
        // El cliente intenta mandar is_customized => 0 (y campos de sistema).
        $this->service->update('tpl-1', [
            'subject'       => 'Hello',
            'is_customized' => 0,
            'customized_by' => 'attacker',
            'customized_at' => '1999-01-01 00:00:00',
        ]);

        $sys = $this->capturedUpdate['systemFields'];
        // systemFields (que el repo mergea DESPUÉS del filtrado) siempre gana.
        $this->assertSame(1, $sys['is_customized']);
        $this->assertSame('Jamie Admin', $sys['customized_by']);
        $this->assertNotSame('1999-01-01 00:00:00', $sys['customized_at']);
    }

    public function testSystemFieldsAreSentOnEveryFieldCombination(): void
    {
        // Aunque sólo cambie el body, la plantilla queda marcada como customizada.
        $this->service->update('tpl-1', ['body' => 'New body']);

        $this->assertSame(1, $this->capturedUpdate['systemFields']['is_customized']);
    }

    // -------------------------------------------------------------------------
    // Errores del flujo
    // -------------------------------------------------------------------------

    public function testUpdateOnMissingTemplateThrows404(): void
    {
        $this->existing = null;

        $this->assertHttpException(404, fn () => $this->service->update('does-not-exist', ['subject' => 'x']));
        $this->assertNull($this->capturedUpdate);
    }

    public function testRepositoryReportingFailureThrows400(): void
    {
        $this->updateReturn = false;

        $this->assertHttpException(400, fn () => $this->service->update('tpl-1', ['subject' => 'x']));
    }

    public function testSuccessfulUpdateReturnsTrue(): void
    {
        $this->assertTrue($this->service->update('tpl-1', [
            'subject'   => 'Payment received',
            'content'   => '{"message":"Thanks {{first_name}}"}',
            'is_active' => true,
            'body'      => '<p>{{content_message}}</p>',
        ]));
    }

    public function testServiceLeavesDataFilteringToRepository(): void
    {
        // El servicio NO filtra $data por sí mismo (eso es responsabilidad del
        // repositorio contra su whitelist). Documenta el reparto de capas.
        $this->service->update('tpl-1', ['subject' => 'x', 'slug' => 'hacked', 'name' => 'hacked']);

        $this->assertArrayHasKey('slug', $this->capturedUpdate['data']);
        $this->assertArrayHasKey('name', $this->capturedUpdate['data']);
        // ...pero el is_customized que se persiste viene de systemFields.
        $this->assertSame(1, $this->capturedUpdate['systemFields']['is_customized']);
    }
}
