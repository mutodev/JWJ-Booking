<?php

namespace Tests\Unit;

use App\Services\CustomServiceService;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Tests for CustomServiceService — CRUD validation and audit columns.
 * Injects a mock repository via reflection to avoid DB dependency.
 *
 * @internal
 */
final class CustomServiceServiceTest extends CIUnitTestCase
{
    private CustomServiceService $service;
    private object $repoMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoMock = new class {
            public array $rows = [];
            public ?array $lastCreate = null;
            public ?array $lastUpdate = null;
            public ?string $lastDeletedId = null;

            public function getById(string $id): ?object
            {
                return isset($this->rows[$id]) ? (object) $this->rows[$id] : null;
            }

            public function create(array $data)
            {
                $this->lastCreate = $data;
                $id = 'new-id';
                $this->rows[$id] = array_merge(['id' => $id], $data);
                return $id;
            }

            public function update(string $id, array $data): bool
            {
                $this->lastUpdate = $data;
                $this->rows[$id] = array_merge($this->rows[$id], $data);
                return true;
            }

            public function softDelete(string $id): bool
            {
                $this->lastDeletedId = $id;
                return true;
            }
        };

        $this->service = new CustomServiceService();

        $ref = new \ReflectionProperty(CustomServiceService::class, 'repo');
        $ref->setAccessible(true);
        $ref->setValue($this->service, $this->repoMock);
    }

    public function testCreateNormalizesDataAndSetsAuditColumns(): void
    {
        $result = $this->service->create([
            'name'   => '  Face Painting  ',
            'detail' => '  1 hour session ',
            'price'  => '150.456',
        ], 'Admin User');

        $this->assertSame('Face Painting', $this->repoMock->lastCreate['name']);
        $this->assertSame('1 hour session', $this->repoMock->lastCreate['detail']);
        $this->assertSame(150.46, $this->repoMock->lastCreate['price']);
        $this->assertSame(1, $this->repoMock->lastCreate['is_active']);
        $this->assertSame('Admin User', $this->repoMock->lastCreate['created_by']);
        $this->assertSame('Admin User', $this->repoMock->lastCreate['updated_by']);
        $this->assertSame('new-id', $result->id);
    }

    public function testCreateIgnoresUnknownFields(): void
    {
        $this->service->create(['name' => 'X service', 'price' => 10, 'id' => 'hack', 'deleted_at' => 'now'], 'Admin');

        $this->assertArrayNotHasKey('id', $this->repoMock->lastCreate);
        $this->assertArrayNotHasKey('deleted_at', $this->repoMock->lastCreate);
    }

    public function testCreateRequiresName(): void
    {
        $this->expectException(HTTPException::class);
        $this->expectExceptionCode(400);

        $this->service->create(['name' => '   ', 'price' => 10], 'Admin');
    }

    public function testCreateRequiresPrice(): void
    {
        $this->expectException(HTTPException::class);
        $this->expectExceptionCode(400);

        $this->service->create(['name' => 'Service'], 'Admin');
    }

    public function testCreateRejectsNegativePrice(): void
    {
        $this->expectException(HTTPException::class);
        $this->expectExceptionCode(400);

        $this->service->create(['name' => 'Service', 'price' => -1], 'Admin');
    }

    public function testCreateRejectsNonNumericPrice(): void
    {
        $this->expectException(HTTPException::class);
        $this->expectExceptionCode(400);

        $this->service->create(['name' => 'Service', 'price' => 'abc'], 'Admin');
    }

    public function testCreateAcceptsZeroPriceAndInactiveFlag(): void
    {
        $this->service->create(['name' => 'Free item', 'price' => 0, 'is_active' => false], 'Admin');

        $this->assertSame(0.0, $this->repoMock->lastCreate['price']);
        $this->assertSame(0, $this->repoMock->lastCreate['is_active']);
    }

    public function testCreateStoresEmptyDetailAsNull(): void
    {
        $this->service->create(['name' => 'Service', 'price' => 5, 'detail' => '  '], 'Admin');

        $this->assertNull($this->repoMock->lastCreate['detail']);
    }

    public function testUpdateOnlyTouchesSentFieldsAndSetsUpdatedBy(): void
    {
        $this->repoMock->rows['abc'] = ['id' => 'abc', 'name' => 'Old', 'price' => 10.0, 'created_by' => 'Creator'];

        $result = $this->service->update('abc', ['price' => 25], 'Editor');

        $this->assertSame(['price' => 25.0, 'updated_by' => 'Editor'], $this->repoMock->lastUpdate);
        $this->assertSame('Old', $result->name);
        $this->assertSame('Creator', $result->created_by);
    }

    public function testUpdateValidatesNameWhenSent(): void
    {
        $this->repoMock->rows['abc'] = ['id' => 'abc', 'name' => 'Old', 'price' => 10.0];

        $this->expectException(HTTPException::class);
        $this->expectExceptionCode(400);

        $this->service->update('abc', ['name' => ''], 'Editor');
    }

    public function testUpdateThrowsNotFound(): void
    {
        $this->expectException(HTTPException::class);
        $this->expectExceptionCode(404);

        $this->service->update('missing', ['price' => 5], 'Editor');
    }

    public function testDeleteSoftDeletesExisting(): void
    {
        $this->repoMock->rows['abc'] = ['id' => 'abc', 'name' => 'Old', 'price' => 10.0];

        $this->assertTrue($this->service->delete('abc'));
        $this->assertSame('abc', $this->repoMock->lastDeletedId);
    }

    public function testDeleteThrowsNotFound(): void
    {
        $this->expectException(HTTPException::class);
        $this->expectExceptionCode(404);

        $this->service->delete('missing');
    }
}
