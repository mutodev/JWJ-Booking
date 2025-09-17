<template>
  <div class="container py-4">
    <!-- Título con icono -->
    <div class="d-flex align-items-center mb-4">
      <i class="bi bi-music-note-beamed fs-3 me-2 text-success"></i>
      <h3 class="mb-0">Select your add-on services</h3>
    </div>

    <!-- Grid de cards con checkbox -->
    <div class="row g-4">
      <div
        v-for="(service, index) in services"
        :key="index"
        class="col-12 col-md-6 col-lg-4"
      >
        <input
          type="checkbox"
          class="btn-check"
          :id="'service-' + index"
          :value="service.value"
          v-model="selectedServices"
        />
        <label
          class="card h-100 shadow-sm border-0 service-card"
          :for="'service-' + index"
        >
          <img :src="service.image" class="card-img-top" :alt="service.title" />
          <div class="card-body">
            <h6 class="card-title mb-1">{{ service.title }}</h6>
            <p class="card-text text-muted small mb-0">{{ service.extra }}</p>
          </div>
        </label>
      </div>
    </div>

    <!-- Debug -->
    <p class="mt-3 text-muted">Selected: {{ selectedServices }}</p>
  </div>
</template>

<script setup>
import { ref } from "vue";

const selectedServices = ref([]);

const services = [
  {
    value: "jukebox-1h",
    title: "Jukebox: Family Friendly Live Background Music",
    extra: "+1 Hour",
    image: "https://via.placeholder.com/300x200?text=Jukebox+1h",
  },
  {
    value: "jukebox-2h",
    title: "Jukebox: Family Friendly Live Background Music",
    extra: "+2 Hour",
    image: "https://via.placeholder.com/300x200?text=Jukebox+2h",
  },
  {
    value: "custom-song",
    title: "Custom Song: original composition for the birthday child",
    extra: "",
    image: "https://via.placeholder.com/300x200?text=Custom+Song",
  },
];
</script>

<style scoped>
.service-card {
  cursor: pointer;
  border-radius: 12px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.btn-check:checked + .service-card {
  border: 2px solid #198754; /* borde verde al seleccionar */
  box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.25);
  transform: translateY(-3px);
}
</style>
