# Base de Datos - Esquema y Estructura

## Arquitectura de Base de Datos

JamWithJamie utiliza MySQL 8.0+ con un diseño relacional optimizado para gestión de reservas de entretenimiento infantil. La base de datos implementa UUIDs como claves primarias, soft deletes y timestamps automáticos.

### Principios de Diseño

1. **UUID Primary Keys**: Claves primarias no secuenciales para seguridad
2. **Soft Deletes**: Eliminación lógica para preservar historial
3. **Referential Integrity**: Claves foráneas y constraints
4. **Normalized Structure**: Diseño normalizado hasta 3FN
5. **Geographic Hierarchy**: Estructura jerárquica de ubicaciones
6. **Audit Trail**: Timestamps automáticos para auditoría

---

## 📊 Esquema de Relaciones

```
MetropolitanAreas (1:N) Counties (1:N) Cities (1:N) ZipCodes
                                  ↓                    ↓
Services (1:N) ServicePrices ←→ Counties          ZipCodes
    ↓                                                ↓
ServicePrices:                                  Reservations
    ├─ (1:N) ChildrenAgeRanges                      ↑
    ├─ (1:N) Durations                              │
    └─ (1:N) Reservations                           │
                                                    │
Customers (1:N) ─────────────────────────────────────┘
                                                    │
Reservations (N:M) Addons ────→ ReservationAddons ──┘

Users ←→ Roles
Roles (N:M) Menus ────→ RoleMenuPermissions
```

---

## 🎪 Tablas Principales

### reservations
**Propósito**: Tabla central del sistema - almacena todas las reservas

```sql
CREATE TABLE reservations (
    id VARCHAR(36) PRIMARY KEY,
    customer_id VARCHAR(36) NOT NULL,
    service_id VARCHAR(36),
    zipcode_id VARCHAR(36) NOT NULL,
    service_price_id VARCHAR(36) NOT NULL,

    -- Event Details
    event_address TEXT NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    children_count INT NOT NULL DEFAULT 1,

    -- Event Configuration
    arrival_parking_instructions TEXT,
    entertainment_start_time TIME,
    birthday_child_name VARCHAR(100),
    birthday_child_age INT,
    children_age_range VARCHAR(50),
    song_requests TEXT,
    sing_happy_birthday BOOLEAN DEFAULT FALSE,

    -- Pricing
    expedition_fee DECIMAL(10,2) DEFAULT 0.00,
    extra_children_fee DECIMAL(10,2) DEFAULT 0.00,
    performers_count INT DEFAULT 1,
    duration_hours DECIMAL(3,2) NOT NULL,
    price_type ENUM('standard', 'jukebox') DEFAULT 'standard',
    base_price DECIMAL(10,2) NOT NULL,
    addons_total DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,

    -- Status
    status ENUM('new', 'under_review', 'confirmed', 'cancelled') DEFAULT 'new',
    is_invoiced BOOLEAN DEFAULT FALSE,
    is_paid BOOLEAN DEFAULT FALSE,

    -- Notes
    customer_notes TEXT,
    internal_notes TEXT,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Foreign Keys
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (service_id) REFERENCES services(id),
    FOREIGN KEY (zipcode_id) REFERENCES zipcodes(id),
    FOREIGN KEY (service_price_id) REFERENCES service_prices(id),

    -- Indexes
    INDEX idx_customer_id (customer_id),
    INDEX idx_service_price_id (service_price_id),
    INDEX idx_event_date (event_date),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_deleted_at (deleted_at)
);
```

**Estados de Reserva**:
- `new`: Reserva recién creada
- `under_review`: En proceso de revisión
- `confirmed`: Confirmada y lista
- `cancelled`: Cancelada por cualquier motivo

### customers
**Propósito**: Información de clientes del sistema

```sql
CREATE TABLE customers (
    id VARCHAR(36) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    full_name VARCHAR(255) NOT NULL,
    billing_address TEXT,
    segment VARCHAR(50),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_full_name (full_name)
);
```

### services
**Propósito**: Catálogo de servicios de entretenimiento

```sql
CREATE TABLE services (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    INDEX idx_name (name),
    INDEX idx_is_active (is_active)
);
```

### service_prices
**Propósito**: Precios de servicios por condado específico

```sql
CREATE TABLE service_prices (
    id VARCHAR(36) PRIMARY KEY,
    service_id VARCHAR(36) NOT NULL,
    county_id VARCHAR(36) NOT NULL,
    img VARCHAR(255),
    performers_count INT DEFAULT 1,
    amount DECIMAL(10,2) NOT NULL,
    min_duration_hours DECIMAL(3,2) DEFAULT 1.0,
    is_available BOOLEAN DEFAULT TRUE,
    notes TEXT,
    extra_child_fee DECIMAL(10,2) DEFAULT 0.00,
    range_age VARCHAR(50),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (service_id) REFERENCES services(id),
    FOREIGN KEY (county_id) REFERENCES counties(id),

    INDEX idx_service_id (service_id),
    INDEX idx_county_id (county_id),
    INDEX idx_is_available (is_available),

    UNIQUE KEY unique_service_county (service_id, county_id, deleted_at)
);
```

### addons
**Propósito**: Servicios adicionales disponibles

```sql
CREATE TABLE addons (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    base_price DECIMAL(10,2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    estimated_duration_minutes INT,
    image VARCHAR(255),
    price_type ENUM('standard', 'jukebox') DEFAULT 'standard',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_name (name),
    INDEX idx_is_active (is_active),
    INDEX idx_price_type (price_type)
);
```

### reservation_addons
**Propósito**: Tabla de relación N:M entre reservas y addons

```sql
CREATE TABLE reservation_addons (
    id VARCHAR(36) PRIMARY KEY,
    reservation_id VARCHAR(36) NOT NULL,
    addon_id VARCHAR(36) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price_at_time DECIMAL(10,2) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Nota: NO tiene updated_at por diseño

    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (addon_id) REFERENCES addons(id),

    INDEX idx_reservation_id (reservation_id),
    INDEX idx_addon_id (addon_id)
);
```

**Características Especiales**:
- `price_at_time`: Precio del addon al momento de la reserva (histórico)
- `quantity`: Cantidad seleccionada del addon
- No tiene `updated_at` para preservar registro histórico

---

## 🗺️ Estructura Geográfica

### metropolitan_areas
```sql
CREATE TABLE metropolitan_areas (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

### counties
```sql
CREATE TABLE counties (
    id VARCHAR(36) PRIMARY KEY,
    metropolitan_area_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (metropolitan_area_id) REFERENCES metropolitan_areas(id),
    INDEX idx_metropolitan_area_id (metropolitan_area_id)
);
```

### cities
```sql
CREATE TABLE cities (
    id VARCHAR(36) PRIMARY KEY,
    county_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (county_id) REFERENCES counties(id),
    INDEX idx_county_id (county_id)
);
```

### zipcodes
```sql
CREATE TABLE zipcodes (
    id VARCHAR(36) PRIMARY KEY,
    city_id VARCHAR(36) NOT NULL,
    zipcode VARCHAR(10) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (city_id) REFERENCES cities(id),
    INDEX idx_city_id (city_id),
    INDEX idx_zipcode (zipcode),

    UNIQUE KEY unique_city_zipcode (city_id, zipcode)
);
```

---

## 🔧 Tablas de Configuración

### children_age_ranges
**Propósito**: Rangos de edad configurables por servicio

```sql
CREATE TABLE children_age_ranges (
    id VARCHAR(36) PRIMARY KEY,
    service_price_id VARCHAR(36) NOT NULL,
    min_age INT NOT NULL,
    max_age INT NOT NULL,
    description VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (service_price_id) REFERENCES service_prices(id) ON DELETE CASCADE,
    INDEX idx_service_price_id (service_price_id)
);
```

### durations
**Propósito**: Duraciones disponibles por servicio

```sql
CREATE TABLE durations (
    id VARCHAR(36) PRIMARY KEY,
    service_price_id VARCHAR(36) NOT NULL,
    minutes INT NOT NULL,
    label VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (service_price_id) REFERENCES service_prices(id) ON DELETE CASCADE,
    INDEX idx_service_price_id (service_price_id)
);
```

---

## 🔐 Sistema de Usuarios y Permisos

### users
```sql
CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    image VARCHAR(255),
    state VARCHAR(50),
    role_id VARCHAR(36) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (role_id) REFERENCES roles(id),
    INDEX idx_email (email),
    INDEX idx_role_id (role_id)
);
```

### roles
```sql
CREATE TABLE roles (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

### menus
```sql
CREATE TABLE menus (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    url VARCHAR(255),
    icon VARCHAR(100),
    parent_id VARCHAR(36),
    order_index INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (parent_id) REFERENCES menus(id),
    INDEX idx_parent_id (parent_id)
);
```

### role_menu_permissions
**Propósito**: Permisos específicos por rol y menú

```sql
CREATE TABLE role_menu_permissions (
    id VARCHAR(36) PRIMARY KEY,
    role_id VARCHAR(36) NOT NULL,
    menu_id VARCHAR(36) NOT NULL,
    can_view BOOLEAN DEFAULT FALSE,
    can_create BOOLEAN DEFAULT FALSE,
    can_edit BOOLEAN DEFAULT FALSE,
    can_delete BOOLEAN DEFAULT FALSE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,

    UNIQUE KEY unique_role_menu (role_id, menu_id)
);
```

---

## 📊 Consultas Optimizadas

### Reservas con Información Completa
```sql
SELECT
    r.*,
    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
    c.email as customer_email,
    c.phone as customer_phone,
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
ORDER BY r.created_at DESC;
```

### Addons Más Populares
```sql
SELECT
    a.name as addon_name,
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
LIMIT 10;
```

### Ingresos por Período
```sql
SELECT
    DATE_FORMAT(r.event_date, '%Y-%m') as period,
    COUNT(*) as reservations_count,
    SUM(r.total_amount) as total_revenue,
    AVG(r.total_amount) as avg_revenue
FROM reservations r
WHERE r.deleted_at IS NULL
    AND r.status IN ('confirmed', 'completed')
    AND r.event_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(r.event_date, '%Y-%m')
ORDER BY period DESC;
```

---

## 🔍 Índices y Performance

### Índices Principales

```sql
-- Reservations
CREATE INDEX idx_reservations_customer_date ON reservations(customer_id, event_date);
CREATE INDEX idx_reservations_status_date ON reservations(status, event_date);
CREATE INDEX idx_reservations_service_price ON reservations(service_price_id, created_at);

-- Customers
CREATE INDEX idx_customers_email_phone ON customers(email, phone);
CREATE INDEX idx_customers_name_segment ON customers(full_name, segment);

-- Service Prices
CREATE INDEX idx_service_prices_county_active ON service_prices(county_id, is_available);

-- Geographic
CREATE INDEX idx_zipcodes_city_code ON zipcodes(city_id, zipcode);
CREATE INDEX idx_cities_county_active ON cities(county_id, is_active);

-- Reservation Addons
CREATE INDEX idx_reservation_addons_lookup ON reservation_addons(reservation_id, addon_id);
```

### Query Performance Tips

1. **Usar LIMIT** en consultas grandes
2. **Filtrar por deleted_at IS NULL** para soft deletes
3. **Índices compuestos** para consultas frecuentes
4. **EXPLAIN** para analizar planes de ejecución

---

## 🛡️ Constraints y Validaciones

### Check Constraints

```sql
-- Reservations
ALTER TABLE reservations ADD CONSTRAINT chk_children_count
    CHECK (children_count >= 1 AND children_count <= 50);

ALTER TABLE reservations ADD CONSTRAINT chk_duration_hours
    CHECK (duration_hours >= 0.5 AND duration_hours <= 8.0);

ALTER TABLE reservations ADD CONSTRAINT chk_event_date
    CHECK (event_date >= CURDATE());

-- Service Prices
ALTER TABLE service_prices ADD CONSTRAINT chk_amount
    CHECK (amount >= 0);

ALTER TABLE service_prices ADD CONSTRAINT chk_min_duration
    CHECK (min_duration_hours >= 0.5);

-- Addons
ALTER TABLE addons ADD CONSTRAINT chk_base_price
    CHECK (base_price >= 0);

-- Age Ranges
ALTER TABLE children_age_ranges ADD CONSTRAINT chk_age_range
    CHECK (min_age >= 0 AND max_age >= min_age AND max_age <= 18);
```

### Triggers

```sql
-- Trigger para actualizar timestamps
DELIMITER //
CREATE TRIGGER reservations_updated_at
    BEFORE UPDATE ON reservations
    FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END//
DELIMITER ;

-- Trigger para validar fechas de eventos
DELIMITER //
CREATE TRIGGER reservations_validate_date
    BEFORE INSERT ON reservations
    FOR EACH ROW
BEGIN
    IF NEW.event_date < CURDATE() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Event date cannot be in the past';
    END IF;
END//
DELIMITER ;
```

---

## 🗄️ Migraciones y Seeders

### Estructura de Migración

```php
// database/migrations/2024_01_01_000001_create_reservations_table.php
<?php

use CodeIgniter\Database\Migration;

class CreateReservationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'VARCHAR',
                'constraint' => 36,
            ],
            'customer_id' => [
                'type' => 'VARCHAR',
                'constraint' => 36,
            ],
            // ... más campos
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('customer_id', 'customers', 'id');
        $this->forge->createTable('reservations');
    }

    public function down()
    {
        $this->forge->dropTable('reservations');
    }
}
```

### Seeders de Datos

```php
// database/seeds/ServiceSeeder.php
<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run()
    {
        $services = [
            [
                'id' => $this->generateUUID(),
                'name' => 'Birthday Party Basic',
                'description' => 'Paquete básico de fiesta de cumpleaños',
                'is_active' => 1
            ],
            [
                'id' => $this->generateUUID(),
                'name' => 'Birthday Party Premium',
                'description' => 'Paquete premium con entretenimiento completo',
                'is_active' => 1
            ]
        ];

        $this->db->table('services')->insertBatch($services);
    }
}
```

---

## 📈 Backup y Mantenimiento

### Backup Strategy

```bash
# Backup completo
mysqldump -u username -p jamwithjamie_db > backup_$(date +%Y%m%d).sql

# Backup solo estructura
mysqldump -u username -p --no-data jamwithjamie_db > structure_$(date +%Y%m%d).sql

# Backup incremental (usando binlogs)
mysqldump -u username -p --single-transaction --master-data jamwithjamie_db > incremental_$(date +%Y%m%d).sql
```

### Mantenimiento Regular

```sql
-- Optimización de tablas
OPTIMIZE TABLE reservations, customers, service_prices;

-- Análisis de índices
ANALYZE TABLE reservations, customers;

-- Limpieza de datos antiguos (soft deletes > 2 años)
DELETE FROM reservations
WHERE deleted_at IS NOT NULL
    AND deleted_at < DATE_SUB(NOW(), INTERVAL 2 YEAR);
```

---

## 🔧 Variables de Configuración

### MySQL Configuration

```ini
# my.cnf
[mysqld]
innodb_buffer_pool_size = 2G
innodb_log_file_size = 512M
innodb_flush_log_at_trx_commit = 1
max_connections = 200

# Query cache
query_cache_type = 1
query_cache_size = 128M

# Character set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
```

---

*Documentación actualizada: Septiembre 2025*