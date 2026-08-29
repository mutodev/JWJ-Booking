<?php

namespace Tests\Unit;

use App\Models\ReservationDraftModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * B4 — "Automatico de carritos abandonados semanal (7 dias)".
 *
 * Cubre ReservationDraftModel::getAbandonedForFollowUp() (criterio 3) y verifica
 * que getAbandoned() (usado por el admin) NO cambia de comportamiento (criterio 4).
 *
 * Estrategia sin base de datos (regla del proyecto): se usa una subclase anonima
 * del modelo cuyo constructor se anula (para no abrir conexion) y que intercepta
 * where()/orderBy()/findAll() del query builder para inspeccionar los filtros
 * generados. La logica de normalizacion de $daysOld y la ventana de corte
 * (date()/strtotime() del lado servidor) se ejecutan tal cual las escribe el
 * modelo real.
 *
 * @internal
 */
final class ReservationDraftModelFollowUpTest extends CIUnitTestCase
{
    /**
     * Subclase de captura: registra cada where()/orderBy() y devuelve filas
     * controladas desde findAll() sin tocar la base de datos.
     */
    private function fakeModel(array $rows = []): object
    {
        return new class ($rows) extends ReservationDraftModel {
            /** @var array<int,array{0:mixed,1:mixed}> */
            public array $wheres = [];
            /** @var array<int,array{0:?string,1:string}> */
            public array $orderBys = [];
            /** @var array<int,object> */
            public array $rows;
            public int $findAllCalls = 0;

            public function __construct(array $rows)
            {
                $this->rows = $rows;
            }

            public function where($key, $value = null, ?bool $escape = null)
            {
                $this->wheres[] = [$key, $value];

                return $this;
            }

            public function orderBy(?string $orderBy, string $direction = '', ?bool $escape = null)
            {
                $this->orderBys[] = [$orderBy, strtoupper($direction)];

                return $this;
            }

            public function findAll(?int $limit = null, int $offset = 0)
            {
                $this->findAllCalls++;

                return $this->rows;
            }
        };
    }

    private function assertCutoffDaysAgo(string $cutoff, int $days): void
    {
        $expected = strtotime("-{$days} days");
        $actual   = strtotime($cutoff);

        $this->assertNotFalse($actual, "cutoff no es una fecha valida: {$cutoff}");
        $this->assertLessThanOrEqual(
            10,
            abs($expected - $actual),
            "el corte deberia ser ~{$days} dias atras, fue {$cutoff}"
        );
    }

    /** @return array{0:mixed,1:mixed}|null */
    private function whereFor(object $model, string $keyStartsWith): ?array
    {
        foreach ($model->wheres as $row) {
            if (is_string($row[0]) && str_starts_with($row[0], $keyStartsWith)) {
                return $row;
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // getAbandonedForFollowUp() — criterio 3
    // -------------------------------------------------------------------------

    public function testReturnsRowsFromFindAll(): void
    {
        $rows  = [(object) ['id' => 'a'], (object) ['id' => 'b']];
        $model = $this->fakeModel($rows);

        $result = $model->getAbandonedForFollowUp();

        $this->assertSame($rows, $result);
        $this->assertSame(1, $model->findAllCalls);
    }

    public function testAppliesTheFourFrozenFilters(): void
    {
        $model = $this->fakeModel();
        $model->getAbandonedForFollowUp(7);

        $this->assertContains(['completed', 0], $model->wheres, 'falta filtro completed = 0');
        $this->assertContains(['email IS NOT NULL', null], $model->wheres, 'falta filtro email IS NOT NULL');
        $this->assertContains(['email !=', ''], $model->wheres, 'falta filtro email != ""');
        $this->assertContains(['follow_up_sent_at IS NULL', null], $model->wheres, 'falta filtro follow_up_sent_at IS NULL');

        $activity = $this->whereFor($model, 'last_activity_at');
        $this->assertNotNull($activity, 'falta filtro por last_activity_at');
        $this->assertSame('last_activity_at <=', $activity[0]);
        $this->assertIsString($activity[1]);
        $this->assertCutoffDaysAgo($activity[1], 7);
    }

    public function testOrdersByLastActivityAscending(): void
    {
        $model = $this->fakeModel();
        $model->getAbandonedForFollowUp();

        $this->assertSame([['last_activity_at', 'ASC']], $model->orderBys);
    }

    public function testDefaultWindowIsSevenDays(): void
    {
        $model = $this->fakeModel();
        $model->getAbandonedForFollowUp();

        $this->assertCutoffDaysAgo($this->whereFor($model, 'last_activity_at')[1], 7);
    }

    public function testZeroDaysOldNormalisesToSeven(): void
    {
        $model = $this->fakeModel();
        $model->getAbandonedForFollowUp(0);

        $this->assertCutoffDaysAgo($this->whereFor($model, 'last_activity_at')[1], 7);
    }

    public function testNegativeDaysOldNormalisesToSeven(): void
    {
        $model = $this->fakeModel();
        $model->getAbandonedForFollowUp(-3);

        $this->assertCutoffDaysAgo($this->whereFor($model, 'last_activity_at')[1], 7);
    }

    public function testValidCustomWindowIsRespected(): void
    {
        $model = $this->fakeModel();
        $model->getAbandonedForFollowUp(14);

        $this->assertCutoffDaysAgo($this->whereFor($model, 'last_activity_at')[1], 14);
    }

    public function testReturnTypeIsAlwaysArray(): void
    {
        $model = $this->fakeModel();

        $this->assertIsArray($model->getAbandonedForFollowUp());
    }

    // -------------------------------------------------------------------------
    // getAbandoned() — criterio 4 (comportamiento intacto)
    // -------------------------------------------------------------------------

    public function testGetAbandonedKeepsHoursWindowAndStrictLessThanAndDescOrder(): void
    {
        $model = $this->fakeModel();
        $model->getAbandoned(24);

        $this->assertContains(['completed', 0], $model->wheres);
        $this->assertContains(['email IS NOT NULL', null], $model->wheres);

        $activity = $this->whereFor($model, 'last_activity_at');
        $this->assertNotNull($activity);
        $this->assertSame('last_activity_at <', $activity[0], 'getAbandoned debe seguir usando "<" estricto');

        $expected = strtotime('-24 hours');
        $this->assertLessThanOrEqual(10, abs($expected - strtotime($activity[1])), 'ventana en horas, no en dias');

        $this->assertSame([['last_activity_at', 'DESC']], $model->orderBys);
    }

    public function testGetAbandonedDoesNotFilterFollowUpSentAtNorBlankEmail(): void
    {
        $model = $this->fakeModel();
        $model->getAbandoned();

        $this->assertNull($this->whereFor($model, 'follow_up_sent_at'), 'getAbandoned no debe filtrar follow_up_sent_at');
        $this->assertNotContains(['email !=', ''], $model->wheres, 'getAbandoned no incorpora el filtro email != ""');
    }

    public function testGetAbandonedDefaultWindowIs24Hours(): void
    {
        $model = $this->fakeModel();
        $model->getAbandoned();

        $activity = $this->whereFor($model, 'last_activity_at');
        $this->assertLessThanOrEqual(10, abs(strtotime('-24 hours') - strtotime($activity[1])));
    }
}
