<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header bg-danger">
          <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Delete Custom Service</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <div class="modal-delete-warning">
            <i class="bi bi-shield-exclamation"></i>
            <p>This action <strong>cannot be undone</strong>. Type <strong>{{ data?.name }}</strong> below to confirm.</p>
          </div>
          <form @submit.prevent="submitForm">
            <div class="mb-1">
              <label for="customServiceDeleteName" class="form-label">Confirm name</label>
              <input
                type="text"
                class="form-control"
                id="customServiceDeleteName"
                v-model="name"
                placeholder="Type name to confirm"
              />
              <small class="text-danger mt-1 d-block">{{ nameError }}</small>
            </div>
          </form>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            <i class="bi bi-x-circle"></i> Cancel
          </button>
          <button type="button" class="btn btn-danger" @click="submitForm" :disabled="loading">
            <i class="bi bi-trash"></i> Delete
          </button>
        </div>
      </div>
    </div>

    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
import { useForm, useField } from "vee-validate";
import { ref, watch, computed } from "vue";
import api from "@/services/axios";
import * as yup from "yup";

const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
  data: {
    type: Object,
    default: () => ({}),
  },
});

const schema = computed(() =>
  yup.object({
    name: yup
      .string()
      .required("Name is required")
      .oneOf([props.data?.name], `The name must be ${props.data?.name || ""}`),
  })
);

const { handleSubmit, resetForm } = useForm({
  validationSchema: schema,
});
const { value: name, errorMessage: nameError } = useField("name");

const loading = ref(false);

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      resetForm({ values: { name: "" } });
    }
  }
);

const closeModal = () => {
  emit("close");
};

const submitForm = handleSubmit(async () => {
  loading.value = true;
  try {
    await api.delete(`/custom-services/${props.data.id}`);
    emit("saved");
    closeModal();
  } finally {
    loading.value = false;
  }
});
</script>
