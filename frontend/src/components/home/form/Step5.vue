<template>
  <div class="container py-4">
    <!-- Título con ícono -->
    <div class="d-flex align-items-center mb-3">
      <img
        src="https://via.placeholder.com/32x32?text=🎵"
        alt="Icon"
        class="me-2"
      />
      <h4 class="mb-0">Choose the duration</h4>
    </div>

    <!-- Botones radio (solo uno activo) -->
    <div class="d-flex flex-wrap gap-2">
      <div v-for="(duration, index) in durations" :key="index">
        <input
          type="radio"
          class="btn-check"
          name="durationOptions"
          :id="'duration-' + index"
          :value="duration.value"
          v-model="selectedDuration"
        />
        <label class="btn btn-outline-secondary" :for="'duration-' + index">
          {{ duration.label }}
        </label>
      </div>
    </div>

    <!-- Debug -->
    <p class="mt-3 text-muted">Selected: {{ selectedDuration }}</p>
  </div>
</template>

<script setup>
import { ref } from "vue";

const selectedDuration = ref("");

const durations = [
  { value: "45", label: "45 minutes" },
  { value: "60", label: "60 minutes" },
  { value: "90", label: "90 minutes" },
  { value: "120", label: "120 minutes" },
];
</script>

<style scoped>
.btn-outline-secondary {
  background-color: #f8f9fa;
  color: #212529;
  border-radius: 8px;
  min-width: 120px;
  text-align: center;
}

.btn-check:checked + .btn-outline-secondary {
  background-color: #495057;
  color: #fff;
  border-color: #495057;
}
</style>
