# Controllers - Documentación de API

## Arquitectura de Controladores

Los controladores en JamWithJamie siguen el patrón RESTful Resource Controller de CodeIgniter 4, proporcionando endpoints HTTP para la gestión de recursos del sistema.

### Estructura de Respuestas

Todos los controladores siguen un formato estándar de respuesta:

```json
{
  "success": boolean,
  "message": string,
  "data": object|array
}
```

### Manejo de Errores

- **HTTP 200**: Operación exitosa
- **HTTP 201**: Recurso creado
- **HTTP 400**: Error de validación o datos incorrectos
- **HTTP 404**: Recurso no encontrado
- **HTTP 500**: Error interno del servidor

---

## 📊 DashboardController

**Ruta Base**: `/api/dashboard`
**Autenticación**: Requerida (JWT)
**Propósito**: Métricas y análisis administrativo

### Endpoints

#### `GET /reservations-by-status`
Distribución de reservas por estado con porcentajes y colores.

**Respuesta**:
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
      }
    ],
    "total": 75
  }
}
```

#### `GET /reservations-evolution`
Evolución temporal de reservas (últimos 6 meses).

#### `GET /payment-status`
Estado de pagos con métricas financieras.

#### `GET /invoice-status`
Estado de facturación.

#### `GET /popular-jam-types`
Servicios más populares con ingresos.

#### `GET /cities-with-most-events`
Top ciudades por número de eventos.

#### `GET /most-popular-addons`
Addons más vendidos con estadísticas.

---

## 🎪 ReservationController

**Ruta Base**: `/api/reservations`
**Autenticación**: Requerida (JWT)
**Propósito**: Gestión completa de reservas

### Endpoints

#### `GET /`
Lista todas las reservas con información completa.

#### `GET /{id}`
Obtiene una reserva específica por ID.

#### `POST /`
Crea una nueva reserva (método legacy).

#### `POST /create-from-form`
Crea reserva desde formulario multi-step (método principal).

**Body**:
```json
{
  "customer": {
    "firstName": "Juan",
    "lastName": "Pérez",
    "email": "juan@email.com",
    "phone": "+1234567890"
  },
  "zipcode": {"id": "uuid"},
  "service": {
    "id": "uuid",
    "amount": 350,
    "extra_child_fee": 75
  },
  "kids": {"selectedKids": 10},
  "hours": {"duration": 2.0},
  "addons": [
    {
      "id": "uuid",
      "base_price": 150,
      "quantity": 1
    }
  ],
  "information": {
    "fullAddress": "123 Main St",
    "eventDate": "2025-12-01",
    "startTime": "15:00",
    "birthdayChildName": "Sofia",
    "childAge": 8,
    "happyBirthdayRequest": "yes"
  }
}
```

#### `PUT /{id}`
Actualiza una reserva existente.

#### `DELETE /{id}`
Elimina una reserva (soft delete).

---

## 👤 CustomerController

**Ruta Base**: `/api/customers`
**Autenticación**: Requerida (JWT)
**Propósito**: Gestión de clientes

### Endpoints

#### `GET /`
Lista todos los clientes.

#### `GET /{id}`
Obtiene un cliente específico.

#### `GET /search?name={name}`
Busca clientes por nombre.

#### `POST /`
Crea un nuevo cliente.

#### `PUT /{id}`
Actualiza información del cliente.

#### `DELETE /{id}`
Elimina un cliente.

---

## 🎭 ServiceController

**Ruta Base**: `/api/services`
**Autenticación**: Requerida (JWT)
**Propósito**: Gestión de servicios

### Endpoints

#### `GET /`
Lista todos los servicios.

#### `GET /active`
Lista servicios activos únicamente.

#### `GET /{id}`
Obtiene un servicio específico.

#### `POST /`
Crea un nuevo servicio.

#### `PUT /{id}`
Actualiza un servicio.

#### `DELETE /{id}`
Elimina un servicio.

---

## 💲 ServicePriceController

**Ruta Base**: `/api/service-prices`
**Autenticación**: Requerida (JWT)
**Propósito**: Gestión de precios por ubicación

### Endpoints

#### `GET /`
Lista todos los precios de servicios.

#### `GET /county/{countyId}`
Obtiene precios por condado.

#### `GET /{id}`
Obtiene un precio específico.

#### `POST /`
Crea un nuevo precio de servicio.

#### `PUT /{id}`
Actualiza un precio.

#### `DELETE /{id}`
Elimina un precio.

---

## ➕ AddonController

**Ruta Base**: `/api/addons`
**Autenticación**: Requerida (JWT)
**Propósito**: Gestión de servicios adicionales

### Endpoints

#### `GET /`
Lista todos los addons.

#### `GET /active`
Lista addons activos.

#### `GET /search?name={name}`
Busca addons por nombre.

#### `GET /{id}`
Obtiene un addon específico.

#### `POST /`
Crea un nuevo addon (con imagen).

#### `PUT /{id}`
Actualiza un addon.

#### `DELETE /{id}`
Elimina un addon.

---

## 🏠 HomeController

**Ruta Base**: `/api/home`
**Autenticación**: No requerida
**Propósito**: Endpoints públicos para el frontend

### Endpoints

#### `GET /counties`
Lista condados disponibles para selección.

#### `GET /zipcode/{cityId}/{code}`
Valida código postal por ciudad.

#### `GET /services/{countyId}`
Servicios disponibles por condado.

#### `GET /range-kids/{servicePriceId}`
Rangos de edad para un servicio.

#### `GET /hours/{servicePriceId}`
Duraciones disponibles para un servicio.

#### `GET /addons`
Lista addons activos para selección.

#### `POST /reservation`
Crea reserva desde el formulario público.

---

## 🗺️ Controladores de Geografía

### CountyController
**Ruta**: `/api/counties`
Gestión de condados y áreas metropolitanas.

### CityController
**Ruta**: `/api/cities`
Gestión de ciudades por condado.

### ZipCodeController
**Ruta**: `/api/zipcodes`
Gestión de códigos postales.

### MetropolitanAreaController
**Ruta**: `/api/metropolitan-areas`
Gestión de áreas metropolitanas.

---

## 🔐 LoginController

**Ruta Base**: `/api/auth`
**Propósito**: Autenticación JWT

### Endpoints

#### `POST /login`
Autentica usuario y genera JWT token.

**Body**:
```json
{
  "email": "admin@example.com",
  "password": "password123"
}
```

**Respuesta**:
```json
{
  "success": true,
  "data": {
    "token": "jwt-token-here",
    "user": {
      "id": "uuid",
      "email": "admin@example.com",
      "role": "admin"
    }
  }
}
```

---

## 🔧 Controladores de Configuración

### UserController
Gestión de usuarios del sistema.

### RoleController
Gestión de roles y permisos.

### ChildrenAgeRangeController
Configuración de rangos de edad.

### DurationController
Configuración de duraciones de servicio.

### ReservationAddonController
Gestión de relaciones reserva-addon.

---

## 📝 Patrones de Desarrollo

### Estructura Común
```php
class ExampleController extends ResourceController
{
    protected $service;

    public function __construct()
    {
        $this->service = new ExampleService();
    }

    public function index()
    {
        try {
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response('message', $this->service->getAll()));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }
}
```

### Helper Functions
- `create_response($message, $data)`: Formatea respuestas estándar
- `lang($key)`: Obtiene mensajes localizados

### Middleware
- **AuthMiddleware**: Valida JWT tokens
- **CorsMiddleware**: Maneja headers CORS
- **RoleMiddleware**: Verifica permisos por rol

---

## 🚀 Mejores Prácticas

1. **Validación**: Todos los inputs se validan en el controlador
2. **Excepciones**: Se capturan y formatean consistentemente
3. **Status Codes**: Se usan códigos HTTP apropiados
4. **Logging**: Errores se registran para debugging
5. **Rate Limiting**: Endpoints públicos tienen límites de requests
6. **Caching**: Respuestas frecuentes se cachean
7. **Paginación**: Listas grandes se paginan automáticamente

---

*Documentación actualizada: Septiembre 2025*