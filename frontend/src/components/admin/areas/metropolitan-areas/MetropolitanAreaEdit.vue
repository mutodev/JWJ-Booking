<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Metropolitan</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <div class="mb-3">
              <label for="roleName" class="form-label">Name</label>
              <input
                type="text"
                class="form-control"
                id="roleName"
                v-model="name"
                required
                placeholder="Enter Name"
              />
              <small class="text-danger small">{{ nameError }}</small>
            </div>
            <div class="mb-3 form-check">
              <input
                type="checkbox"
                class="form-check-input"
                id="roleActive"
                v-model="is_active"
              />
              <label class="form-check-label" for="roleActive"> Active </label>
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
import { watch, ref } from "vue";
import api from "@/services/axios";
import * as yup from "yup";

const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
  data: Object,
});

const schema = yup.object({
  name: yup
    .string()
    .required("Role name is required")
    .min(6, "Minimum 6 characters")
    .max(50, "Maximum 50 characters"),
  is_active: yup.boolean(),
});

const { handleSubmit, setValues, resetForm } = useForm({
  validationSchema: schema,
});

const { value: name, errorMessage: nameError } = useField("name");
const { value: is_active } = useField("is_active");

// Watch for changes in props.data
watch(
  () => props.data,
  (newData) => {
    if (newData) {
      setValues({
        name: newData.name,
        is_active: newData.is_active,
      });
    } else {
      resetForm();
    }
  },
  { immediate: true }
);

const closeModal = () => {
  emit("close");
};

const submitForm = handleSubmit(async (values) => {
  await api.put(`/metropolitan-areas/${props.data.id}`, values);
  emit("saved");
  closeModal();
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
