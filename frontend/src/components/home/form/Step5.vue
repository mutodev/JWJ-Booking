<template>
  <div class="container py-4">
    <!-- Título con ícono -->
    <div class="d-flex align-items-center mb-4">
      <i class="bi bi-clock fs-4 me-2 text-success"></i>
      <h4 class="mb-0">Choose the duration</h4>
    </div>

    <!-- Botones dinámicos -->
    <div v-if="hoursOptions.length" class="d-flex flex-wrap gap-3">
      <template v-for="option in hoursOptions" :key="option.id">
        <div class="form-check-container">
          <input
            type="radio"
            class="btn-check"
            :id="option.id"
            :value="option"
            v-model="selected"
          />
          <label class="btn custom-hour-btn" :for="option.id">
            {{ option.label }}
          </label>
        </div>
      </template>
    </div>

    <!-- Mensaje si no hay opciones -->
    <div v-else class="text-muted">No duration options available</div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import api from "@/services/axios";

const props = defineProps({
  service: {
    type: String,
    required: true,
  },
  active: {
    type: Boolean,
    default: false,
  },
  hours: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["setData"]);

const hoursOptions = ref([]);
const selected = ref(null);

async function loadHoursOptions() {
  if (!props.active || !props.service) return;
  const { data } = await api.get(`/home/hours/${props.service}`);

  hoursOptions.value = data.map((opt) => ({
    ...opt,
    label: `${opt.hours} hour${opt.hours > 1 ? 's' : ''}`,
  }));

  // Restore selected hours if it exists
  if (props.hours) {
    const foundOption = hoursOptions.value.find(opt => opt.id === props.hours.id);
    if (foundOption) {
      selected.value = foundOption;
    }
  }
}

function emitHoursData() {
  if (!selected.value) {
    emit("setData", { hours: null });
    return;
  }

  const hoursData = {
    ...selected.value,
    isValid: true
  };

  emit("setData", { hours: hoursData });
}

watch(
  () => props.active,
  (active) => {
    if (active) {
      loadHoursOptions();
    }
  },
  { immediate: true }
);

watch(selected, () => {
  emitHoursData();
});
</script>

<style scoped>
.form-check-container {
  position: relative;
}

.custom-hour-btn {
  background-color: #f8f9fa !important;
  border: 2px solid #d1d5db !important;
  color: #6b7280 !important;
  font-weight: 600 !important;
  padding: 12px 20px !important;
  border-radius: 8px !important;
  transition: all 0.2s ease !important;
  cursor: pointer !important;
  min-width: 120px !important;
  text-align: center !important;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
}

.custom-hour-btn:hover {
  background-color: #f3f4f6 !important;
  border-color: #9ca3af !important;
  color: #4b5563 !important;
  transform: translateY(-2px) !important;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15) !important;
}

.btn-check:checked + .custom-hour-btn {
  background-color: #6b7280 !important;
  border-color: #6b7280 !important;
  color: white !important;
  transform: translateY(-2px) !important;
  box-shadow: 0 4px 12px rgba(107, 114, 128, 0.4) !important;
}

.btn-check:checked + .custom-hour-btn:hover {
  background-color: #4b5563 !important;
  border-color: #4b5563 !important;
}
</style>
