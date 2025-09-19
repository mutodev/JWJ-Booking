<template>
  <div class="container py-4">
    <!-- Título con ícono -->
    <div class="d-flex align-items-center mb-3">
      <i class="bi bi-people-fill fs-4 me-2 text-success"></i>
      <h4 class="mb-0">Number of children attending</h4>
    </div>

    <!-- Botones dinámicos -->
    <div v-if="kidsOptions.length" class="btn-group flex-wrap" role="group">
      <template v-for="option in kidsOptions" :key="option.id">
        <input
          type="radio"
          class="btn-check"
          :id="option.id"
          :value="option"
          v-model="selected"
        />
        <label class="btn btn-outline-secondary" :for="option.id">
          {{ option.label }}
        </label>
      </template>

      <!-- Botón fijo 40+ kids -->
      <input
        type="radio"
        class="btn-check"
        id="kids40plus"
        :value="{ id: '40plus', label: '40+ kids' }"
        v-model="selected"
      />
      <label class="btn btn-outline-secondary" for="kids40plus">
        40+ kids
      </label>
    </div>
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
});

const emit = defineEmits(["setData"]);

const kidsOptions = ref([]);
const selected = ref(null);

async function loadKidsOptions() {
  selected.value = null;
  emit("setData", { kids: null });
  
  if (!props.active || !props.service) return;
  const { data } = await api.get(`/home/range-kids/${props.service}`);

  kidsOptions.value = data.map((opt) => ({
    ...opt,
    label: `Ages ${opt.min_age}–${opt.max_age}`,
  }));
}

watch(
  () => props.active,
  (active) => {
    if (active) {
      loadKidsOptions();
    }
  },
  { immediate: true }
);

watch(selected, (val) => {
  if (val) {
    emit("setData", { kids: val });
  }
});
</script>

<style scoped>
.btn-outline-secondary {
  background-color: #6c757d;
  color: #fff;
  border: none;
  transition: all 0.2s ease;
}
.btn-outline-secondary:hover {
  background-color: #5a6268;
  color: #fff;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
  transform: translateY(-2px);
}
.btn-check:checked + .btn-outline-secondary {
  transform: translateY(-8px);
  box-shadow: 0 4px 12px rgba(25, 135, 84, 0.4);
}
.btn-group .btn {
  border-radius: 6px !important;
  margin-right: 8px;
  margin-bottom: 8px;
}
</style>
