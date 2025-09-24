# Repositories - Capa de Acceso a Datos

## Arquitectura de Repositorios

Los repositorios en JamWithJamie implementan el patrón Repository para abstraer el acceso a datos. Proporcionan una interfaz consistente para operaciones de base de datos y encapsulan la lógica de consulta compleja.

### Principios de Diseño

1. **Data Abstraction**: Abstraen detalles de la base de datos
2. **Query Optimization**: Consultas SQL optimizadas
3. **Relationship Management**: Gestión eficiente de relaciones
4. **Caching Strategy**: Implementan caching cuando apropiado
5. **Error Handling**: Manejo consistente de errores de BD

---

## 🎪 ReservationRepository

**Archivo**: `app/Repositories/ReservationRepository.php`
**Propósito**: Acceso a datos de reservas con relaciones complejas

### Funcionalidades Principales

#### `getAll(): array`
Lista completa de reservas con información relacionada.

```sql
SELECT r.*,
       CONCAT(c.first_name, ' ', c.last_name) as full_name,
       c.email, c.phone,
       sp.amount as service_amount,
       s.name as service_name,
       co.name as county_name,
       ci.name as city_name,
       z.zipcode
FROM reservations r
LEFT JOIN customers c ON r.customer_id = c.id
LEFT JOIN service_prices sp ON r.service_price_id = sp.id
LEFT JOIN services s ON sp.service_id = s.id
LEFT JOIN zipcodes z ON r.zipcode_id = z.id
LEFT JOIN cities ci ON z.city_id = ci.id
LEFT JOIN counties co ON ci.county_id = co.id
WHERE r.deleted_at IS NULL
ORDER BY r.created_at DESC
```

#### `getById(string $id): ?object`
Reserva específica con todas las relaciones.

#### `create(array $data): mixed`
Crear nueva reserva con validación de integridad.

#### `update(string $id, array $data): bool`
Actualizar reserva existente.

#### `delete(string $id): bool`
Eliminación lógica (soft delete).

### Consultas Especializadas

#### `getByStatus(string $status): array`
Reservas filtradas por estado.

#### `getByDateRange(string $startDate, string $endDate): array`
Reservas en rango de fechas específico.

#### `getByCustomer(string $customerId): array`
Historial de reservas de un cliente.

#### `getRevenueByPeriod(string $period): array`
Ingresos agrupados por período.

---

## 👤 CustomerRepository

**Archivo**: `app/Repositories/CustomerRepository.php`
**Propósito**: Gestión de datos de clientes

### Funcionalidades

#### `getAll(): array`
Lista completa de clientes activos.

#### `getById(string $id): ?object`
Cliente específico por ID.

#### `getByEmail(string $email): ?object`
Buscar cliente por email (único).

```php
public function getByEmail(string $email): ?object
{
    return $this->model
        ->where('email', $email)
        ->where('deleted_at', null)
        ->first();
}
```

#### `searchByName(string $name): array`
Búsqueda por nombre con LIKE.

```sql
SELECT * FROM customers
WHERE (first_name LIKE '%{name}%' OR last_name LIKE '%{name}%')
AND deleted_at IS NULL
ORDER BY first_name, last_name
```

#### `create(array $data): string`
Crear cliente con validación de email único.

#### `update(string $id, array $data): bool`
Actualizar información del cliente.

#### `getWithReservationStats(string $id): array`
Cliente con estadísticas de reservas.

```php
public function getWithReservationStats(string $id): array
{
    $customer = $this->getById($id);
    if (!$customer) return null;

    $stats = $this->model
        ->select('COUNT(*) as total_reservations, SUM(total_amount) as total_spent')
        ->join('reservations r', 'r.customer_id = customers.id')
        ->where('customers.id', $id)
        ->where('r.deleted_at', null)
        ->first();

    return [
        'customer' => $customer,
        'stats' => $stats
    ];
}
```

---

## 🎭 ServiceRepository

**Archivo**: `app/Repositories/ServiceRepository.php`
**Propósito**: Gestión del catálogo de servicios

### Funcionalidades

#### `getAll(): array`
Catálogo completo de servicios.

#### `getAllActive(): array`
Solo servicios activos y disponibles.

#### `getById(string $id): ?object`
Servicio específico con detalles.

#### `getWithPrices(string $id): array`
Servicio con todos sus precios por ubicación.

```php
public function getWithPrices(string $id): array
{
    $service = $this->getById($id);
    if (!$service) return null;

    $prices = $this->db->table('service_prices sp')
        ->select('sp.*, c.name as county_name, ma.name as area_name')
        ->join('counties c', 'sp.county_id = c.id')
        ->join('metropolitan_areas ma', 'c.metropolitan_area_id = ma.id')
        ->where('sp.service_id', $id)
        ->where('sp.deleted_at', null)
        ->get()
        ->getResultArray();

    return [
        'service' => $service,
        'prices' => $prices
    ];
}
```

#### CRUD Operations
- `create()`: Nuevo servicio
- `update()`: Actualizar servicio
- `delete()`: Eliminación lógica

---

## 💲 ServicePriceRepository

**Archivo**: `app/Repositories/ServicePriceRepository.php`
**Propósito**: Precios de servicios por ubicación

### Funcionalidades

#### `getAllByCounty(string $countyId): array`
Precios disponibles por condado.

```sql
SELECT sp.*, s.name as service_name, s.description,
       c.name as county_name, ma.name as area_name
FROM service_prices sp
JOIN services s ON sp.service_id = s.id
JOIN counties c ON sp.county_id = c.id
JOIN metropolitan_areas ma ON c.metropolitan_area_id = ma.id
WHERE sp.county_id = ?
AND sp.deleted_at IS NULL
AND s.is_active = 1
ORDER BY s.name
```

#### `getByServiceAndCounty(string $serviceId, string $countyId): ?object`
Precio específico por servicio y ubicación.

#### `getWithRelations(string $id): array`
Precio con todas las relaciones.

#### Image Management
```php
public function updateWithImage(string $id, string $imagePath): bool
{
    return $this->update($id, ['img' => $imagePath]);
}

public function deleteImage(string $id): bool
{
    $item = $this->getById($id);
    if ($item && $item->img) {
        unlink(FCPATH . 'uploads/' . $item->img);
    }
    return $this->update($id, ['img' => null]);
}
```

---

## ➕ AddonRepository

**Archivo**: `app/Repositories/AddonRepository.php`
**Propósito**: Gestión de servicios adicionales

### Funcionalidades

#### `getAllActive(): array`
Addons disponibles para selección.

```sql
SELECT * FROM addons
WHERE is_active = 1
AND deleted_at IS NULL
ORDER BY name
```

#### `search(string $name): array`
Búsqueda de addons por nombre.

#### `getByPriceType(string $type): array`
Addons filtrados por tipo de precio.

```php
public function getByPriceType(string $type): array
{
    return $this->model
        ->where('price_type', $type)
        ->where('is_active', 1)
        ->where('deleted_at', null)
        ->findAll();
}
```

#### Image Management
Similar a ServicePriceRepository con gestión completa de imágenes.

---

## 🔗 ReservationAddonRepository

**Archivo**: `app/Repositories/ReservationAddonRepository.php`
**Propósito**: Relaciones reserva-addon con estadísticas

### Funcionalidades

#### `getByReservation(string $reservationId): array`
Addons de una reserva específica.

```sql
SELECT ra.*, a.name, a.description, a.image
FROM reservation_addons ra
JOIN addons a ON ra.addon_id = a.id
WHERE ra.reservation_id = ?
ORDER BY a.name
```

#### `getMostPopular(int $limit = 10): array`
Addons más populares con estadísticas.

```sql
SELECT a.name as addon_name,
       a.description as addon_description,
       SUM(ra.quantity) as total_sold,
       SUM(ra.quantity * ra.price_at_time) as total_revenue,
       COUNT(DISTINCT ra.reservation_id) as reservations_count,
       AVG(ra.price_at_time) as avg_price
FROM reservation_addons ra
JOIN addons a ON ra.addon_id = a.id
JOIN reservations r ON ra.reservation_id = r.id
WHERE r.status != 'cancelled'
GROUP BY a.id, a.name, a.description
ORDER BY total_sold DESC
LIMIT ?
```

#### `create(array $data): string`
Crear relación reserva-addon.

#### `updateQuantity(string $id, int $quantity): bool`
Actualizar cantidad de addon en reserva.

#### `deleteByReservation(string $reservationId): bool`
Eliminar todos los addons de una reserva.

---

## 🗺️ Repositorios de Geografía

### CountyRepository
```php
public function getAllWithAreas(): array
{
    return $this->model
        ->select('counties.*, ma.name as area_name')
        ->join('metropolitan_areas ma', 'counties.metropolitan_area_id = ma.id')
        ->where('counties.is_active', 1)
        ->orderBy('ma.name, counties.name')
        ->findAll();
}

public function getByMetropolitan(string $areaId): array
{
    return $this->model
        ->where('metropolitan_area_id', $areaId)
        ->where('is_active', 1)
        ->orderBy('name')
        ->findAll();
}
```

### CityRepository
```php
public function getByCounty(string $countyId): array
{
    return $this->model
        ->where('county_id', $countyId)
        ->where('is_active', 1)
        ->orderBy('name')
        ->findAll();
}

public function getAllWithCounties(): array
{
    return $this->model
        ->select('cities.*, c.name as county_name')
        ->join('counties c', 'cities.county_id = c.id')
        ->where('cities.is_active', 1)
        ->orderBy('c.name, cities.name')
        ->findAll();
}
```

### ZipCodeRepository
```php
public function getByCityAndCode(string $cityId, string $code): ?object
{
    return $this->model
        ->where('city_id', $cityId)
        ->where('zipcode', $code)
        ->where('is_active', 1)
        ->first();
}

public function getByCity(string $cityId): array
{
    return $this->model
        ->where('city_id', $cityId)
        ->where('is_active', 1)
        ->orderBy('zipcode')
        ->findAll();
}
```

---

## 🔧 Repositorios de Configuración

### UserRepository
Gestión de usuarios con roles y permisos.

### RoleRepository
Sistema de roles jerárquico con permisos.

### ChildrenAgeRangeRepository
```php
public function getByServicePrice(string $servicePriceId): array
{
    return $this->model
        ->where('service_price_id', $servicePriceId)
        ->where('is_active', 1)
        ->orderBy('min_age')
        ->findAll();
}
```

### DurationRepository
```php
public function getByServicePrice(string $servicePriceId): array
{
    return $this->model
        ->where('service_price_id', $servicePriceId)
        ->where('is_active', 1)
        ->orderBy('minutes')
        ->findAll();
}
```

---

## 📝 Patrones de Implementación

### Base Repository Structure
```php
class BaseRepository
{
    protected $model;
    protected $db;

    public function __construct()
    {
        $this->model = new ExampleModel();
        $this->db = \Config\Database::connect();
    }

    public function getAll(): array
    {
        return $this->model
            ->where('deleted_at', null)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function getById(string $id): ?object
    {
        return $this->model
            ->where('id', $id)
            ->where('deleted_at', null)
            ->first();
    }

    public function create(array $data): string
    {
        $data['id'] = $this->generateUUID();
        $data['created_at'] = date('Y-m-d H:i:s');

        $this->model->insert($data);
        return $data['id'];
    }

    public function update(string $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->model->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->model->update($id, [
            'deleted_at' => date('Y-m-d H:i:s')
        ]);
    }
}
```

### Query Builder Patterns
```php
// Complex joins
public function getWithRelations(): array
{
    return $this->model
        ->select('main.*, rel1.name as rel1_name, rel2.value as rel2_value')
        ->join('related_table1 rel1', 'main.rel1_id = rel1.id', 'left')
        ->join('related_table2 rel2', 'main.rel2_id = rel2.id', 'left')
        ->where('main.deleted_at', null)
        ->findAll();
}

// Aggregation queries
public function getStatistics(): array
{
    return $this->db->table($this->model->getTable())
        ->select('COUNT(*) as total, SUM(amount) as total_amount, AVG(amount) as avg_amount')
        ->where('deleted_at', null)
        ->get()
        ->getRowArray();
}

// Subqueries
public function getWithSubquery(): array
{
    $subquery = $this->db->table('related_table')
        ->select('parent_id, COUNT(*) as count')
        ->groupBy('parent_id');

    return $this->db->table($this->model->getTable() . ' main')
        ->select('main.*, sub.count')
        ->join("({$subquery->getCompiledSelect()}) sub", 'main.id = sub.parent_id', 'left')
        ->get()
        ->getResultArray();
}
```

### Transaction Management
```php
public function complexOperation(array $data): bool
{
    $this->db->transStart();

    try {
        $this->model->insert($data['main']);

        foreach ($data['related'] as $related) {
            $this->relatedModel->insert($related);
        }

        $this->db->transComplete();
        return $this->db->transStatus();

    } catch (\Exception $e) {
        $this->db->transRollback();
        throw $e;
    }
}
```

---

## 🚀 Optimizaciones y Mejores Prácticas

### Query Optimization
1. **Indexes**: Índices en columnas frecuentemente consultadas
2. **Eager Loading**: Cargar relaciones en una sola consulta
3. **Pagination**: Limitar resultados grandes
4. **Caching**: Cache de consultas frecuentes

### Security
1. **SQL Injection Prevention**: Query Builder escapa automáticamente
2. **Data Validation**: Validación en capa de repository
3. **Soft Deletes**: Eliminación lógica por defecto
4. **UUID Primary Keys**: IDs no secuenciales

### Performance
```php
// Caching example
public function getCachedData(string $key): array
{
    $cache = \Config\Services::cache();
    $data = $cache->get($key);

    if ($data === null) {
        $data = $this->expensiveQuery();
        $cache->save($key, $data, 300); // 5 minutes
    }

    return $data;
}

// Batch operations
public function createBatch(array $dataArray): bool
{
    return $this->model->insertBatch($dataArray);
}

public function updateBatch(array $dataArray, string $key): bool
{
    return $this->model->updateBatch($dataArray, $key);
}
```

### Error Handling
```php
public function safeOperation(array $data): mixed
{
    try {
        return $this->model->insert($data);
    } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
        log_message('error', 'Database error: ' . $e->getMessage());
        return false;
    }
}
```

---

*Documentación actualizada: Septiembre 2025*