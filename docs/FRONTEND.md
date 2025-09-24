# Frontend - Vue.js 3 SPA

## Arquitectura Frontend

El frontend de JamWithJamie es una Single Page Application (SPA) desarrollada con Vue.js 3, Vite y Bootstrap 5. Implementa una arquitectura moderna basada en composables, rutas protegidas y gestión de estado reactiva.

### Stack Tecnológico

- **Framework**: Vue.js 3.5
- **Build Tool**: Vite 7.0
- **UI Framework**: Bootstrap 5.3
- **Router**: Vue Router 4
- **State Management**: Composables + Pinia
- **Forms**: VeeValidate 4
- **Charts**: Chart.js + Vue-ChartJS
- **HTTP Client**: Axios
- **Styling**: Sass/SCSS

---

## 📁 Estructura del Proyecto

```
frontend/
├── src/
│   ├── components/           # Componentes Vue organizados por módulo
│   │   ├── admin/           # Área administrativa
│   │   │   ├── dashboard/   # Dashboard con métricas
│   │   │   ├── reservations/# Gestión de reservas
│   │   │   ├── services/    # Servicios y precios
│   │   │   ├── areas/       # Geografía (condados/ciudades)
│   │   │   ├── client/      # Gestión de clientes
│   │   │   ├── config/      # Configuración del sistema
│   │   │   └── template/    # Plantillas base (navbar, sidebar)
│   │   ├── home/            # Área pública
│   │   │   ├── form/        # Wizard de reserva (8 pasos)
│   │   │   ├── template/    # Plantillas públicas
│   │   │   └── login/       # Autenticación
│   │   └── not-found/       # Página 404
│   ├── views/               # Vistas principales
│   ├── services/            # Servicios API
│   ├── composables/         # Lógica reutilizable
│   ├── utils/               # Utilidades
│   └── assets/              # Recursos estáticos
```

---

## 🎪 Módulos Principales

### Home - Área Pública

#### Wizard de Reserva (8 Pasos)

El proceso de reserva está dividido en 8 componentes especializados:

1. **Step1.vue** - Información del Cliente
   - Formulario de datos personales
   - Validación de email y teléfono
   - Integración con VeeValidate

2. **Step2.vue** - Selección de Código Postal
   - Lista de condados disponibles
   - Validación de código postal por ciudad
   - Llamadas a API de geografía

3. **Step3.vue** - Selección de Servicio
   - Servicios disponibles por condado
   - Precios dinámicos por ubicación
   - Visualización de imágenes de servicios

4. **Step4.vue** - Cantidad de Niños
   - Selector de número de niños
   - Cálculo de tarifas adicionales
   - Validación de cantidad mínima

5. **Step5.vue** - Duración del Evento
   - Opciones de duración disponibles
   - Precios por duración
   - Configuración por servicio

6. **Step6.vue** - Selección de Addons
   - Catálogo de servicios adicionales
   - Selector de cantidad por addon
   - Cálculo de subtotales

7. **Step7.vue** - Confirmación de Subtotal
   - Resumen de la reserva
   - Cálculo de recargos por fecha
   - Revisión antes de finalizar

8. **Step8.vue** - Información Detallada
   - Detalles específicos del evento
   - Instrucciones especiales
   - Confirmación final

#### Componentes de Soporte

- **Home.vue** - Contenedor principal del wizard
- **NavbarHome.vue** - Navegación pública

### Admin - Área Administrativa

#### Dashboard

- **Dashboard.vue** - Vista principal con métricas
- **StatusChart.vue** - Gráfico de estados de reserva
- **EvolutionChart.vue** - Evolución temporal
- **PaymentChart.vue** - Estado de pagos
- **JamTypesChart.vue** - Servicios populares
- **CitiesChart.vue** - Ciudades con más eventos
- **AddonsChart.vue** - Addons más vendidos

#### Reservations

- **Reservations.vue** - Lista de reservas
- **ReservationView.vue** - Vista detallada
- **ReservationEdit.vue** - Edición de reserva
- **ReservationCreate.vue** - Nueva reserva (admin)

##### Creación de Reservas (Admin)
- **ReservationForm.vue** - Formulario principal
- **ReservationClient.vue** - Selección de cliente
- **ReservationAreas.vue** - Selección de ubicación
- **ReservationServices.vue** - Selección de servicio
- **ReservationBoys.vue** - Cantidad de niños
- **ReservationAddons.vue** - Selección de addons
- **ReservationTotal.vue** - Cálculo final

#### Services

- **JamTypes.vue** - Gestión de tipos de servicio
- **Prices.vue** - Gestión de precios por condado
- **AddOns.vue** - Gestión de servicios adicionales

#### Areas (Geografía)

- **MetropolitanAreas.vue** - Áreas metropolitanas
- **Counties.vue** - Condados
- **Cities.vue** - Ciudades
- **PostalCode.vue** - Códigos postales

#### Client

- **Client.vue** - Lista de clientes
- **ClientEdit.vue** - Edición de cliente
- **ClientCreate.vue** - Nuevo cliente

#### Config (Configuración)

- **Users.vue** - Gestión de usuarios
- **Roles.vue** - Gestión de roles
- **Menu.vue** - Configuración de menús

#### Template

- **NavbarAdmin.vue** - Navegación administrativa
- **SidebarAdmin.vue** - Menú lateral con navegación
- **Admin.vue** - Layout base del área administrativa

---

## 🔧 Servicios y API

### Estructura de Servicios

```javascript
// Ejemplo: ReservationService.js
import { api } from '@/utils/api'

export const ReservationService = {
  async getAll() {
    return api.get('/reservations')
  },

  async getById(id) {
    return api.get(`/reservations/${id}`)
  },

  async create(data) {
    return api.post('/reservations', data)
  },

  async createFromForm(data) {
    return api.post('/home/reservation', data)
  },

  async update(id, data) {
    return api.put(`/reservations/${id}`, data)
  },

  async delete(id) {
    return api.delete(`/reservations/${id}`)
  }
}
```

### API Client Configuration

```javascript
// utils/api.js
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8080/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  }
})

// Request interceptor para JWT
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('authToken')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// Response interceptor para manejo de errores
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Redirect to login
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)
```

---

## 📊 Charts y Visualizaciones

### Chart.js Integration

```vue
<template>
  <div class="chart-container">
    <Doughnut :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup>
import { Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend)

const chartData = {
  labels: ['Nueva', 'Confirmada', 'Cancelada'],
  datasets: [{
    data: [45, 30, 5],
    backgroundColor: ['#17a2b8', '#28a745', '#dc3545'],
    borderWidth: 0
  }]
}

const chartOptions = {
  responsive: true,
  plugins: {
    legend: {
      position: 'bottom'
    }
  }
}
</script>
```

---

## 🔐 Autenticación y Rutas

### Router Configuration

```javascript
// router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  // Rutas públicas
  {
    path: '/',
    name: 'Home',
    component: () => import('@/components/home/Home.vue')
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/components/home/login/Login.vue')
  },

  // Rutas protegidas (admin)
  {
    path: '/admin',
    component: () => import('@/components/admin/Admin.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/components/admin/dashboard/Dashboard.vue')
      },
      {
        path: 'reservations',
        name: 'Reservations',
        component: () => import('@/components/admin/reservations/Reservations.vue')
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Guard de autenticación
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
  } else {
    next()
  }
})
```

### Auth Store (Pinia)

```javascript
// stores/auth.js
import { defineStore } from 'pinia'
import { LoginService } from '@/services/LoginService'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('authToken'),
    isAuthenticated: false
  }),

  actions: {
    async login(credentials) {
      try {
        const response = await LoginService.login(credentials)

        this.token = response.data.token
        this.user = response.data.user
        this.isAuthenticated = true

        localStorage.setItem('authToken', this.token)

        return response
      } catch (error) {
        throw error
      }
    },

    logout() {
      this.user = null
      this.token = null
      this.isAuthenticated = false

      localStorage.removeItem('authToken')
    },

    checkAuth() {
      if (this.token) {
        this.isAuthenticated = true
      }
    }
  }
})
```

---

## 🎨 Estilos y UI

### Bootstrap 5 Customization

```scss
// assets/styles/main.scss
@import "bootstrap/scss/functions";
@import "bootstrap/scss/variables";

// Custom variables
$primary: #007bff;
$secondary: #6c757d;
$success: #28a745;
$info: #17a2b8;
$warning: #ffc107;
$danger: #dc3545;

@import "bootstrap/scss/bootstrap";

// Custom styles
.wizard-container {
  min-height: 600px;
  padding: 2rem;
}

.chart-container {
  position: relative;
  height: 300px;
  margin: 1rem 0;
}

.admin-sidebar {
  width: 280px;
  background: #343a40;
  min-height: 100vh;
}
```

### Component-Specific Styles

```vue
<style scoped>
.reservation-card {
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 1rem;
  transition: box-shadow 0.3s ease;
}

.reservation-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.status-badge {
  font-size: 0.75rem;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
}

.status-new {
  background-color: #17a2b8;
  color: white;
}

.status-confirmed {
  background-color: #28a745;
  color: white;
}

.status-cancelled {
  background-color: #dc3545;
  color: white;
}
</style>
```

---

## 🔄 Composables y Estado

### Ejemplo de Composable

```javascript
// composables/useReservations.js
import { ref, reactive } from 'vue'
import { ReservationService } from '@/services/ReservationService'

export function useReservations() {
  const reservations = ref([])
  const loading = ref(false)
  const error = ref(null)

  const reservation = reactive({
    customer: {},
    service: {},
    addons: [],
    total: 0
  })

  const fetchReservations = async () => {
    loading.value = true
    error.value = null

    try {
      const response = await ReservationService.getAll()
      reservations.value = response.data.data
    } catch (err) {
      error.value = err.message
    } finally {
      loading.value = false
    }
  }

  const createReservation = async (data) => {
    try {
      const response = await ReservationService.createFromForm(data)
      return response.data
    } catch (err) {
      throw err
    }
  }

  const calculateTotal = () => {
    let total = reservation.service.amount || 0

    // Add addons
    reservation.addons.forEach(addon => {
      total += addon.base_price * addon.quantity
    })

    // Add extra children
    if (reservation.extraChildren > 0) {
      total += reservation.extraChildren * reservation.service.extra_child_fee
    }

    // Date surcharge
    const eventDate = new Date(reservation.eventDate)
    const today = new Date()
    const diffDays = Math.ceil((eventDate - today) / (1000 * 60 * 60 * 24))

    if (diffDays < 2) {
      total *= 1.2 // 20% surcharge
    } else if (diffDays <= 7) {
      total *= 1.1 // 10% surcharge
    }

    reservation.total = total
    return total
  }

  return {
    reservations,
    loading,
    error,
    reservation,
    fetchReservations,
    createReservation,
    calculateTotal
  }
}
```

---

## 📱 Responsividad y UX

### Responsive Design

```vue
<template>
  <div class="container-fluid">
    <!-- Desktop sidebar -->
    <div class="row d-none d-lg-flex">
      <div class="col-lg-3 col-xl-2">
        <SidebarAdmin />
      </div>
      <div class="col-lg-9 col-xl-10">
        <router-view />
      </div>
    </div>

    <!-- Mobile navigation -->
    <div class="d-lg-none">
      <nav class="navbar navbar-expand-lg">
        <button class="navbar-toggler" @click="toggleMobileMenu">
          <i class="bi bi-list"></i>
        </button>
      </nav>

      <div v-if="showMobileMenu" class="mobile-sidebar">
        <SidebarAdmin @close="toggleMobileMenu" />
      </div>

      <div class="mobile-content">
        <router-view />
      </div>
    </div>
  </div>
</template>
```

### Loading States

```vue
<template>
  <div>
    <!-- Skeleton loading -->
    <div v-if="loading" class="loading-skeleton">
      <div class="skeleton-card" v-for="n in 3" :key="n">
        <div class="skeleton-line"></div>
        <div class="skeleton-line short"></div>
        <div class="skeleton-line medium"></div>
      </div>
    </div>

    <!-- Content -->
    <div v-else>
      <!-- Actual content -->
    </div>
  </div>
</template>

<style scoped>
.skeleton-card {
  padding: 1rem;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.skeleton-line {
  height: 1rem;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
  border-radius: 4px;
  margin-bottom: 0.5rem;
}

.skeleton-line.short {
  width: 60%;
}

.skeleton-line.medium {
  width: 80%;
}

@keyframes loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}
</style>
```

---

## 📊 Validación de Formularios

### VeeValidate Integration

```vue
<template>
  <Form @submit="onSubmit" v-slot="{ errors }">
    <div class="mb-3">
      <label class="form-label">Email</label>
      <Field
        name="email"
        type="email"
        class="form-control"
        :class="{ 'is-invalid': errors.email }"
        rules="required|email"
      />
      <div v-if="errors.email" class="invalid-feedback">
        {{ errors.email }}
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Teléfono</label>
      <Field
        name="phone"
        type="tel"
        class="form-control"
        :class="{ 'is-invalid': errors.phone }"
        rules="required|min:10"
      />
      <div v-if="errors.phone" class="invalid-feedback">
        {{ errors.phone }}
      </div>
    </div>

    <button type="submit" class="btn btn-primary">
      Continuar
    </button>
  </Form>
</template>

<script setup>
import { Form, Field } from 'vee-validate'

const onSubmit = (values) => {
  // Handle form submission
  console.log(values)
}
</script>
```

---

## 🚀 Build y Despliegue

### Vite Configuration

```javascript
// vite.config.js
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  build: {
    outDir: '../public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: 'src/main.js'
    }
  },
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: 'http://localhost:8080',
        changeOrigin: true
      }
    }
  }
})
```

### Package.json Scripts

```json
{
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview",
    "watch": "vite build --watch"
  }
}
```

---

## 🔧 Configuración de Desarrollo

### Environment Variables

```bash
# .env.development
VITE_API_URL=http://localhost:8080/api
VITE_APP_NAME=JamWithJamie
VITE_DEBUG=true

# .env.production
VITE_API_URL=https://api.jamwithjamie.com/api
VITE_APP_NAME=JamWithJamie
VITE_DEBUG=false
```

### Hot Module Replacement

Vite proporciona HMR automático para:
- Cambios en componentes Vue
- Actualizaciones de estilos CSS/SCSS
- Modificaciones de JavaScript
- Cambios en archivos de configuración

---

*Documentación actualizada: Septiembre 2025*