# Services - Capa de Lógica de Negocio

## Arquitectura de Servicios

Los servicios en JamWithJamie contienen toda la lógica de negocio de la aplicación. Actúan como intermediarios entre los controladores y los repositorios, implementando reglas de negocio, validaciones y cálculos complejos.

### Principios de Diseño

1. **Single Responsibility**: Cada servicio maneja un dominio específico
2. **Dependency Injection**: Servicios inyectan repositorios necesarios
3. **Transaction Management**: Operaciones complejas son transaccionales
4. **Exception Handling**: Errores se convierten en HTTPException
5. **Business Logic**: Toda la lógica de negocio reside aquí

---

## 🎪 ReservationService

**Archivo**: `app/Services/ReservationService.php`
**Propósito**: Gestión completa del ciclo de vida de reservas

### Funcionalidades Principales

#### Creación de Reservas Multi-Step
Procesa datos del formulario de 8 pasos:

```php
public function createFromForm(array $formData): array
```

**Proceso Completo**:
1. Valida datos requeridos y formatos
2. Inicia transacción de base de datos
3. Crea o encuentra cliente por email
4. Calcula precios detallados
5. Crea reserva principal
6. Guarda relaciones de addons
7. Confirma transacción

**Validaciones Aplicadas**:
- Datos requeridos presentes
- Fecha del evento futura y válida
- Al menos 1 niño requerido
- Precio de servicio válido (> 0)
- Addons con IDs y precios válidos
- Duración requerida

**Cálculos Automáticos**:
```php
$extraChildren = max(0, $selectedKids - $serviceIncludedKids);
$addonsTotal = array_reduce($addons, function($sum, $addon) {
    return $sum + ($addon['base_price'] * $addon['quantity']);
});
$baseTotal = $servicePrice + $addonsTotal + $extraChildrenTotal;

// Recargos por fecha
if ($diffDays < 2) {
    $surchargeAmount = $baseTotal * 0.2; // 20%
} elseif ($diffDays <= 7) {
    $surchargeAmount = $baseTotal * 0.1; // 10%
}
```

### Métodos Principales

#### `getAll(): array`
Lista todas las reservas con información completa.

#### `getById(string $id): mixed`
Obtiene reserva específica con validación de existencia.

#### `create(array $data): mixed`
Método legacy para crear reservas (deprecated).

#### `update(string $id, array $data): bool`
Actualiza reserva existente con validaciones.

#### `delete(string $id): bool`
Eliminación lógica de reserva.

#### `createOrFindCustomer(array $customerData, array $information): string`
Crea cliente nuevo o encuentra existente por email.

#### `determinePriceType(array $addons): string`
Determina tipo de precio ('jukebox' vs 'standard').

---

## 📊 DashboardService

**Archivo**: `app/Services/DashboardService.php`
**Propósito**: Métricas y análisis del negocio

### Métricas Implementadas

#### `getReservationsByStatus(): array`
Distribución de reservas por estado con porcentajes.

```php
return [
    'data' => [
        [
            'status' => 'new',
            'label' => 'Nueva',
            'count' => 45,
            'percentage' => 60.0,
            'color' => '#17a2b8'
        ]
    ],
    'total' => 75
];
```

#### `getReservationsStatusEvolution(): array`
Evolución temporal de estados (últimos 6 meses).

#### `getPaymentStatus(): array`
Métricas de pagos: pagadas vs pendientes.

#### `getInvoiceStatus(): array`
Estado de facturación del negocio.

#### `getMostPopularJamTypes(int $limit = 10): array`
Top servicios por reservas e ingresos.

#### `getCitiesWithMostEvents(int $limit = 10): array`
Ciudades con más eventos organizados.

#### `getMostPopularAddons(int $limit = 10): array`
Addons más vendidos con estadísticas detalladas.

---

## 👤 CustomerService

**Archivo**: `app/Services/CustomerService.php`
**Propósito**: Gestión de clientes (CRM)

### Funcionalidades

#### `getAll(): array`
Lista completa de clientes.

#### `getById(string $id): mixed`
Cliente específico con validación.

#### `searchByName(string $name): array`
Búsqueda de clientes por nombre.

#### `create(array $data): string`
Crear nuevo cliente con validaciones.

#### `update(string $id, array $data): bool`
Actualizar información del cliente.

#### `delete(string $id): bool`
Eliminación lógica del cliente.

### Validaciones de Cliente
- Email único y formato válido
- Teléfono en formato correcto
- Nombres requeridos
- Datos de contacto completos

---

## 🎭 ServiceService

**Archivo**: `app/Services/ServiceService.php`
**Propósito**: Gestión del catálogo de servicios

### Funcionalidades

#### `getAll(): array`
Catálogo completo de servicios.

#### `getAllActive(): array`
Solo servicios activos disponibles.

#### `getById(string $id): mixed`
Servicio específico con detalles.

#### `create(array $data): string`
Nuevo servicio con validaciones.

#### `update(string $id, array $data): bool`
Actualización de servicio.

#### `delete(string $id): bool`
Eliminación lógica.

---

## 💲 ServicePriceService

**Archivo**: `app/Services/ServicePriceService.php`
**Propósito**: Gestión de precios por ubicación

### Funcionalidades

#### `getAll(): array`
Todos los precios de servicios.

#### `getAllByCounty(string $countyId): array`
Precios disponibles por condado.

#### `getById(string $id): mixed`
Precio específico con detalles.

#### `getByServiceAndCounty(string $serviceId, string $countyId): mixed`
Precio específico por servicio y condado.

#### Gestión con Imágenes
- `createWithImage()`: Crear con upload de imagen
- `updateWithImage()`: Actualizar con nueva imagen
- Validación de formatos de imagen
- Optimización automática de imágenes

---

## ➕ AddonService

**Archivo**: `app/Services/AddonService.php`
**Propósito**: Gestión de servicios adicionales

### Funcionalidades

#### `getAll(): array`
Catálogo completo de addons.

#### `getAllActive(): array`
Addons disponibles para selección.

#### `search(string $name): array`
Búsqueda de addons por nombre.

#### `getById(string $id): mixed`
Addon específico con detalles.

#### Gestión con Imágenes
Similar a ServicePriceService, incluye:
- Upload y validación de imágenes
- Redimensionamiento automático
- Gestión de formatos múltiples
- Limpieza de archivos antiguos

### Tipos de Addon
- **Standard**: Addon regular
- **Jukebox**: Addon de música (afecta pricing)

---

## 🔐 AuthService

**Archivo**: `app/Services/AuthService.php`
**Propósito**: Autenticación y autorización

### Funcionalidades

#### JWT Token Management
```php
public function generateToken(array $userData): string
public function validateToken(string $token): bool
public function refreshToken(string $token): string
```

#### User Authentication
```php
public function login(string $email, string $password): array
public function logout(string $token): bool
```

#### Role-Based Access
```php
public function hasPermission(string $userId, string $permission): bool
public function getUserRoles(string $userId): array
```

---

## 📧 BrevoEmailService

**Archivo**: `app/Services/BrevoEmailService.php`
**Propósito**: Integración con Brevo para emails

### Funcionalidades

#### Email Templates
```php
public function sendReservationConfirmation(array $reservationData): bool
public function sendPaymentReminder(array $customerData): bool
public function sendEventReminder(array $reservationData): bool
```

#### Email Campaigns
```php
public function addToContactList(string $email, array $attributes): bool
public function removeFromContactList(string $email): bool
```

---

## 🗺️ Servicios de Geografía

### CountyService
Gestión de condados y áreas metropolitanas.

### CityService
Gestión de ciudades por condado.

### ZipCodeService
Validación y gestión de códigos postales.

### MetropolitanAreaService
Gestión de áreas metropolitanas.

**Funcionalidades Comunes**:
- CRUD completo
- Validaciones geográficas
- Relaciones jerárquicas
- Búsqueda por nombre/código
- Estados activo/inactivo

---

## 🔧 Servicios de Configuración

### UserService
Gestión de usuarios del sistema administrativo.

### RoleService
Gestión de roles y permisos granulares.

### ChildrenAgeRangeService
Configuración de rangos de edad por servicio.

### DurationService
Gestión de duraciones disponibles por servicio.

### ReservationAddonService
Gestión de relaciones reserva-addon.

---

## 📝 Patrones de Implementación

### Estructura Común
```php
class ExampleService
{
    protected $repository;

    public function __construct()
    {
        $this->repository = new ExampleRepository();
    }

    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    public function getById(string $id): mixed
    {
        $item = $this->repository->getById($id);

        if (!$item) {
            throw new HTTPException('Item not found', Response::HTTP_NOT_FOUND);
        }

        return $item;
    }

    public function create(array $data): string
    {
        // Validaciones específicas del negocio
        $this->validateData($data);

        // Crear elemento
        $id = $this->repository->create($data);

        if (!$id) {
            throw new HTTPException('Creation failed', Response::HTTP_BAD_REQUEST);
        }

        return $id;
    }
}
```

### Manejo de Transacciones
```php
public function complexOperation(array $data): array
{
    $db = \Config\Database::connect();
    $db->transStart();

    try {
        // Operaciones múltiples
        $result1 = $this->repository->operation1($data);
        $result2 = $this->repository->operation2($data);

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new HTTPException('Transaction failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return ['result1' => $result1, 'result2' => $result2];

    } catch (\Throwable $e) {
        $db->transRollback();
        throw $e;
    }
}
```

### Validaciones de Negocio
```php
private function validateReservationData(array $data): void
{
    // Fecha futura
    if (isset($data['eventDate'])) {
        $eventDate = new \DateTime($data['eventDate']);
        if ($eventDate < new \DateTime('today')) {
            throw new HTTPException('Event date cannot be in the past', Response::HTTP_BAD_REQUEST);
        }
    }

    // Niños mínimos
    if (isset($data['selectedKids']) && $data['selectedKids'] < 1) {
        throw new HTTPException('At least one child is required', Response::HTTP_BAD_REQUEST);
    }
}
```

---

## 🚀 Mejores Prácticas

1. **Separation of Concerns**: Cada servicio tiene una responsabilidad clara
2. **Exception Handling**: Todos los errores se convierten en HTTPException
3. **Input Validation**: Validación exhaustiva en cada método público
4. **Transaction Safety**: Operaciones complejas son atómicas
5. **Code Reuse**: Métodos comunes se extraen a traits o helpers
6. **Documentation**: Cada método público está documentado
7. **Testing**: Servicios son fácilmente testeable con mocks
8. **Performance**: Consultas optimizadas y caching cuando apropiado

---

*Documentación actualizada: Septiembre 2025*