# JamWithJamie - Plataforma de Entretenimiento Infantil

![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)
![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4-orange.svg)
![Vue.js](https://img.shields.io/badge/Vue.js-3.5-green.svg)

## 📋 Descripción del Proyecto

JamWithJamie es una plataforma web completa para la gestión de servicios de entretenimiento infantil. Permite a los clientes reservar servicios de entretenimiento para fiestas infantiles, gestionar addons, y administrar toda la operación del negocio.

### 🎯 Funcionalidades Principales

- **Reservas Multi-Step**: Wizard de 8 pasos para crear reservas
- **Gestión de Servicios**: Catálogo completo de servicios de entretenimiento
- **Sistema de Addons**: Servicios adicionales personalizables
- **Cálculo Automático**: Precios dinámicos con recargos por fecha
- **Dashboard Administrativo**: Métricas y análisis del negocio
- **Gestión de Clientes**: CRM integrado para manejo de customers
- **Sistema de Roles**: Control de acceso granular
- **API RESTful**: Backend completo con CodeIgniter 4

## 🏗️ Arquitectura del Proyecto

```
JamWitjJamie/
├── 🎨 frontend/           # Vue.js 3 SPA
│   ├── src/components/    # Componentes Vue reutilizables
│   ├── src/views/        # Vistas principales
│   └── src/services/     # Servicios API
├── 🔧 app/               # Backend CodeIgniter 4
│   ├── Controllers/      # Controladores REST
│   ├── Services/         # Lógica de negocio
│   ├── Repositories/     # Capa de datos
│   ├── Models/          # Modelos de datos
│   └── Database/        # Migraciones y seeders
└── 📚 docs/             # Documentación
```

## 🚀 Stack Tecnológico

### Backend
- **Framework**: CodeIgniter 4.4+
- **PHP**: 8.1+
- **Base de Datos**: MySQL 8.0+
- **Autenticación**: JWT Tokens
- **Validación**: CodeIgniter Validation
- **Email**: Brevo API Integration

### Frontend
- **Framework**: Vue.js 3.5
- **Build Tool**: Vite 7.0
- **UI Framework**: Bootstrap 5.3
- **State Management**: Composables + Pinia
- **Routing**: Vue Router 4
- **Forms**: VeeValidate 4
- **Charts**: Chart.js + Vue-ChartJS
- **HTTP Client**: Axios

### Desarrollo
- **CSS Preprocessor**: Sass
- **Package Manager**: NPM
- **Icons**: Bootstrap Icons
- **Animations**: Animate.css
- **Notifications**: Vue Toastification

## 📊 Modelo de Datos

### Entidades Principales

#### 🎪 **Reservas (Reservations)**
- Sistema central de reservas con cálculos automáticos
- Estados: new, under_review, confirmed, cancelled
- Integración con servicios, addons y clientes

#### 👨‍👩‍👧‍👦 **Clientes (Customers)**
- Gestión completa de información de clientes
- Historial de reservas y preferencias
- Sistema de contacto integrado

#### 🎭 **Servicios (Services)**
- Catálogo de servicios de entretenimiento
- Precios por condado y área metropolitana
- Configuración de performers y duraciones

#### ➕ **Addons**
- Servicios adicionales personalizables
- Tipos: standard, jukebox
- Precios dinámicos y disponibilidad

#### 🗺️ **Geografía**
```
Metropolitan Areas → Counties → Cities → ZipCodes
```

## 🔄 Flujo de Negocio

### Proceso de Reserva (8 Steps)

1. **Step 1**: Información del Cliente
2. **Step 2**: Selección de Código Postal
3. **Step 3**: Selección de Servicio
4. **Step 4**: Cantidad de Niños
5. **Step 5**: Duración del Evento
6. **Step 6**: Selección de Addons
7. **Step 7**: Confirmación de Subtotal
8. **Step 8**: Información Detallada del Evento

### Cálculo de Precios

```
Base Total = Servicio Base + Addons + (Niños Extra × Fee)
Recargo = Base Total × Porcentaje (según proximidad fecha)
Total Final = Base Total + Recargo
```

**Recargos por Fecha:**
- < 2 días: +20%
- 2-7 días: +10%
- > 7 días: Sin recargo

## 🔐 Sistema de Autenticación

- **JWT Tokens** para autenticación stateless
- **Roles y Permisos** granulares
- **Middleware** de autorización en rutas
- **Refresh Tokens** para sesiones extendidas

## 📡 API Endpoints

### Rutas Públicas (Frontend)
```
GET  /api/home/counties           # Lista de condados
GET  /api/home/services/{id}      # Servicios por condado
GET  /api/home/addons            # Addons activos
POST /api/home/reservation       # Crear reserva
```

### Rutas Administrativas
```
GET  /api/reservations           # Lista reservas
GET  /api/dashboard/*            # Métricas del negocio
GET  /api/customers              # Gestión clientes
```

## 🚀 Instalación y Configuración

### Prerrequisitos
- PHP 8.1+
- MySQL 8.0+
- Node.js 18+
- Composer 2.0+

### Backend Setup
```bash
# Clonar repositorio
git clone <repository-url>
cd JamWitjJamie

# Instalar dependencias PHP
composer install

# Configurar base de datos
cp app/Config/Database.example.php app/Config/Database.php
# Editar configuración de BD

# Ejecutar migraciones
php spark migrate

# Ejecutar seeders
php spark db:seed DatabaseSeeder

# Iniciar servidor
php spark serve
```

### Frontend Setup
```bash
# Navegar a frontend
cd frontend

# Instalar dependencias
npm install

# Desarrollo
npm run watch

# Producción
npm run build
```

## 🧪 Testing

### Backend Tests
```bash
php spark test
```

### API Testing
- Usar Postman/Insomnia con collection incluida
- Tests automatizados con PHPUnit

## 📈 Métricas y Analytics

El dashboard incluye:
- **Reservas por Estado**: Distribución de estados
- **Evolución Temporal**: Tendencias mensuales
- **Estado de Pagos**: Métricas financieras
- **Addons Populares**: Análisis de productos
- **Ciudades Top**: Geografía de servicios
- **Tipos de Jam**: Servicios más demandados

## 🔧 Configuración

### Variables de Entorno
```env
# Base de datos
database.default.hostname = localhost
database.default.database = jamwithjamie
database.default.username = user
database.default.password = password

# JWT
JWT_SECRET = your-secret-key
JWT_EXPIRE = 3600

# Email
BREVO_API_KEY = your-brevo-key
```

## 📚 Documentación Técnica

- [API Documentation](docs/API.md)
- [Database Schema](docs/DATABASE.md)
- [Frontend Components](docs/FRONTEND.md)
- [Deployment Guide](docs/DEPLOYMENT.md)

## 🤝 Contribución

1. Fork el proyecto
2. Crear branch de feature (`git checkout -b feature/AmazingFeature`)
3. Commit cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push al branch (`git push origin feature/AmazingFeature`)
5. Abrir Pull Request

## 📝 Licencia

Este proyecto está bajo la licencia MIT. Ver `LICENSE` para más detalles.

## 👥 Equipo

- **Backend Developer**: Arquitectura CodeIgniter 4
- **Frontend Developer**: Vue.js 3 SPA
- **Database Designer**: Modelo de datos optimizado
- **DevOps**: Configuración de despliegue

---

**JamWithJamie** - Llevando alegría a las fiestas infantiles 🎉

*Versión: 2.0.0 | Última actualización: Septiembre 2025*
