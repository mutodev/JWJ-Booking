<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Delete Jam Type</h5>
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
              <span class="text-muted">
                Write the name <b>{{ data.name }}</b> to confirm deletion.
              </span>
              <br />
              <small class="text-danger">{{ name_error }}</small>
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
            class="btn btn-danger"
            @click="submitForm"
            :disabled="loading"
          >
            <i class="bi bi-trash"></i>
            Delete
          </button>
        </div>
      </div>
    </div>

    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
import { useForm, useField } from "vee-validate";
import { watch, ref, onMounted, computed } from "vue";
import api from "@/services/axios";
import * as yup from "yup";

const data = ref({});
const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
  data: {
    type: Object,
    default: () => ({}),
  },
});

watch(
  () => props.data,
  (newData) => {
    data.value = { ...newData };
  },
  { deep: true, immediate: true }
);

// Schema reactivo con validación de nombre exacto
const schema = computed(() =>
  yup.object({
    name: yup
      .string()
      .required("Name is required")
      .oneOf([data.value?.name], `The name must be ${data.value?.name || ""}`),
  })
);

const { handleSubmit, resetForm } = useForm({
  validationSchema: schema,
});
const { value: name, errorMessage: name_error } = useField("name");

const closeModal = () => {
  emit("close");
};

const submitForm = handleSubmit(async () => {
  await api.delete(`/services/${props.data.id}`);
  resetForm({
    values: {
      name: "",
    },
  });

  emit("saved", true);
  closeModal();
});

onMounted(() => {
  if (props.show) {
    resetForm({
      values: {
        name: "",
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
