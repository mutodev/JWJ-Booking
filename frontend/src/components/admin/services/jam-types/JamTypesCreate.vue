<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create Jam Types</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <!-- Name -->
            <div class="mb-3">
              <label for="serviceName" class="form-label">Name</label>
              <input
                type="text"
                class="form-control"
                id="serviceName"
                v-model="name"
                required
                placeholder="Enter service name"
              />
              <small class="text-danger small">{{ nameError }}</small>
            </div>

            <!-- Description -->
            <div class="mb-3">
              <label for="serviceDescription" class="form-label"
                >Description</label
              >
              <textarea
                id="serviceDescription"
                class="form-control"
                v-model="description"
                placeholder="Enter service description"
                rows="3"
              ></textarea>
              <small class="text-danger small">{{ descriptionError }}</small>
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
import { watch, onMounted } from "vue";
import api from "@/services/axios";
import * as yup from "yup";

const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
});

const schema = yup.object({
  name: yup
    .string()
    .required("Service name is required")
    .min(2, "Minimum 2 characters")
    .max(100, "Maximum 100 characters"),
  description: yup.string().nullable().max(255, "Maximum 255 characters"),
  is_active: yup.boolean(),
});

const { handleSubmit, resetForm } = useForm({
  validationSchema: schema,
});

const { value: name, errorMessage: nameError } = useField("name");
const { value: description, errorMessage: descriptionError } =
  useField("description");
const { value: is_active } = useField("is_active");

// Resetear formulario al abrir modal
watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      resetForm({
        values: {
          name: "",
          description: "",
          is_active: true,
        },
      });
    }
  }
);

const closeModal = () => {
  emit("close");
};

const submitForm = handleSubmit(async (values) => {
  await api.post(`/services`, values);
  emit("saved", true);
  closeModal();
});

onMounted(() => {
  if (props.show) {
    resetForm({
      values: {
        name: "",
        description: "",
        is_active: true,
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
