<?php

namespace Tests\Unit;

use App\Entities\EmailTemplate;
use App\Models\EmailTemplateModel;
use App\Repositories\EmailTemplateRepository;
use App\Services\AuthService;
use App\Services\EmailTemplateService;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * A2 — "Editar plantillas de email sin que se reseteen todas" (casos límite QA).
 *
 * Complementa el contrato EmailTemplateServiceUpdateTest (que NO se toca) con
 * los casos exigidos en las "Notas para el Tester" del PLAN_MAESTRO:
 *   - subject de 255 vs 256 caracteres multibyte (incl. emoji de 4 bytes).
 *   - content valiendo los strings JSON "null", "[]", "{}", "0" (JSON válido → aceptar)
 *     y valores PHP no-string (null, [], 0) → 400.
 *   - is_active ausente en el payload → no rompe y no se persiste.
 *   - dos updates seguidos: is_customized sigue en 1 y customized_at se regenera.
 *   - mass assignment: is_customized/customized_at/customized_by falsificados no ganan;
 *     la whitelist del Repository no contiene los 3 campos de auditoría.
 *   - customized_by: nombre parcial, objeto de usuario vacío.
 *   - regresión: render() sigue funcionando con la entidad ampliada.
 *
 * Sin base de datos: mismo andamiaje que el contrato (createMock del repositorio
 * vía Reflection + AuthService real inyectado como servicio 'auth').
 *
 * @internal
 */
final class EmailTemplateServiceUpdateEdgeCasesTest extends CIUnitTestCase
{
    private EmailTemplateService $service;

    /** @var \PHPUnit\Framework\MockObject\MockObject&EmailTemplateRepository */
    private $repo;

    /** @var object|null */
    private $existing;

    private bool $updateReturn = true;

    /** @var list<array{id:string,data:array,systemFields:array}> */
    private array $updateCalls = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->existing     = (object) ['id' => 'tpl-1', 'slug' => 'welcome', 'name' => 'Welcome'];
        $this->updateReturn = true;
        $this->updateCalls  = [];

        $this->repo = $this->createMock(EmailTemplateRepository::class);
        $this->repo->method('getById')->willReturnCallback(fn ($id) => $this->existing);
        $this->repo->method('update')->willReturnCallback(function ($id, $data, $systemFields = []) {
            $this->updateCalls[] = ['id' => $id, 'data' => $data, 'systemFields' => $systemFields];

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

    private function lastCall(): array
    {
        return $this->updateCalls[array_key_last($this->updateCalls)];
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
    // subject — límites multibyte
    // -------------------------------------------------------------------------

    public function testSubjectOf255FourByteEmojiIsAccepted(): void
    {
        // 255 code points, cada uno de 4 bytes => 1020 bytes.
        $subject = str_repeat('😀', 255);
        $this->assertSame(255, mb_strlen($subject));
        $this->assertSame(1020, strlen($subject));

        $this->assertTrue($this->service->update('tpl-1', ['subject' => $subject]));
        $this->assertSame($subject, $this->lastCall()['data']['subject']);
    }

    public function testSubjectOf256FourByteEmojiThrows400(): void
    {
        $this->assertHttpException(
            400,
            fn () => $this->service->update('tpl-1', ['subject' => str_repeat('😀', 256)])
        );
    }

    public function testSubjectOf255MixedMultibyteIsAccepted(): void
    {
        // 5 + 250 = 255 caracteres, mezcla ASCII / acentos / CJK.
        $subject = 'Hola ' . str_repeat('é', 125) . str_repeat('漢', 125);
        $this->assertSame(255, mb_strlen($subject));

        $this->assertTrue($this->service->update('tpl-1', ['subject' => $subject]));
    }

    public function testSubjectExactly254And255BoundaryAscii(): void
    {
        $this->assertTrue($this->service->update('tpl-1', ['subject' => str_repeat('a', 254)]));
        $this->assertTrue($this->service->update('tpl-1', ['subject' => str_repeat('a', 255)]));
        $this->assertHttpException(
            400,
            fn () => $this->service->update('tpl-1', ['subject' => str_repeat('a', 256)])
        );
    }

    public function testNullSubjectThrows400(): void
    {
        $this->assertHttpException(400, fn () => $this->service->update('tpl-1', ['subject' => null]));
    }

    public function testArraySubjectThrows400(): void
    {
        $this->assertHttpException(400, fn () => $this->service->update('tpl-1', ['subject' => ['x']]));
    }

    // -------------------------------------------------------------------------
    // content — JSON válido "raro" vs valores no-string
    // -------------------------------------------------------------------------

    /**
     * Diseño (PLAN_MAESTRO A2): "corchetes vacíos o cero (todos JSON válidos)".
     * El string es JSON sintácticamente válido y no vacío => se acepta y llega
     * al repositorio tal cual. Documenta la decisión de aceptar cualquier JSON.
     *
     * @dataProvider validJsonStringContentProvider
     */
    public function testValidJsonStringContentIsAccepted(string $content): void
    {
        $this->assertTrue($this->service->update('tpl-1', ['content' => $content]));
        $this->assertSame($content, $this->lastCall()['data']['content']);
    }

    public static function validJsonStringContentProvider(): array
    {
        return [
            'json null'        => ['null'],
            'empty array'      => ['[]'],
            'empty object'     => ['{}'],
            'zero'             => ['0'],
            'false literal'    => ['false'],
            'quoted string'    => ['"hi"'],
            'padded zero'      => ['  0  '],
        ];
    }

    /**
     * Valores PHP que NO son string: aunque "representen" algo vacío, el
     * servicio exige is_string() y devuelve 400.
     *
     * @dataProvider nonStringContentProvider
     */
    public function testNonStringContentValuesThrow400($content): void
    {
        $this->assertHttpException(400, fn () => $this->service->update('tpl-1', ['content' => $content]));
    }

    public static function nonStringContentProvider(): array
    {
        return [
            'php null'  => [null],
            'php array' => [[]],
            'php int 0' => [0],
            'php bool'  => [false],
        ];
    }

    public function testEmptyStringContentThrows400(): void
    {
        $this->assertHttpException(400, fn () => $this->service->update('tpl-1', ['content' => '']));
    }

    public function testContentKeyAbsentSkipsContentValidation(): void
    {
        $this->assertTrue($this->service->update('tpl-1', ['subject' => 'Only subject']));
        $this->assertArrayNotHasKey('content', $this->lastCall()['data']);
    }

    // -------------------------------------------------------------------------
    // is_active ausente
    // -------------------------------------------------------------------------

    public function testIsActiveAbsentIsNotPersistedAndDoesNotBreak(): void
    {
        $this->assertTrue($this->service->update('tpl-1', ['subject' => 'No is_active here']));
        $this->assertArrayNotHasKey('is_active', $this->lastCall()['data']);
    }

    public function testIsActiveExplicitNullNormalizesToZero(): void
    {
        // filter_var(null, FILTER_VALIDATE_BOOLEAN) === false => 0
        $this->service->update('tpl-1', ['is_active' => null]);
        $this->assertSame(0, $this->lastCall()['data']['is_active']);
    }

    // -------------------------------------------------------------------------
    // Dos updates seguidos
    // -------------------------------------------------------------------------

    public function testTwoConsecutiveUpdatesKeepIsCustomizedAndRefreshTimestamp(): void
    {
        $this->service->update('tpl-1', ['subject' => 'First edit']);
        $first = $this->lastCall()['systemFields'];

        $this->service->update('tpl-1', ['body' => 'Second edit only body']);
        $second = $this->lastCall()['systemFields'];

        $this->assertCount(2, $this->updateCalls);
        $this->assertSame(1, $first['is_customized']);
        $this->assertSame(1, $second['is_customized']);

        // customized_at se recalcula en cada llamada (nunca se arrastra del payload).
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $first['customized_at']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $second['customized_at']);
        $this->assertGreaterThanOrEqual($first['customized_at'], $second['customized_at']);

        $this->assertSame('Jamie Admin', $second['customized_by']);
    }

    public function testSecondUpdateCannotBeUndoneByForgedPayload(): void
    {
        $this->service->update('tpl-1', ['subject' => 'Real edit']);

        // El atacante intenta "revertir" la marca en un segundo update.
        $this->service->update('tpl-1', [
            'body'          => 'tweak',
            'is_customized' => 0,
            'customized_at' => null,
            'customized_by' => '',
        ]);

        $sys = $this->lastCall()['systemFields'];
        $this->assertSame(1, $sys['is_customized']);
        $this->assertNotNull($sys['customized_at']);
        $this->assertSame('Jamie Admin', $sys['customized_by']);
    }

    // -------------------------------------------------------------------------
    // Mass assignment — defensa en capas
    // -------------------------------------------------------------------------

    public function testRepositoryWhitelistExcludesAuditFields(): void
    {
        $ref = new \ReflectionProperty(EmailTemplateRepository::class, 'allowedFields');
        $ref->setAccessible(true);
        $allowed = $ref->getValue(new EmailTemplateRepository());

        foreach (['is_customized', 'customized_at', 'customized_by'] as $field) {
            $this->assertNotContains(
                $field,
                $allowed,
                "El campo de auditoría '{$field}' NO debe estar en la whitelist del Repository."
            );
        }
        // Los campos que sí edita el cliente siguen presentes.
        foreach (['subject', 'body', 'content', 'is_active'] as $field) {
            $this->assertContains($field, $allowed);
        }
    }

    public function testModelAllowedFieldsIncludeAuditFields(): void
    {
        $ref = new \ReflectionProperty(EmailTemplateModel::class, 'allowedFields');
        $ref->setAccessible(true);
        $allowed = $ref->getValue(new EmailTemplateModel());

        foreach (['is_customized', 'customized_at', 'customized_by'] as $field) {
            $this->assertContains($field, $allowed);
        }
    }

    public function testEntityCastsAndDatesIncludeCustomizationFields(): void
    {
        $entity = new EmailTemplate();

        $dates = (new \ReflectionProperty($entity, 'dates'))->getValue($entity);
        $this->assertContains('customized_at', $dates);

        $casts = (new \ReflectionProperty($entity, 'casts'))->getValue($entity);
        $this->assertSame('boolean', $casts['is_customized'] ?? null);
    }

    public function testForgedAuditFieldsArePassedThroughDataButOverriddenBySystemFields(): void
    {
        $this->service->update('tpl-1', [
            'subject'       => 'Hello',
            'is_customized' => 0,
            'customized_by' => 'attacker',
            'customized_at' => '1999-01-01 00:00:00',
        ]);

        $call = $this->lastCall();
        // El servicio no filtra $data (eso es del Repository), pero systemFields manda.
        $this->assertSame(1, $call['systemFields']['is_customized']);
        $this->assertSame('Jamie Admin', $call['systemFields']['customized_by']);
        $this->assertNotSame('1999-01-01 00:00:00', $call['systemFields']['customized_at']);
    }

    // -------------------------------------------------------------------------
    // customized_by — resolución de identidad
    // -------------------------------------------------------------------------

    public function testCustomizedByUsesFirstNameOnlyWhenNoLastName(): void
    {
        $this->setAuthUser((object) ['first_name' => 'Jamie']);
        $this->service->update('tpl-1', ['subject' => 'x']);
        $this->assertSame('Jamie', $this->lastCall()['systemFields']['customized_by']);
    }

    public function testCustomizedByFallsBackToSystemWhenUserObjectIsEmpty(): void
    {
        $this->setAuthUser((object) []);
        $this->service->update('tpl-1', ['subject' => 'x']);
        $this->assertSame('System', $this->lastCall()['systemFields']['customized_by']);
    }

    public function testCustomizedByTrimsWhitespaceOnlyName(): void
    {
        $this->setAuthUser((object) ['first_name' => '  ', 'last_name' => '  ', 'email' => 'a@b.com']);
        $this->service->update('tpl-1', ['subject' => 'x']);
        $this->assertSame('a@b.com', $this->lastCall()['systemFields']['customized_by']);
    }

    // -------------------------------------------------------------------------
    // Regresión: render() sigue operando con la entidad ampliada
    // -------------------------------------------------------------------------

    public function testRenderStillWorksWithExtendedEntity(): void
    {
        $tpl = new EmailTemplate([
            'id'            => 'tpl-1',
            'slug'          => 'welcome',
            'name'          => 'Welcome',
            'subject'       => 'Hello {{name}}',
            'body'          => '<p>{{content_greeting}} {{name}}</p>',
            'content'       => '{"greeting":"Hi"}',
            'is_active'     => 1,
            'is_customized' => 1,
            'customized_at' => '2026-08-28 10:00:00',
            'customized_by' => 'Jamie Admin',
        ]);

        $repo = $this->createMock(EmailTemplateRepository::class);
        $repo->method('getBySlug')->willReturn($tpl);

        $service = new EmailTemplateService();
        $ref = new \ReflectionProperty(EmailTemplateService::class, 'repository');
        $ref->setAccessible(true);
        $ref->setValue($service, $repo);

        $out = $service->render('welcome', ['name' => 'World']);

        $this->assertSame('Hello World', $out['subject']);
        $this->assertStringContainsString('Hi World', $out['body']);
    }

    public function testRenderFallsBackWhenTemplateInactiveRegardlessOfCustomizationFlag(): void
    {
        $tpl = new EmailTemplate([
            'slug'          => 'welcome',
            'subject'       => 'x',
            'body'          => 'y',
            'is_active'     => 0,
            'is_customized' => 1,
        ]);

        $repo = $this->createMock(EmailTemplateRepository::class);
        $repo->method('getBySlug')->willReturn($tpl);

        $service = new EmailTemplateService();
        $ref = new \ReflectionProperty(EmailTemplateService::class, 'repository');
        $ref->setAccessible(true);
        $ref->setValue($service, $repo);

        $out = $service->render('welcome', ['password' => 'secret']);
        // Fallback PHP view => asunto fijo de bienvenida.
        $this->assertSame('Welcome to Jam with Jamie', $out['subject']);
    }
}
