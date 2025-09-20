<template>
  <div class="container py-4">
    <!-- Título -->
    <div class="d-flex align-items-center mb-4">
      <i class="bi bi-music-note-beamed fs-3 me-2 text-success"></i>
      <h2 class="mb-0">Choose your jam</h2>
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
          @click="selectService(service)"
        >
          <!-- Imagen -->
          <img
            :src="service.img || '/img/default.jpg'"
            class="card-img-top"
            :alt="service.name"
          />

          <!-- Info -->
          <div class="card-body d-flex flex-column">
            <!-- Título + precio -->
            <h5 class="card-title fw-bold">
              {{ service.name }}
            </h5>
            <p class="price mb-2">${{ service.amount }}</p>

            <p class="card-text small text-muted mb-2">
              {{ service.performers_count }} performer<span
                v-if="service.performers_count > 1"
                >s</span
              >
            </p>

            <span class="badge bg-light text-dark mb-2">
              For Ages {{ service.range_age }}
            </span>
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
  county: {
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
  if (!props.county) return;
  const { data } = await api.get(`/home/services/${props.county}`);
  services.value = data;

  // Restore selected service if it exists in the loaded services
  if (props.service) {
    const foundService = data.find(s => s.id === props.service.id);
    if (foundService) {
      selectedService.value = foundService;
    }
  }
}

function selectService(service) {
  selectedService.value = service;
  emit("setData", { service });
}

watch(
  () => [props.county, props.active],
  ([county, active]) => {
    console.log('Step 3 watcher triggered:', { county, active });
    if (active && county) {
      console.log('Loading services for county:', county);
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
.card {
  border-radius: 14px;
  transition: transform 0.25s ease, box-shadow 0.25s ease;
  cursor: pointer;
}
.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 14px rgba(0, 0, 0, 0.12);
}
.selectable-card.selected {
  border: 2px solid rgba(78, 245, 167, 0.45) !important;
  transform: translateY(-8px);
  box-shadow: 0 5px 10px rgba(78, 245, 167, 0.45) !important;
}
.card-img-top {
  height: 180px;
  object-fit: cover;
  border-top-left-radius: 14px;
  border-top-right-radius: 14px;
}
.price {
  font-size: 1.1rem;
  font-weight: 600;
  color: #198754;
}
.badge {
  font-size: 0.75rem;
}
</style>
