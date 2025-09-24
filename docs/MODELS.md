# Models - Capa de Datos y Entidades

## Arquitectura de Modelos

Los modelos en JamWithJamie implementan el patrón Active Record de CodeIgniter 4, proporcionando una interfaz elegante para interactuar con las tablas de la base de datos. Cada modelo está vinculado a una Entity correspondiente para tipado fuerte y manipulación de datos.

### Principios de Diseño

1. **UUID Primary Keys**: Todos los modelos usan UUIDs como claves primarias
2. **Soft Deletes**: Eliminación lógica habilitada donde es apropiado
3. **Entity Mapping**: Cada modelo está vinculado a una Entity específica
4. **Type Safety**: Uso de entities para tipado fuerte
5. **Timestamp Management**: Manejo automático de created_at/updated_at

---

## 🎪 ReservationModel

**Archivo**: `app/Models/ReservationModel.php`
**Entity**: `App\Entities\Reservation`
**Tabla**: `reservations`

### Configuración Específica

- **UUID**: Auto-generado en beforeInsert
- **Soft Deletes**: Habilitado
- **Return Type**: `Reservation::class`
- **Auto Increment**: Deshabilitado (UUIDs)

### Campos Permitidos

```php
protected $allowedFields = [
    'id',                          // UUID manual
    'customer_id',                 // FK a customers
    'service_id',                  // FK a services
    'zipcode_id',                  // FK a zipcodes
    'service_price_id',            // FK a service_prices
    'event_address',               // Dirección del evento
    'event_date',                  // Fecha del evento
    'event_time',                  // Hora del evento
    'children_count',              // Cantidad de niños
    'arrival_parking_instructions', // Instrucciones de llegada
    'entertainment_start_time',     // Hora inicio entretenimiento
    'birthday_child_name',          // Nombre del cumpleañero
    'birthday_child_age',           // Edad del cumpleañero
    'children_age_range',           // Rango de edades
    'song_requests',                // Solicitudes de canciones
    'sing_happy_birthday',          // ¿Cantar cumpleaños?
    'expedition_fee',               // Tarifa de expedición
    'extra_children_fee',           // Tarifa niños extra
    'performers_count',             // Cantidad de performers
    'duration_hours',               // Duración en horas
    'price_type',                   // Tipo de precio (standard/jukebox)
    'base_price',                   // Precio base del servicio
    'addons_total',                 // Total de addons
    'total_amount',                 // Monto total final
    'status',                       // Estado (new/confirmed/cancelled)
    'is_invoiced',                  // ¿Facturado?
    'is_paid',                      // ¿Pagado?
    'customer_notes',               // Notas del cliente
    'internal_notes',               // Notas internas
    'created_at',
    'updated_at',
    'deleted_at'
];
```

### Estados de Reserva

- **new**: Reserva recién creada
- **under_review**: En revisión
- **confirmed**: Confirmada
- **cancelled**: Cancelada

---

## 👤 CustomerModel

**Archivo**: `app/Models/CustomerModel.php`
**Entity**: `App\Entities\Customer`
**Tabla**: `customers`

### Configuración Específica

- **Soft Deletes**: Deshabilitado (clientes se mantienen)
- **Return Type**: `Customer::class`

### Campos Permitidos

```php
protected $allowedFields = [
    'email',            // Email único del cliente
    'phone',            // Teléfono de contacto
    'full_name',        // Nombre completo
    'billing_address',  // Dirección de facturación
    'segment',          // Segmento de cliente
];
```

### Características

- Email debe ser único en el sistema
- Usado para crear o encontrar clientes en reservas
- Vinculado con múltiples reservas (uno a muchos)

---

## 🎭 ServiceModel

**Archivo**: `app/Models/ServiceModel.php`
**Entity**: `App\Entities\Service`
**Tabla**: `services`

### Configuración Específica

- **Soft Deletes**: Habilitado
- **Return Type**: `Service::class`

### Campos Permitidos

```php
protected $allowedFields = [
    'name',         // Nombre del servicio
    'description',  // Descripción detallada
    'is_active'     // Estado activo/inactivo
];
```

### Relaciones

- **Uno a muchos**: ServicePrices (precios por condado)
- **Uno a muchos**: Reservations (reservas del servicio)

---

## 💲 ServicePriceModel

**Archivo**: `app/Models/ServicePriceModel.php`
**Entity**: `App\Entities\ServicePrice`
**Tabla**: `service_prices`

### Configuración Específica

- **Soft Deletes**: Habilitado
- **Return Type**: `ServicePrice::class`

### Campos Permitidos

```php
protected $allowedFields = [
    'service_id',           // FK a services
    'county_id',           // FK a counties
    'img',                 // Imagen del servicio
    'performers_count',    // Cantidad de performers
    'amount',              // Precio base
    'min_duration_hours',  // Duración mínima
    'is_available',        // Disponibilidad
    'notes',               // Notas adicionales
    'extra_child_fee',     // Tarifa por niño extra
    'range_age',           // Rango de edad aplicable
];
```

### Características

- Define precios específicos por condado
- Incluye imagen asociada al servicio
- Configuración de tarifas adicionales

---

## ➕ AddonModel

**Archivo**: `app/Models/AddonModel.php`
**Entity**: `App\Entities\Addon`
**Tabla**: `addons`

### Configuración Específica

- **Soft Deletes**: Deshabilitado
- **Return Type**: `Addon::class`

### Campos Permitidos

```php
protected $allowedFields = [
    'name',                         // Nombre del addon
    'description',                  // Descripción
    'base_price',                   // Precio base
    'is_active',                    // Estado activo
    'estimated_duration_minutes',   // Duración estimada
    'image',                        // Imagen del addon
    'price_type'                    // Tipo (standard/jukebox)
];
```

### Tipos de Addon

- **standard**: Addon regular
- **jukebox**: Addon de música (afecta pricing type)

---

## 🔗 ReservationAddonModel

**Archivo**: `app/Models/ReservationAddonModel.php`
**Entity**: `App\Entities\ReservationAddon`
**Tabla**: `reservation_addons`

### Configuración Específica

- **Soft Deletes**: Deshabilitado
- **Return Type**: `ReservationAddon::class`
- **Updated Field**: Vacío (sin updated_at)

### Campos Permitidos

```php
protected $allowedFields = [
    'reservation_id',   // FK a reservations
    'addon_id',        // FK a addons
    'quantity',        // Cantidad seleccionada
    'price_at_time'    // Precio al momento de la reserva
];
```

### Características Especiales

```php
protected $updatedField = '';  // Sin columna updated_at
```

Esta configuración evita errores al no tener columna `updated_at` en la tabla.

---

## 🗺️ Modelos de Geografía

### CountyModel

**Entity**: `App\Entities\County`
**Tabla**: `counties`

```php
protected $allowedFields = [
    'metropolitan_area_id',  // FK a metropolitan_areas
    'name',                  // Nombre del condado
    'is_active'             // Estado activo
];
```

### CityModel

**Entity**: `App\Entities\City`
**Tabla**: `cities`

```php
protected $allowedFields = [
    'county_id',    // FK a counties
    'name',         // Nombre de la ciudad
    'is_active'     // Estado activo
];
```

### ZipCodeModel

**Entity**: `App\Entities\Zipcode`
**Tabla**: `zipcodes`

```php
protected $allowedFields = [
    'city_id',      // FK a cities
    'zipcode',      // Código postal
    'is_active'     // Estado activo
];
```

### MetropolitanAreaModel

**Entity**: `App\Entities\MetropolitanArea`
**Tabla**: `metropolitan_areas`

```php
protected $allowedFields = [
    'name',         // Nombre del área metropolitana
    'is_active'     // Estado activo
];
```

---

## 🔐 Modelos de Sistema

### UserModel

**Entity**: `App\Entities\User`
**Tabla**: `users`

```php
protected $allowedFields = [
    'first_name',   // Primer nombre
    'last_name',    // Apellido
    'email',        // Email de login
    'password',     // Password hasheada
    'image',        // Avatar del usuario
    'state',        // Estado del usuario
    'role_id',      // FK a roles
    'is_active'     // Usuario activo
];
```

### RoleModel

**Entity**: `App\Entities\Role`
**Tabla**: `roles`

```php
protected $allowedFields = [
    'name',         // Nombre del rol
    'description',  // Descripción
    'is_active'     // Rol activo
];
```

---

## 🔧 Modelos de Configuración

### ChildrenAgeRangeModel

**Entity**: `App\Entities\ChildrenAgeRange`
**Tabla**: `children_age_ranges`

```php
protected $allowedFields = [
    'service_price_id', // FK a service_prices
    'min_age',          // Edad mínima
    'max_age',          // Edad máxima
    'description',      // Descripción del rango
    'is_active'         // Estado activo
];
```

### DurationModel

**Entity**: `App\Entities\Duration`
**Tabla**: `durations`

```php
protected $allowedFields = [
    'service_price_id', // FK a service_prices
    'minutes',          // Duración en minutos
    'label',            // Etiqueta descriptiva
    'is_active'         // Estado activo
];
```

---

## 📊 Entities - Tipado de Datos

### Reservation Entity

```php
class Reservation extends Entity
{
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'event_date',
    ];

    protected $casts = [
        'id' => 'string',
        'children_count' => 'integer',
        'base_price' => 'float',
        'addons_total' => 'float',
        'total_amount' => 'float',
        'is_invoiced' => 'boolean',
        'is_paid' => 'boolean',
        // ... más campos
    ];
}
```

### Customer Entity

```php
class Customer extends Entity
{
    protected $dates = [
        'created_at',
        'updated_at',
        'last_reservation_date'
    ];

    protected $casts = [
        'id' => 'string',
        'email' => 'string',
        'phone' => 'string',
        'full_name' => 'string',
        'billing_address' => 'string',
        'segment' => 'string',
    ];
}
```

### Service Entity

```php
class Service extends Entity
{
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'id' => 'string',
        'name' => 'string',
        'description' => 'string',
        'is_active' => 'boolean'
    ];
}
```

---

## 📝 Patrones de Implementación

### Estructura Base del Modelo

```php
class ExampleModel extends Model
{
    // Configuración de tabla
    protected $table            = 'table_name';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = EntityName::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    // Campos permitidos para mass assignment
    protected $allowedFields    = [
        'field1', 'field2', 'field3'
    ];

    // Configuración de timestamps
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['generateUUID'];

    protected function generateUUID(array $data)
    {
        return generate_uuid_data($data);
    }
}
```

### Callback generateUUID

Todos los modelos implementan el helper `generate_uuid_data()`:

```php
protected function generateUUID(array $data)
{
    return generate_uuid_data($data);
}
```

Este callback se ejecuta antes de insertar y garantiza que cada registro tenga un UUID único como clave primaria.

---

## 🔍 Relaciones de Datos

### Esquema de Relaciones Principales

```
MetropolitanAreas (1:N) Counties (1:N) Cities (1:N) ZipCodes
                                                        ↓
Services (1:N) ServicePrices ←→ Counties           ZipCodes
    ↓                                                   ↓
ServicePrices (1:N) Reservations ←→ Customers     Reservations
    ↓
ServicePrices (1:N) ChildrenAgeRanges
ServicePrices (1:N) Durations

Reservations (N:M) Addons → ReservationAddons

Users ←→ Roles
Roles (N:M) Menus → RoleMenuPermissions
```

### Claves Foráneas Importantes

- `reservations.customer_id` → `customers.id`
- `reservations.service_price_id` → `service_prices.id`
- `reservations.zipcode_id` → `zipcodes.id`
- `reservation_addons.reservation_id` → `reservations.id`
- `reservation_addons.addon_id` → `addons.id`

---

## 🚀 Características Avanzadas

### UUID Generation

Todos los modelos usan UUIDs como primary keys:

```php
// En beforeInsert callback
protected function generateUUID(array $data)
{
    return generate_uuid_data($data);
}
```

### Soft Deletes

Modelos con eliminación lógica:
- ReservationModel
- ServiceModel
- ServicePriceModel
- CountyModel
- CityModel
- UserModel

### Type Casting

Las entities proporcionan casting automático:

```php
protected $casts = [
    'id' => 'string',           // UUID siempre string
    'amount' => 'float',        // Montos decimales
    'count' => 'integer',       // Cantidades enteras
    'is_active' => 'boolean',   // Estados booleanos
];
```

### Date Handling

Campos de fecha se auto-convierten:

```php
protected $dates = [
    'created_at',
    'updated_at',
    'deleted_at',
    'event_date',
];
```

---

## 🛡️ Seguridad y Validación

### Mass Assignment Protection

```php
protected $protectFields = true;
protected $allowedFields = [
    // Solo campos explícitamente listados
];
```

### Timestamp Consistency

```php
protected $useTimestamps = true;
protected $dateFormat    = 'datetime';
```

### Soft Delete Safety

```php
protected $useSoftDeletes = true;
protected $deletedField  = 'deleted_at';
```

---

## 🔧 Configuraciones Especiales

### ReservationAddonModel

```php
// Sin columna updated_at
protected $updatedField = '';
```

### CustomerModel

```php
// No usa soft deletes (conservar histórico)
protected $useSoftDeletes = false;
```

### AddonModel

```php
// No usa soft deletes (mantener addons)
protected $useSoftDeletes = false;
```

---

*Documentación actualizada: Septiembre 2025*