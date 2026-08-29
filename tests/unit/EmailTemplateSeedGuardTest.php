<?php

namespace Tests\Unit;

use App\Database\Seeds\Support\EmailTemplateSeedGuard;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * A2 — Guarda no destructiva de los seeders de email_templates.
 *
 * Prueba la DECISIÓN PURA del trait EmailTemplateSeedGuard sin base de datos:
 * se dobla la conexión (`seedGuardDb()`) con un fake del query builder.
 *
 *   - templateIsCustomized(): true solo si is_customized = 1; false si la fila
 *     no existe, si is_customized = 0, o si la columna aún no existe.
 *   - safeUpdateTemplate(): salta el UPDATE (retorna false) cuando la plantilla
 *     está personalizada; ejecuta el UPDATE (retorna true) cuando no lo está.
 *   - degradación: sin la columna is_customized (o si fieldExists lanza), el
 *     trait se comporta como antes (todo es "no personalizado", guardas vacías).
 *   - customizationGuardSql(): fragmento SQL correcto con y sin WHERE previo.
 *
 * NOTA sobre las clases harness: emailTemplatesHaveCustomizationFlag() cachea el
 * resultado en un `static` por-clase. Por eso hay dos harness idénticos: uno para
 * el escenario "columna presente" y otro para "columna ausente/degradado", de
 * modo que el cache estático nunca cruza escenarios.
 *
 * @internal
 */
final class EmailTemplateSeedGuardTest extends CIUnitTestCase
{
    // ---------------------------------------------------------------------
    // Columna presente
    // ---------------------------------------------------------------------

    public function testCustomizedRowIsDetected(): void
    {
        $db = new FakeSeedGuardDb();
        $db->rows['payment_notification'] = ['is_customized' => 1];
        $harness = new SeedGuardHarnessWithColumn($db);

        $this->assertTrue($harness->callTemplateIsCustomized('payment_notification'));
    }

    public function testNonCustomizedRowIsNotDetected(): void
    {
        $db = new FakeSeedGuardDb();
        $db->rows['welcome'] = ['is_customized' => 0];
        $harness = new SeedGuardHarnessWithColumn($db);

        $this->assertFalse($harness->callTemplateIsCustomized('welcome'));
    }

    public function testMissingRowIsNotCustomized(): void
    {
        $harness = new SeedGuardHarnessWithColumn(new FakeSeedGuardDb());

        $this->assertFalse($harness->callTemplateIsCustomized('does_not_exist'));
    }

    public function testSafeUpdateSkipsCustomizedTemplate(): void
    {
        $db = new FakeSeedGuardDb();
        $db->rows['reservation_confirmation'] = ['is_customized' => 1];
        $harness = new SeedGuardHarnessWithColumn($db);

        $ran = $harness->callSafeUpdateTemplate('reservation_confirmation', ['body' => 'NEW']);

        $this->assertFalse($ran, 'safeUpdateTemplate debe retornar false al saltar');
        $this->assertSame([], $db->updates, 'no debe ejecutarse ningún UPDATE');
    }

    public function testSafeUpdateRunsForNonCustomizedTemplate(): void
    {
        $db = new FakeSeedGuardDb();
        $db->rows['week_reminder'] = ['is_customized' => 0];
        $harness = new SeedGuardHarnessWithColumn($db);

        $ran = $harness->callSafeUpdateTemplate('week_reminder', ['body' => 'NEW BODY']);

        $this->assertTrue($ran);
        $this->assertCount(1, $db->updates);
        $this->assertSame(['slug' => 'week_reminder'], $db->updates[0]['where']);
        $this->assertSame(['body' => 'NEW BODY'], $db->updates[0]['data']);
    }

    public function testSafeUpdateRunsWhenRowDoesNotExistYet(): void
    {
        // Slug sin fila previa: no está "personalizado" => el UPDATE corre
        // (afectará 0 filas en DB real; el INSERT lo maneja el propio seeder).
        $db = new FakeSeedGuardDb();
        $harness = new SeedGuardHarnessWithColumn($db);

        $this->assertTrue($harness->callSafeUpdateTemplate('brand_new_slug', ['body' => 'x']));
        $this->assertCount(1, $db->updates);
    }

    public function testGuardSqlWithColumnPresent(): void
    {
        $harness = new SeedGuardHarnessWithColumn(new FakeSeedGuardDb());

        $this->assertSame(' WHERE is_customized = 0', $harness->callCustomizationGuardSql(false));
        $this->assertSame(' AND is_customized = 0', $harness->callCustomizationGuardSql(true));
    }

    public function testHasFlagTrueWhenColumnPresent(): void
    {
        $this->assertTrue((new SeedGuardHarnessWithColumn(new FakeSeedGuardDb()))->callHasFlag());
    }

    // ---------------------------------------------------------------------
    // Columna ausente / degradación
    // ---------------------------------------------------------------------

    public function testDegradesWhenColumnAbsent(): void
    {
        $db = new FakeSeedGuardDb();
        $db->hasColumn = false;
        // Aunque hubiera una fila marcada, sin columna no se puede saber.
        $db->rows['payment_notification'] = ['is_customized' => 1];
        $harness = new SeedGuardHarnessNoColumn($db);

        $this->assertFalse($harness->callHasFlag());
        $this->assertFalse($harness->callTemplateIsCustomized('payment_notification'));

        // El UPDATE corre siempre (comportamiento histórico intacto).
        $this->assertTrue($harness->callSafeUpdateTemplate('payment_notification', ['body' => 'x']));
        $this->assertCount(1, $db->updates);

        // Sin columna, el fragmento SQL es vacío => no altera queries existentes.
        $this->assertSame('', $harness->callCustomizationGuardSql(false));
        $this->assertSame('', $harness->callCustomizationGuardSql(true));
    }

    public function testDegradesWhenFieldExistsThrows(): void
    {
        $db = new FakeSeedGuardDb();
        $db->fieldExistsThrows = true;
        $harness = new SeedGuardHarnessNoColumn($db);

        $this->assertFalse($harness->callHasFlag());
        $this->assertFalse($harness->callTemplateIsCustomized('anything'));
    }
}

/**
 * Fake mínimo de la conexión de CodeIgniter para el trait.
 */
class FakeSeedGuardDb
{
    public bool $hasColumn = true;
    public bool $fieldExistsThrows = false;

    /** @var array<string,array<string,mixed>> slug => row */
    public array $rows = [];

    /** @var list<array{where:array,data:array}> */
    public array $updates = [];

    public function fieldExists(string $field, string $table): bool
    {
        if ($this->fieldExistsThrows) {
            throw new \RuntimeException('simulated: no metadata access');
        }

        return $this->hasColumn;
    }

    public function table(string $table): FakeSeedGuardBuilder
    {
        return new FakeSeedGuardBuilder($this);
    }
}

class FakeSeedGuardBuilder
{
    private array $wheres = [];

    public function __construct(private FakeSeedGuardDb $db)
    {
    }

    public function select($fields): self
    {
        return $this;
    }

    public function where($key, $value = null): self
    {
        $this->wheres[$key] = $value;

        return $this;
    }

    public function get(): FakeSeedGuardResult
    {
        $slug = $this->wheres['slug'] ?? null;

        return new FakeSeedGuardResult($this->db->rows[$slug] ?? null);
    }

    public function update(array $data): bool
    {
        $this->db->updates[] = ['where' => $this->wheres, 'data' => $data];

        return true;
    }
}

class FakeSeedGuardResult
{
    public function __construct(private ?array $row)
    {
    }

    public function getRowArray(): ?array
    {
        return $this->row;
    }
}

/**
 * Wrappers públicos para invocar los métodos protegidos del trait bajo prueba.
 * Es un trait (no una clase base) a propósito: así CADA harness que lo usa
 * recibe su PROPIA copia de emailTemplatesHaveCustomizationFlag() y, por tanto,
 * su propio `static $exists`. Una clase base compartida filtraría el cache
 * entre escenarios.
 */
trait SeedGuardHarnessHelpers
{
    use EmailTemplateSeedGuard;

    public function __construct(protected FakeSeedGuardDb $fakeDb)
    {
    }

    protected function seedGuardDb()
    {
        return $this->fakeDb;
    }

    public function callTemplateIsCustomized(string $slug): bool
    {
        return $this->templateIsCustomized($slug);
    }

    public function callSafeUpdateTemplate(string $slug, array $data): bool
    {
        return $this->safeUpdateTemplate($slug, $data);
    }

    public function callCustomizationGuardSql(bool $hasWhere): string
    {
        return $this->customizationGuardSql($hasWhere);
    }

    public function callHasFlag(): bool
    {
        return $this->emailTemplatesHaveCustomizationFlag();
    }
}

/** Escenario: columna is_customized presente (cache estático propio). */
class SeedGuardHarnessWithColumn
{
    use SeedGuardHarnessHelpers;
}

/** Escenario: columna ausente / degradación (cache estático propio). */
class SeedGuardHarnessNoColumn
{
    use SeedGuardHarnessHelpers;
}
