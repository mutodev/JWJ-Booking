# API Documentation - REST Endpoints

## API Overview

JamWithJamie expone una API RESTful completa construida con CodeIgniter 4. Proporciona endpoints para gestión de reservas, servicios, clientes, geografía y métricas administrativas.

### Base URL
```
http://localhost:8080/api
```

### Características Principales

- **RESTful Design**: Endpoints siguiendo convenciones REST
- **JWT Authentication**: Autenticación basada en tokens
- **JSON Responses**: Formato estándar de respuestas
- **Error Handling**: Manejo consistente de errores
- **CORS Enabled**: Habilitado para frontend SPA
- **Rate Limiting**: Límites en endpoints públicos

---

## 🔐 Autenticación

### POST /auth/login
Autentica usuario y retorna JWT token.

**Request:**
```json
{
  "email": "admin@jamwithjamie.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_in": 3600,
    "user": {
      "id": "uuid-here",
      "email": "admin@jamwithjamie.com",
      "first_name": "Admin",
      "last_name": "User",
      "role": {
        "id": "uuid-here",
        "name": "Administrator"
      }
    }
  }
}
```

**Headers para requests autenticados:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

---

## 📊 Dashboard - Métricas Administrativas

Base URL: `/dashboard`

### GET /dashboard/reservations-by-status
Distribución de reservas por estado con porcentajes.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "status": "new",
        "label": "Nueva",
        "count": 45,
        "percentage": 60.0,
        "color": "#17a2b8"
      },
      {
        "status": "confirmed",
        "label": "Confirmada",
        "count": 25,
        "percentage": 33.3,
        "color": "#28a745"
      },
      {
        "status": "cancelled",
        "label": "Cancelada",
        "count": 5,
        "percentage": 6.7,
        "color": "#dc3545"
      }
    ],
    "total": 75
  }
}
```

### GET /dashboard/reservations-evolution
Evolución temporal de reservas (últimos 6 meses).

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "month": "2024-09",
      "month_name": "Septiembre",
      "reservations_count": 28,
      "revenue": 12450.00
    },
    {
      "month": "2024-08",
      "month_name": "Agosto",
      "reservations_count": 35,
      "revenue": 15680.00
    }
  ]
}
```

### GET /dashboard/payment-status
Estado de pagos con métricas financieras.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "paid": {
      "count": 42,
      "percentage": 70.0,
      "total_amount": 18750.00
    },
    "pending": {
      "count": 18,
      "percentage": 30.0,
      "total_amount": 8125.00
    },
    "total_revenue": 26875.00
  }
}
```

### GET /dashboard/most-popular-addons
Addons más vendidos con estadísticas detalladas.

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "addon_name": "Jukebox Premium",
      "addon_description": "Sistema de música premium",
      "total_sold": 45,
      "total_revenue": 6750.00,
      "reservations_count": 35,
      "avg_price": 150.00
    },
    {
      "addon_name": "Decoración Extra",
      "addon_description": "Paquete adicional de decoración",
      "total_sold": 32,
      "total_revenue": 3200.00,
      "reservations_count": 28,
      "avg_price": 100.00
    }
  ]
}
```

### GET /dashboard/popular-jam-types
Servicios más populares por reservas e ingresos.

### GET /dashboard/cities-with-most-events
Ciudades con mayor número de eventos organizados.

---

## 🎪 Reservations - Gestión de Reservas

Base URL: `/reservations`
**Autenticación:** Requerida

### GET /reservations
Lista todas las reservas con información completa.

**Query Parameters:**
- `status` (opcional): Filtrar por estado
- `customer_id` (opcional): Filtrar por cliente
- `date_from` (opcional): Fecha desde (YYYY-MM-DD)
- `date_to` (opcional): Fecha hasta (YYYY-MM-DD)
- `limit` (opcional): Límite de resultados (default: 50)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid-reservation",
      "customer_id": "uuid-customer",
      "service_price_id": "uuid-service-price",
      "event_address": "123 Main St, City",
      "event_date": "2024-12-15",
      "event_time": "15:00:00",
      "children_count": 8,
      "duration_hours": 2.0,
      "base_price": 450.00,
      "addons_total": 200.00,
      "total_amount": 650.00,
      "status": "confirmed",
      "is_paid": false,
      "is_invoiced": false,
      "customer_name": "Maria Rodriguez",
      "customer_email": "maria@email.com",
      "customer_phone": "+1234567890",
      "service_name": "Birthday Party Premium",
      "county_name": "Miami-Dade",
      "city_name": "Miami",
      "zipcode": "33101",
      "created_at": "2024-09-01T10:30:00Z"
    }
  ],
  "total": 1,
  "page": 1,
  "per_page": 50
}
```

### GET /reservations/{id}
Obtiene una reserva específica con todos los detalles.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "reservation": {
      "id": "uuid-reservation",
      "customer_id": "uuid-customer",
      // ... todos los campos de reserva
    },
    "addons": [
      {
        "id": "uuid-addon",
        "name": "Jukebox Premium",
        "quantity": 1,
        "price_at_time": 150.00,
        "total": 150.00
      }
    ],
    "customer": {
      "id": "uuid-customer",
      "full_name": "Maria Rodriguez",
      "email": "maria@email.com",
      "phone": "+1234567890"
    }
  }
}
```

**Response (404):**
```json
{
  "success": false,
  "message": "Reservation not found"
}
```

### POST /reservations
Crea una nueva reserva (método administrativo).

**Request:**
```json
{
  "customer_id": "uuid-customer",
  "service_price_id": "uuid-service-price",
  "zipcode_id": "uuid-zipcode",
  "event_address": "123 Party Lane",
  "event_date": "2024-12-20",
  "event_time": "15:00",
  "children_count": 10,
  "duration_hours": 2.5,
  "birthday_child_name": "Sofia",
  "birthday_child_age": 8,
  "sing_happy_birthday": true,
  "addons": [
    {
      "addon_id": "uuid-addon",
      "quantity": 1
    }
  ],
  "customer_notes": "Preferible música en español"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Reservation created successfully",
  "data": {
    "id": "uuid-new-reservation",
    "total_amount": 750.00
  }
}
```

### PUT /reservations/{id}
Actualiza una reserva existente.

**Request:**
```json
{
  "event_date": "2024-12-25",
  "children_count": 12,
  "status": "confirmed",
  "internal_notes": "Cliente confirmó fecha"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Reservation updated successfully"
}
```

### DELETE /reservations/{id}
Elimina una reserva (soft delete).

**Response (200):**
```json
{
  "success": true,
  "message": "Reservation deleted successfully"
}
```

---

## 🏠 Home - Endpoints Públicos

Base URL: `/home`
**Autenticación:** No requerida

### POST /home/reservation
Crea reserva desde formulario público multi-step.

**Request:**
```json
{
  "customer": {
    "firstName": "Juan",
    "lastName": "Pérez",
    "email": "juan@email.com",
    "phone": "+1234567890"
  },
  "zipcode": {
    "id": "uuid-zipcode"
  },
  "service": {
    "id": "uuid-service-price",
    "amount": 350.00,
    "extra_child_fee": 75.00
  },
  "kids": {
    "selectedKids": 10
  },
  "hours": {
    "duration": 2.0
  },
  "addons": [
    {
      "id": "uuid-addon",
      "base_price": 150.00,
      "quantity": 1
    }
  ],
  "information": {
    "fullAddress": "123 Main Street, Miami FL",
    "eventDate": "2024-12-01",
    "startTime": "15:00",
    "birthdayChildName": "Sofia",
    "childAge": 8,
    "happyBirthdayRequest": "yes"
  }
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Reservation created successfully",
  "data": {
    "reservation_id": "uuid-new-reservation",
    "customer_id": "uuid-customer",
    "total_amount": 695.00,
    "breakdown": {
      "service_base": 350.00,
      "extra_children": 225.00,
      "addons": 150.00,
      "surcharge": 0.00,
      "total": 725.00
    }
  }
}
```

### GET /home/counties
Lista condados disponibles para selección.

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid-county",
      "name": "Miami-Dade",
      "metropolitan_area": "South Florida"
    },
    {
      "id": "uuid-county-2",
      "name": "Broward",
      "metropolitan_area": "South Florida"
    }
  ]
}
```

### GET /home/zipcode/{cityId}/{code}
Valida código postal específico por ciudad.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": "uuid-zipcode",
    "zipcode": "33101",
    "city_name": "Miami",
    "county_name": "Miami-Dade",
    "is_valid": true
  }
}
```

**Response (404):**
```json
{
  "success": false,
  "message": "Zipcode not found for this city"
}
```

### GET /home/services/{countyId}
Servicios disponibles por condado.

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid-service-price",
      "service_id": "uuid-service",
      "service_name": "Birthday Party Basic",
      "description": "Paquete básico de entretenimiento",
      "amount": 300.00,
      "extra_child_fee": 50.00,
      "performers_count": 1,
      "min_duration_hours": 1.0,
      "img": "party-basic.jpg",
      "county_name": "Miami-Dade"
    }
  ]
}
```

### GET /home/range-kids/{servicePriceId}
Rangos de edad disponibles para un servicio.

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid-range",
      "min_age": 3,
      "max_age": 6,
      "description": "Preescolar (3-6 años)"
    },
    {
      "id": "uuid-range-2",
      "min_age": 7,
      "max_age": 12,
      "description": "Escolar (7-12 años)"
    }
  ]
}
```

### GET /home/hours/{servicePriceId}
Duraciones disponibles para un servicio.

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid-duration",
      "minutes": 60,
      "label": "1 hora"
    },
    {
      "id": "uuid-duration-2",
      "minutes": 90,
      "label": "1.5 horas"
    },
    {
      "id": "uuid-duration-3",
      "minutes": 120,
      "label": "2 horas"
    }
  ]
}
```

### GET /home/addons
Lista addons activos disponibles para selección.

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid-addon",
      "name": "Jukebox Premium",
      "description": "Sistema de música con luces",
      "base_price": 150.00,
      "estimated_duration_minutes": 30,
      "image": "jukebox.jpg",
      "price_type": "jukebox"
    },
    {
      "id": "uuid-addon-2",
      "name": "Decoración Extra",
      "description": "Globos y decoración adicional",
      "base_price": 100.00,
      "image": "decoration.jpg",
      "price_type": "standard"
    }
  ]
}
```

---

## 👤 Customers - Gestión de Clientes

Base URL: `/customers`
**Autenticación:** Requerida

### GET /customers
Lista todos los clientes.

**Query Parameters:**
- `search` (opcional): Búsqueda por nombre o email
- `segment` (opcional): Filtrar por segmento
- `limit` (opcional): Límite de resultados

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid-customer",
      "email": "maria@email.com",
      "phone": "+1234567890",
      "full_name": "Maria Rodriguez",
      "billing_address": "123 Oak St, Miami FL 33101",
      "segment": "premium",
      "created_at": "2024-01-15T09:30:00Z",
      "total_reservations": 3,
      "total_spent": 1875.00,
      "last_reservation_date": "2024-08-15"
    }
  ]
}
```

### GET /customers/{id}
Obtiene un cliente específico con estadísticas.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "customer": {
      "id": "uuid-customer",
      "email": "maria@email.com",
      "phone": "+1234567890",
      "full_name": "Maria Rodriguez",
      "billing_address": "123 Oak St, Miami FL 33101",
      "segment": "premium",
      "created_at": "2024-01-15T09:30:00Z"
    },
    "stats": {
      "total_reservations": 3,
      "total_spent": 1875.00,
      "avg_spent": 625.00,
      "last_reservation_date": "2024-08-15",
      "favorite_services": [
        "Birthday Party Premium"
      ]
    },
    "recent_reservations": [
      {
        "id": "uuid-reservation",
        "event_date": "2024-08-15",
        "total_amount": 650.00,
        "status": "completed"
      }
    ]
  }
}
```

### POST /customers
Crea un nuevo cliente.

**Request:**
```json
{
  "email": "nuevo@cliente.com",
  "phone": "+1987654321",
  "full_name": "Cliente Nuevo",
  "billing_address": "456 Pine St, Orlando FL 32801",
  "segment": "standard"
}
```

### PUT /customers/{id}
Actualiza información del cliente.

### DELETE /customers/{id}
Elimina un cliente (soft delete).

---

## 🎭 Services - Gestión de Servicios

Base URL: `/services`
**Autenticación:** Requerida

### GET /services
Lista todos los servicios.

### GET /services/active
Lista únicamente servicios activos.

### GET /services/{id}
Obtiene un servicio específico.

### POST /services
Crea un nuevo servicio.

### PUT /services/{id}
Actualiza un servicio.

### DELETE /services/{id}
Elimina un servicio (soft delete).

---

## 💲 Service Prices - Precios por Ubicación

Base URL: `/service-prices`
**Autenticación:** Requerida

### GET /service-prices
Lista todos los precios de servicios.

### GET /service-prices/county/{countyId}
Obtiene precios disponibles por condado.

### GET /service-prices/{id}
Obtiene un precio específico.

### POST /service-prices
Crea un nuevo precio de servicio.

### PUT /service-prices/{id}
Actualiza un precio.

### DELETE /service-prices/{id}
Elimina un precio.

---

## ➕ Addons - Servicios Adicionales

Base URL: `/addons`
**Autenticación:** Requerida

### GET /addons
Lista todos los addons.

### GET /addons/active
Lista addons activos únicamente.

### GET /addons/search
Busca addons por nombre.

**Query Parameters:**
- `name` (requerido): Término de búsqueda

### GET /addons/{id}
Obtiene un addon específico.

### POST /addons
Crea un nuevo addon (con imagen).

**Content-Type:** `multipart/form-data`

**Request:**
```
name: Nuevo Addon
description: Descripción del addon
base_price: 125.00
price_type: standard
is_active: true
image: [archivo de imagen]
```

### PUT /addons/{id}
Actualiza un addon.

### DELETE /addons/{id}
Elimina un addon.

---

## 🗺️ Geographic - Endpoints de Geografía

### Counties
Base URL: `/counties`

- **GET** `/counties` - Lista condados
- **GET** `/counties/{id}` - Condado específico
- **POST** `/counties` - Crear condado
- **PUT** `/counties/{id}` - Actualizar condado
- **DELETE** `/counties/{id}` - Eliminar condado

### Cities
Base URL: `/cities`

- **GET** `/cities` - Lista ciudades
- **GET** `/cities/county/{countyId}` - Ciudades por condado
- **GET** `/cities/{id}` - Ciudad específica
- **POST** `/cities` - Crear ciudad
- **PUT** `/cities/{id}` - Actualizar ciudad
- **DELETE** `/cities/{id}` - Eliminar ciudad

### ZipCodes
Base URL: `/zipcodes`

- **GET** `/zipcodes` - Lista códigos postales
- **GET** `/zipcodes/city/{cityId}` - Códigos por ciudad
- **GET** `/zipcodes/{id}` - Código específico
- **POST** `/zipcodes` - Crear código
- **PUT** `/zipcodes/{id}` - Actualizar código
- **DELETE** `/zipcodes/{id}` - Eliminar código

### Metropolitan Areas
Base URL: `/metropolitan-areas`

- **GET** `/metropolitan-areas` - Lista áreas metropolitanas
- **GET** `/metropolitan-areas/{id}` - Área específica
- **POST** `/metropolitan-areas` - Crear área
- **PUT** `/metropolitan-areas/{id}` - Actualizar área
- **DELETE** `/metropolitan-areas/{id}` - Eliminar área

---

## ❌ Manejo de Errores

### Códigos de Estado HTTP

- **200 OK**: Operación exitosa
- **201 Created**: Recurso creado
- **400 Bad Request**: Error de validación
- **401 Unauthorized**: No autenticado
- **403 Forbidden**: Sin permisos
- **404 Not Found**: Recurso no encontrado
- **422 Unprocessable Entity**: Error de validación específico
- **500 Internal Server Error**: Error del servidor

### Formato de Respuesta de Error

```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "email": ["Email format is invalid"],
    "phone": ["Phone number is required"]
  }
}
```

### Ejemplos de Errores Comunes

**401 Unauthorized:**
```json
{
  "success": false,
  "message": "Token is invalid or expired"
}
```

**404 Not Found:**
```json
{
  "success": false,
  "message": "Reservation not found"
}
```

**422 Validation Error:**
```json
{
  "success": false,
  "message": "The given data was invalid",
  "errors": {
    "event_date": ["Event date cannot be in the past"],
    "children_count": ["At least 1 child is required"]
  }
}
```

---

## 🔧 Rate Limiting

### Límites por Endpoint

- **Endpoints públicos**: 60 requests/minuto por IP
- **Endpoints autenticados**: 200 requests/minuto por usuario
- **Upload endpoints**: 10 requests/minuto por usuario

### Headers de Rate Limit

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1640995200
```

---

## 📄 Paginación

### Parámetros de Query

- `page`: Número de página (default: 1)
- `per_page`: Elementos por página (default: 20, max: 100)
- `sort`: Campo de ordenamiento
- `order`: Dirección (asc/desc)

### Respuesta Paginada

```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 145,
    "last_page": 8,
    "from": 1,
    "to": 20
  }
}
```

---

## 🧪 Testing con Postman

### Colección de Endpoints

```json
{
  "info": {
    "name": "JamWithJamie API",
    "version": "2.0.0"
  },
  "auth": {
    "type": "bearer",
    "bearer": [
      {
        "key": "token",
        "value": "{{auth_token}}"
      }
    ]
  },
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost:8080/api"
    },
    {
      "key": "auth_token",
      "value": ""
    }
  ]
}
```

### Variables de Entorno

```json
{
  "development": {
    "base_url": "http://localhost:8080/api",
    "admin_email": "admin@jamwithjamie.com",
    "admin_password": "password123"
  },
  "production": {
    "base_url": "https://api.jamwithjamie.com/api",
    "admin_email": "admin@jamwithjamie.com",
    "admin_password": "{{secure_password}}"
  }
}
```

---

*Documentación actualizada: Septiembre 2025*