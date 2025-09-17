<template>
  <div class="container py-4">
    <!-- Título con ícono -->
    <div class="d-flex align-items-center mb-3">
      <img
        src="https://via.placeholder.com/24x24?text=🎉"
        alt="Icon"
        class="me-2"
      />
      <h4 class="mb-0">Number of children attending</h4>
    </div>

    <!-- Botones select -->
    <div class="btn-group" role="group" aria-label="Children attending">
      <input
        type="radio"
        class="btn-check"
        name="childrenOptions"
        id="kids1"
        value="up-to-24"
        v-model="selected"
      />
      <label class="btn btn-outline-secondary" for="kids1">Up to 24 kids</label>

      <input
        type="radio"
        class="btn-check"
        name="childrenOptions"
        id="kids2"
        value="25-40"
        v-model="selected"
      />
      <label class="btn btn-outline-secondary" for="kids2">25–40 kids</label>

      <input
        type="radio"
        class="btn-check"
        name="childrenOptions"
        id="kids3"
        value="40-plus"
        v-model="selected"
      />
      <label class="btn btn-outline-secondary" for="kids3">40+ kids</label>
    </div>

    <!-- Resultado (debug) -->
    <p class="mt-3 text-muted">Selected: {{ selected }}</p>
  </div>
</template>

<script setup>
import { ref } from "vue";

const selected = ref("");
</script>

<style scoped>
.btn-outline-secondary {
  background-color: #6c757d; /* gris oscuro como en la imagen */
  color: #fff;
  border: none;
}
.btn-outline-secondary:hover,
.btn-check:checked + .btn-outline-secondary {
  background-color: #495057; /* un gris más oscuro al seleccionar */
  color: #fff;
}
.btn-group .btn {
  border-radius: 6px !important;
  margin-right: 8px;
}
</style>
