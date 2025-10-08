<template>
  <div class="container py-4">
    <!-- Título con ícono -->
    <div class="d-flex align-items-center mb-3">
      <i class="bi bi-people-fill fs-4 me-2 text-success"></i>
      <h4 class="mb-0">Number of children attending</h4>
    </div>

    <!-- Botones dinámicos -->
    <div v-if="kidsOptions.length" class="d-flex flex-wrap gap-3">
      <template v-for="option in kidsOptions" :key="option.id">
        <div class="form-check-container">
          <input
            type="radio"
            class="btn-check"
            :id="option.id"
            :value="option"
            v-model="selected"
          />
          <label class="btn custom-age-btn" :for="option.id">
            {{ option.label }}
          </label>
        </div>
      </template>

      <!-- Botón fijo 40+ kids -->
      <div class="form-check-container">
        <input
          type="radio"
          class="btn-check"
          id="kids40plus"
          :value="{ id: '40plus', label: '40+ kids' }"
          v-model="selected"
        />
        <label class="btn custom-age-btn" for="kids40plus">
          40+ kids
        </label>
      </div>
    </div>

    <!-- Input para cantidad específica cuando es 40+ -->
    <div v-if="selected?.id === '40plus'" class="mt-4">
      <div class="input-group" style="max-width: 300px;">
        <span class="input-group-text">
          <i class="bi bi-123"></i>
        </span>
        <input
          type="number"
          class="form-control"
          placeholder="Enter number of kids (41+)"
          v-model.number="customKidsCount"
          min="41"
        />
      </div>
      <small class="text-muted mt-1 d-block">Please enter the exact number of children (41 or more)</small>
      <div v-if="customCountError" class="text-danger small mt-1">
        {{ customCountError }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from "vue";
import api from "@/services/axios";
import * as yup from "yup";

const props = defineProps({
  service: {
    type: String,
    required: true,
  },
  active: {
    type: Boolean,
    default: false,
  },
  kids: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["setData"]);

const kidsOptions = ref([]);
const selected = ref(null);
const customKidsCount = ref(null);
const customCountError = ref("");

// Yup schema for validation
const customCountSchema = yup.number()
  .integer("Must be a whole number")
  .min(41, "For 41+ children option, minimum 41 children required")
  .required("Please enter the number of children");

// Computed property to check if form is valid
const isValidSelection = computed(() => {
  if (!selected.value) return false;

  if (selected.value.id === '40plus') {
    try {
      customCountSchema.validateSync(customKidsCount.value);
      customCountError.value = "";
      return true;
    } catch (error) {
      customCountError.value = error.message;
      return false;
    }
  }

  return true;
});

async function loadKidsOptions() {
  if (!props.active || !props.service) return;
  const { data } = await api.get(`/home/range-kids/${props.service}`);

  kidsOptions.value = data.map((opt) => ({
    ...opt,
    label: `Ages ${opt.min_age}–${opt.max_age}`,
  }));

  // Restore selected kids if it exists
  if (props.kids) {
    if (props.kids.type === 'custom') {
      selected.value = { id: '40plus', label: '40+ kids' };
      customKidsCount.value = props.kids.count || null;
    } else if (props.kids.type === 'range') {
      const foundOption = kidsOptions.value.find(opt => opt.id === props.kids.id);
      if (foundOption) {
        selected.value = foundOption;
      }
    }
  }
}

function emitKidsData() {
  if (!selected.value) {
    emit("setData", { kids: null });
    return;
  }

  let kidsData;
  if (selected.value.id === '40plus') {
    kidsData = {
      ...selected.value,
      type: 'custom',
      count: customKidsCount.value,
      isValid: isValidSelection.value
    };
  } else {
    kidsData = {
      ...selected.value,
      type: 'range',
      isValid: true
    };
  }

  emit("setData", { kids: kidsData });
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

watch(selected, (newVal) => {
  if (newVal?.id !== '40plus') {
    customKidsCount.value = null;
  }
  emitKidsData();
});

watch(customKidsCount, () => {
  if (selected.value?.id === '40plus') {
    emitKidsData();
  }
});
</script>

<style scoped>
.form-check-container {
  position: relative;
}

.custom-age-btn {
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

.custom-age-btn:hover {
  background-color: #f3f4f6 !important;
  border-color: #9ca3af !important;
  color: #4b5563 !important;
  transform: translateY(-2px) !important;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15) !important;
}

.btn-check:checked + .custom-age-btn {
  background-color: #FF74B7 !important;
  border-color: #FF74B7 !important;
  color: black !important;
  transform: translateY(-2px) !important;
  box-shadow: 0 4px 12px rgba(255, 116, 183, 0.4) !important;
}

.btn-check:checked + .custom-age-btn:hover {
  background-color: #FF74B7 !important;
  border-color: #FF74B7 !important;
  color: black !important;
}

.input-group-text {
  background-color: #f8f9fa;
  border-color: #d1d5db;
  color: #6b7280;
}

.form-control {
  border-color: #d1d5db;
}

.form-control:focus {
  border-color: #FF74B7;
  box-shadow: 0 0 0 0.2rem rgba(255, 116, 183, 0.25);
}
</style>
