<template>
  <div class="container py-4">
    <!-- Título -->
    <div class="d-flex align-items-center mb-4">
      <i class="bi bi-music-note-beamed fs-3 me-2 text-success"></i>
      <h2 class="mb-0">Choose your jam and duration</h2>
    </div>

    <!-- Grid de tarjetas -->
    <div v-if="services.length" class="row g-4">
      <div
        v-for="service in services"
        :key="service.id"
        class="col-12 col-sm-6 col-lg-4"
      >
        <div
          class="card h-100 shadow-sm border-0 selectable-card"
          :class="{ selected: selectedService?.id === service.id }"
        >
          <!-- Imagen -->
          <img
            :src="service.img || '/img/default.jpg'"
            class="card-img-top"
            :alt="service.name"
            @click="selectService(service)"
            style="cursor: pointer;"
          />

          <!-- Info -->
          <div class="card-body d-flex flex-column">
            <!-- Título -->
            <h5 class="card-title fw-bold" @click="selectService(service)" style="cursor: pointer;">
              {{ service.name }}
            </h5>

            <div class="service-details mb-2">
              <p class="card-text small text-muted mb-1">
                <i class="bi bi-people-fill me-1"></i>
                {{ service.performers_count }} performer<span
                  v-if="service.performers_count > 1"
                  >s</span
                >
              </p>

              <p class="card-text small text-muted mb-1" v-if="service.min_duration_hours">
                <i class="bi bi-clock me-1"></i>
                {{ service.min_duration_hours }} hour<span
                  v-if="service.min_duration_hours > 1"
                  >s</span
                > minimum
              </p>

              <p class="card-text small text-muted mb-1" v-if="service.children_count">
                <i class="bi bi-person-hearts me-1"></i>
                Up to {{ service.children_count }} children included
              </p>
            </div>

            <span class="badge bg-light text-dark mb-3">
              For Ages {{ service.range_age }}
            </span>

            <!-- Selector de duración -->
            <div v-if="selectedService?.id === service.id && hoursOptions[service.id]?.length" class="duration-selector mt-auto">
              <p class="small fw-bold text-muted mb-2">
                <i class="bi bi-clock me-1"></i>Select Duration:
              </p>
              <div class="d-flex flex-wrap gap-2">
                <template v-for="option in hoursOptions[service.id]" :key="option.id">
                  <div class="form-check-container">
                    <input
                      type="radio"
                      class="btn-check"
                      :id="`hour-${service.id}-${option.id}`"
                      :value="option"
                      v-model="selectedHours"
                      @click.stop
                    />
                    <label
                      class="btn custom-hour-btn-small"
                      :for="`hour-${service.id}-${option.id}`"
                      @click.stop
                    >
                      {{ option.label }}
                    </label>
                  </div>
                </template>
              </div>
            </div>
          </div>
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
  hours: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["setData"]);

const services = ref([]);
const selectedService = ref(null);
const hoursOptions = ref({});
const selectedHours = ref(null);

async function loadServices() {
  if (!props.metropolitanArea) return;

  try {
    const response = await api.get(`/home/services/${props.metropolitanArea}`);
    // El interceptor ya retorna response.data, así que response puede ser:
    // - Un array directo: [...]
    // - Un objeto con data: {data: [...], message: "..."}
    const servicesList = Array.isArray(response) ? response : (response?.data || []);
    services.value = servicesList;

    // Restore selected service if it exists in the loaded services
    if (props.service) {
      const foundService = servicesList.find(s => s.id === props.service.id);
      if (foundService) {
        selectedService.value = foundService;
        await loadHoursForService(foundService.id);
      }
    }
  } catch (error) {
    console.error('Error loading services:', error);
  }
}

async function loadHoursForService(serviceId) {
  try {
    const response = await api.get(`/home/hours/${serviceId}`);
    const hoursList = Array.isArray(response) ? response : (response?.data || []);

    hoursOptions.value[serviceId] = hoursList.map((opt) => ({
      ...opt,
      label: `${opt.hours} hour${opt.hours > 1 ? 's' : ''}`,
    }));

    // Restore selected hours if it exists
    if (props.hours && hoursOptions.value[serviceId]) {
      const foundOption = hoursOptions.value[serviceId].find(opt => opt.id === props.hours.id);
      if (foundOption) {
        selectedHours.value = foundOption;
      }
    }
  } catch (error) {
    console.error('Error loading hours:', error);
  }
}

async function selectService(service) {
  selectedService.value = service;
  selectedHours.value = null;

  // Load hours for this service
  if (!hoursOptions.value[service.id]) {
    await loadHoursForService(service.id);
  }

  emitData();
}

function emitData() {
  if (!selectedService.value) {
    emit("setData", { service: null, hours: null });
    return;
  }

  const serviceData = selectedService.value;

  if (!selectedHours.value) {
    emit("setData", { service: serviceData, hours: null });
    return;
  }

  const hoursData = {
    ...selectedHours.value,
    isValid: true
  };

  emit("setData", { service: serviceData, hours: hoursData });
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
        loadHoursForService(foundService.id);
      }
    } else if (!service) {
      selectedService.value = null;
      selectedHours.value = null;
    }
  },
  { immediate: true }
);

// Watch for hours selection changes
watch(selectedHours, () => {
  emitData();
});
</script>

<style scoped>
.card {
  border-radius: 14px;
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 14px rgba(0, 0, 0, 0.12);
}
.selectable-card.selected {
  border: 2px solid rgba(255, 116, 183, 0.6) !important;
  transform: translateY(-8px);
  box-shadow: 0 5px 15px rgba(255, 116, 183, 0.4) !important;
}
.card-img-top {
  height: 180px;
  object-fit: cover;
  border-top-left-radius: 14px;
  border-top-right-radius: 14px;
}
.service-details {
  line-height: 1.3;
}

.service-details i {
  color: #6c757d;
  width: 14px;
}
.badge {
  font-size: 0.75rem;
}

.duration-selector {
  border-top: 1px solid #e9ecef;
  padding-top: 0.75rem;
}

.form-check-container {
  position: relative;
}

.custom-hour-btn-small {
  background-color: #f8f9fa !important;
  border: 2px solid #d1d5db !important;
  color: #6b7280 !important;
  font-weight: 600 !important;
  padding: 6px 12px !important;
  border-radius: 6px !important;
  transition: all 0.2s ease !important;
  cursor: pointer !important;
  font-size: 0.875rem !important;
  text-align: center !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}

.custom-hour-btn-small:hover {
  background-color: #f3f4f6 !important;
  border-color: #9ca3af !important;
  color: #4b5563 !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
}

.btn-check:checked + .custom-hour-btn-small {
  background-color: #FF74B7 !important;
  border-color: #FF74B7 !important;
  color: black !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 2px 6px rgba(255, 116, 183, 0.4) !important;
}

.btn-check:checked + .custom-hour-btn-small:hover {
  background-color: #FF74B7 !important;
  border-color: #FF74B7 !important;
  color: black !important;
}
</style>
