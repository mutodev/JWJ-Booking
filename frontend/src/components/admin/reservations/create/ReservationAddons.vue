<template>
  <div class="row justify-content-center">
    <div class="col-10">Addons</div>
  </div>
  <div class="d-flex flex-row gap-2 flex-wrap justify-content-center mt-3">
    <div
      v-for="addon in listAddons"
      :key="addon.id"
      class="card addon-card p-2 text-start"
      :class="{
        'selected-card': selectedAddons.some((a) => a.id === addon.id),
      }"
      @click="toggleSelect(addon)"
    >
      <!-- Name -->
      <div class="fw-semibold small mb-1">
        {{ addon.name }}
      </div>

      <!-- Description -->
      <p class="text-muted mb-2 xsmall">{{ addon.description }}</p>

      <!-- Price & Duration -->
      <div class="d-flex justify-content-between align-items-center">
        <div class="text-center w-50 text-primary fw-bold">
          <i class="bi bi-cash-coin me-1"></i>${{ addon.base_price }}
        </div>
        <div class="text-muted small d-flex align-items-center gap-1">
          <i class="bi bi-clock"></i>{{ addon.estimated_duration_minutes }} min
        </div>
      </div>

      <!-- Checkmark overlay -->
      <div
        v-if="selectedAddons.some((a) => a.id === addon.id)"
        class="checkmark"
      >
        <i class="bi bi-check-circle-fill"></i>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";

const props = defineProps({
  addons: { type: Array, default: () => [] },
  multiple: { type: Boolean, default: true },
});

const emit = defineEmits(["setData"]);

const listAddons = ref([]);
const selectedAddons = ref([]);

watch(
  () => props.addons,
  (val) => (listAddons.value = [...val]),
  { immediate: true }
);

const toggleSelect = (addon) => {
  if (props.multiple) {
    if (selectedAddons.value.some((a) => a.id === addon.id)) {
      selectedAddons.value = selectedAddons.value.filter(
        (a) => a.id !== addon.id
      );
    } else {
      selectedAddons.value.push(addon);
    }
  } else {
    selectedAddons.value = selectedAddons.value.some((a) => a.id === addon.id)
      ? []
      : [addon];
  }

  emit("setData", { addons: [...selectedAddons.value] });
};
</script>

<style scoped>
.addon-card {
  width: 160px;
  min-height: 110px;
  border-radius: 0.5rem;
  border: 1px solid #dee2e6;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  font-size: 0.8rem;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
  position: relative;
}
.addon-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}
.selected-card {
  border-color: #0d6efd;
  background-color: #e7f1ff;
  box-shadow: 0 0 10px rgba(13, 110, 253, 0.3);
}
.xsmall {
  font-size: 0.7rem;
}
.checkmark {
  position: absolute;
  top: 5px;
  right: 5px;
  font-size: 1rem;
  color: #0d6efd;
}
</style>
