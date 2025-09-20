<template>
  <div class="container py-4">
    <!-- Título con icono -->
    <div class="d-flex align-items-center mb-4">
      <i class="bi bi-music-note-beamed fs-3 me-2 text-success"></i>
      <h3 class="mb-0">Select your add-on services</h3>
    </div>


    <!-- Grid de cards con checkbox -->
    <div v-if="addons.length" class="row g-4">
      <div
        v-for="addon in addons"
        :key="addon.id"
        class="col-12 col-md-6 col-lg-4"
      >
        <input
          type="checkbox"
          class="btn-check"
          :id="'addon-' + addon.id"
          :value="addon"
          v-model="selectedAddons"
        />
        <label
          class="card h-100 shadow-sm border-0 service-card"
          :for="'addon-' + addon.id"
        >
          <img
            :src="addon.image || defaultImage"
            class="card-img-top"
            :alt="addon.name"
            @error="handleImageError"
          />
          <div class="card-body">
            <h6 class="card-title mb-1">{{ addon.name }}</h6>
            <p class="card-text text-muted small mb-2">{{ addon.description }}</p>
            <span class="badge" :class="addon.price_type === 'jukebox' ? 'bg-info' : 'bg-secondary'">
              {{ addon.price_type }}
            </span>
          </div>
        </label>
      </div>
    </div>

    <!-- Mensaje si no hay addons -->
    <div v-else class="text-muted text-center">
      <i class="bi bi-info-circle fs-4 mb-2"></i>
      <p>No add-on services available</p>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import api from "@/services/axios";

const props = defineProps({
  active: {
    type: Boolean,
    default: false,
  },
  addons: {
    type: Array,
    default: () => [],
  },
  service: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["setData"]);

const addons = ref([]);
const selectedAddons = ref([]);
const defaultImage = "https://via.placeholder.com/300x200?text=Add-on+Service";

async function loadAddons() {
  if (!props.active) return;

  try {
    const { data } = await api.get("/home/addons");
    addons.value = data;

    // Restore selected addons if they exist
    if (props.addons && props.addons.length > 0) {
      selectedAddons.value = props.addons.filter(savedAddon =>
        addons.value.some(addon => addon.id === savedAddon.id)
      );
    }
  } catch (error) {
    console.error("Error loading addons:", error);
    addons.value = [];
  }
}

function handleImageError(event) {
  event.target.src = defaultImage;
}

function emitAddonsData() {
  const addonsData = {
    addons: selectedAddons.value,
    isValid: true // Los addons no son requeridos
  };

  emit("setData", addonsData);
}

watch(
  () => props.active,
  (active) => {
    if (active) {
      loadAddons();
    }
  },
  { immediate: true }
);

watch(selectedAddons, () => {
  emitAddonsData();
}, { deep: true });
</script>

<style scoped>
.service-card {
  cursor: pointer;
  border-radius: 12px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.service-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
}

.btn-check:checked + .service-card {
  border: 2px solid #198754 !important;
  box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.25);
  transform: translateY(-3px);
}

.card-img-top {
  height: 200px;
  object-fit: cover;
  border-radius: 12px 12px 0 0;
}

.card-body {
  padding: 1.25rem;
}

.card-title {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
}

.card-text {
  font-size: 0.875rem;
  line-height: 1.4;
}
</style>
