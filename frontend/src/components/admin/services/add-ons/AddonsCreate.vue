<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create Addon</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <!-- Nombre -->
            <div class="mb-3">
              <label for="name" class="form-label">Name</label>
              <input
                type="text"
                class="form-control"
                id="name"
                v-model="name"
                placeholder="Enter Addon Name"
              />
              <small class="text-danger">{{ nameError }}</small>
            </div>

            <!-- Descripción -->
            <div class="mb-3">
              <label for="description" class="form-label">Description</label>
              <textarea
                id="description"
                class="form-control"
                v-model="description"
                placeholder="Enter Addon Description"
                rows="3"
              ></textarea>
              <small class="text-danger">{{ descriptionError }}</small>
            </div>

            <div class="row">
              <!-- Precio -->
              <div class="col-md-6 mb-3">
                <label for="base_price" class="form-label"
                  >Base Price (USD)</label
                >
                <input
                  type="number"
                  step="0.01"
                  class="form-control"
                  id="base_price"
                  v-model="base_price"
                  placeholder="Enter Base Price"
                />
                <small class="text-danger">{{ basePriceError }}</small>
              </div>

              <!-- Duración -->
              <div class="col-md-6 mb-3">
                <label for="estimated_duration_minutes" class="form-label"
                  >Estimated Duration (minutes)</label
                >
                <input
                  type="number"
                  class="form-control"
                  id="estimated_duration_minutes"
                  v-model="estimated_duration_minutes"
                  placeholder="Enter Duration"
                />
                <small class="text-danger">{{ durationError }}</small>
              </div>
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
});

const loading = ref(false);

const schema = yup.object({
  name: yup
    .string()
    .required("Addon name is required")
    .max(100, "Maximum 100 characters"),
  description: yup.string().nullable().max(255, "Maximum 255 characters"),
  base_price: yup
    .number()
    .typeError("Base price must be a number")
    .required("Base price is required")
    .min(0, "Price must be >= 0"),
  estimated_duration_minutes: yup
    .number()
    .typeError("Duration must be a number")
    .required("Duration is required")
    .min(1, "Must be at least 1 minute"),
});

const { handleSubmit, resetForm } = useForm({
  validationSchema: schema,
});

const { value: name, errorMessage: nameError } = useField("name");
const { value: description, errorMessage: descriptionError } =
  useField("description");
const { value: base_price, errorMessage: basePriceError } =
  useField("base_price");
const { value: estimated_duration_minutes, errorMessage: durationError } =
  useField("estimated_duration_minutes");

// Reinicia valores al abrir la modal
watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      resetForm({
        values: {
          name: "",
          description: "",
          base_price: null,
          estimated_duration_minutes: null,
        },
      });
    }
  }
);

const closeModal = () => {
  emit("close");
};

const submitForm = handleSubmit(async (values) => {
  try {
    loading.value = true;
    // Forzamos is_active en true al crear
    await api.post(`/addons`, { ...values, is_active: true });
    emit("saved", true);
    closeModal();
  } catch (error) {
    console.error(error);
  } finally {
    loading.value = false;
  }
});

onMounted(() => {
  if (props.show) {
    resetForm({
      values: {
        name: "",
        description: "",
        base_price: null,
        estimated_duration_minutes: null,
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
