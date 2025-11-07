<template>
  <div class="container py-4">
    <!-- Título -->
    <div class="d-flex align-items-center mb-4">
      <i class="bi bi-music-note-beamed fs-3 me-2 text-success"></i>
      <h2 class="mb-0">Choose your jam</h2>
    </div>

    <!-- Grid de tarjetas -->
    <div v-if="services.length" class="services-grid">
      <div
        v-for="service in services"
        :key="service.id"
        class="service-card"
        :class="{ 'service-card--selected': selectedService?.id === service.id }"
        @click="selectService(service)"
      >
        <!-- Imagen -->
        <div class="service-card__image-wrapper">
          <img
            :src="service.img || '/img/default.jpg'"
            class="service-card__image"
            :alt="service.name"
          />
        </div>

        <!-- Contenido -->
        <div class="service-card__content">
          <!-- Título -->
          <h3 class="service-card__title">{{ service.name }}</h3>

          <!-- Grid de información 2x2 -->
          <div class="service-info-grid">
            <!-- Fila 1 -->
            <div class="service-info-item">
              <i class="bi bi-people-fill service-info-item__icon"></i>
              <span class="service-info-item__text">
                {{ service.performers_count }} performer<span v-if="service.performers_count > 1">s</span>
              </span>
            </div>
            <div class="service-info-item">
              <i class="bi bi-cake2 service-info-item__icon"></i>
              <span class="service-info-item__text">Ages {{ service.range_age }}</span>
            </div>

            <!-- Fila 2 -->
            <div class="service-info-item">
              <i class="bi bi-clock service-info-item__icon"></i>
              <span class="service-info-item__text">
                {{ service.duration_hours || service.min_duration_hours || 1 }} minutes
              </span>
            </div>
            <div class="service-info-item">
              <i class="bi bi-currency-dollar service-info-item__icon"></i>
              <span class="service-info-item__text">${{ service.amount }}</span>
            </div>
          </div>

          <!-- Descripción -->
          <p class="service-card__description" v-if="service.notes">
            {{ service.notes }}
          </p>

          <!-- Botón de selección -->
          <button
            class="service-card__button"
            :class="{ 'service-card__button--selected': selectedService?.id === service.id }"
          >
            <i v-if="selectedService?.id === service.id" class="bi bi-check-circle-fill me-2"></i>
            <span>{{ selectedService?.id === service.id ? 'Selected' : 'Select' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Mensaje si no hay servicios -->
    <div v-else class="text-muted">No services available</div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import api from "@/services/axios";

const props = defineProps({
  metropolitanArea: {
    type: String,
    required: true,
  },
  active: {
    type: Boolean,
    default: false,
  },
  service: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["setData"]);

const services = ref([]);
const selectedService = ref(null);

async function loadServices() {
  if (!props.metropolitanArea) return;

  try {
    const response = await api.get(`/home/services/${props.metropolitanArea}`);
    const servicesList = Array.isArray(response) ? response : (response?.data || []);
    services.value = servicesList;

    // Restore selected service if it exists in the loaded services
    if (props.service) {
      const foundService = servicesList.find(s => s.id === props.service.id);
      if (foundService) {
        selectedService.value = foundService;
      }
    }
  } catch (error) {
    console.error('Error loading services:', error);
  }
}

function selectService(service) {
  selectedService.value = service;
  emitData();
}

function emitData() {
  if (!selectedService.value) {
    emit("setData", { service: null });
    return;
  }

  emit("setData", { service: selectedService.value });
}

watch(
  () => [props.metropolitanArea, props.active],
  ([metropolitanArea, active]) => {
    if (active && metropolitanArea) {
      loadServices();
    }
  },
  { immediate: true }
);

// Watch for service prop changes to maintain selection
watch(
  () => props.service,
  (service) => {
    if (service && services.value.length > 0) {
      const foundService = services.value.find(s => s.id === service.id);
      if (foundService) {
        selectedService.value = foundService;
      }
    } else if (!service) {
      selectedService.value = null;
    }
  },
  { immediate: true }
);
</script>

<style scoped>
/* Grid de servicios - Responsive */
.services-grid {
  display: grid;
  gap: 30px;
  grid-template-columns: 1fr;
}

/* Desktop: 2 columnas */
@media (min-width: 769px) {
  .services-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Tablet: 2 columnas más estrechas */
@media (min-width: 481px) and (max-width: 768px) {
  .services-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
  }
}

/* Mobile: 1 columna */
@media (max-width: 480px) {
  .services-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }
}

/* Card de servicio */
.service-card {
  background: white;
  border-radius: 10px;
  border: 2px solid #e5e7eb;
  overflow: hidden;
  transition: all 0.3s ease;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

/* Estado normal hover */
.service-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

/* Estado seleccionado */
.service-card--selected {
  border-color: #10b981;
  background: #f0fdf4;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.service-card--selected:hover {
  box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
}

/* Imagen */
.service-card__image-wrapper {
  width: 100%;
  aspect-ratio: 16 / 9;
  overflow: hidden;
  background: #f3f4f6;
}

.service-card__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.service-card:hover .service-card__image {
  transform: scale(1.05);
}

/* Contenido de la card */
.service-card__content {
  padding: 20px;
}

/* Título */
.service-card__title {
  font-size: 1.5rem;
  font-weight: 700;
  text-align: center;
  margin: 0 0 20px 0;
  color: #1f2937;
}

/* Grid de información 2x2 */
.service-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px 16px;
  margin-bottom: 16px;
}

.service-info-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.service-info-item__icon {
  font-size: 1.25rem;
  color: #10b981;
  flex-shrink: 0;
}

.service-info-item__text {
  font-size: 0.95rem;
  color: #4b5563;
  font-weight: 500;
}

/* Descripción */
.service-card__description {
  font-size: 0.95rem;
  line-height: 1.6;
  color: #6b7280;
  margin: 0 0 20px 0;
  text-align: justify;
}

/* Botón de selección */
.service-card__button {
  width: 100%;
  padding: 12px;
  border: 2px solid #10b981;
  background: white;
  color: #10b981;
  font-weight: 600;
  font-size: 1rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.service-card__button:hover {
  background: #10b981;
  color: white;
}

.service-card__button--selected {
  background: #10b981;
  color: white;
}

.service-card__button--selected:hover {
  background: #059669;
  border-color: #059669;
}
</style>
