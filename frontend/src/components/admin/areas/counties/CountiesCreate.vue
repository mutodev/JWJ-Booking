<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create County</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <div class="mb-3">
              <label for="name" class="form-label">Name</label>
              <input
                type="text"
                class="form-control"
                id="name"
                v-model="name"
                required
                placeholder="Enter name"
              />
              <small class="text-danger small">{{ name_error }}</small>
            </div>

            <div class="mb-3">
              <label for="area" class="form-label">Metropolitan area</label>
              <select
                class="form-select"
                id="area"
                v-model="metropolitan_area_id"
                required
              >
                <option value="" disabled>Select a area</option>
                <option
                  v-for="area in dataAreas"
                  :key="area.id"
                  :value="area.id"
                >
                  {{ area.name }}
                </option>
              </select>
              <small class="text-danger small">{{
                metropolitan_area_id_error
              }}</small>
            </div>
          </form>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            <i class="bi bi-arrow-90deg-down"></i>
            Back
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="submitForm"
            :disabled="loading"
          >
            <i class="bi bi-save"></i>
            Save
          </button>
        </div>
      </div>
    </div>

    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
import { useForm, useField } from "vee-validate";
import { watch, ref, onMounted } from "vue";
import api from "@/services/axios";
import * as yup from "yup";

const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
  areas: {
    type: Array,
    default: () => [],
  },
});

const dataAreas = ref([]);
watch(
  () => props.areas,
  (newData) => {
    dataAreas.value = [...newData];
  },
  { deep: true, immediate: true }
);

const schema = yup.object({
  name: yup
    .string()
    .required("First name is required")
    .min(2, "Minimum 2 characters")
    .max(30, "Maximum 30 characters"),
  metropolitan_area_id: yup
    .string()
    .required("Metropolitan area name is required"),
});

const { handleSubmit, resetForm } = useForm({
  validationSchema: schema,
});

const { value: name, errorMessage: name_error } = useField("name");
const { value: metropolitan_area_id, errorMessage: metropolitan_area_id_error } = useField("metropolitan_area_id");

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      // Resetear el formulario cuando la modal se abre
      resetForm({
        values: {
          name: "",
          metropolitan_area_id: ""
        },
      });
    }
  }
);

const closeModal = () => {
  emit("close");
};

const submitForm = handleSubmit(async (values) => {
  await api.post(`/counties`, values);
  emit("saved", true);
  closeModal();
});

onMounted(() => {
  if (props.show) {
    resetForm({
      values: {
        name: "",
        metropolitan_area_id: ""
      },
    });
  }
});
</script>
<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
}
.modal-backdrop {
  z-index: 1040;
}
.modal-dialog {
  z-index: 1050;
}
</style>
