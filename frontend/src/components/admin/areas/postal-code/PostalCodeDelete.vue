<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Delete Zip Code</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <div class="mb-3">
              <label for="zipcode" class="form-label">Zip Code</label>
              <input
                type="text"
                class="form-control"
                id="zipcode"
                v-model="zipcode"
                required
                placeholder="Enter zipcode"
              />
              <span class="text-ligth">
                Write the zipcode <b>{{ data.zipcode }}</b> to perform the deletion.
              </span>
              <br />
              <small class="text-danger">{{ zipcode_error }}</small>
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

// Schema reactivo que usa el valor actualizado de data
const schema = computed(() =>
  yup.object({
    zipcode: yup
      .string()
      .required("Is required")
      .oneOf([data.value?.zipcode], `The Zip code must be ${data.value?.zipcode || ""}`),
  })
);

const { handleSubmit, resetForm } = useForm({
  validationSchema: schema,
});
const { value: zipcode, errorMessage: zipcode_error } = useField("zipcode");

const closeModal = () => {
  emit("close");
};

const submitForm = handleSubmit(async () => {
  await api.delete(`/zipcodes/${props.data.id}`);
  resetForm({
    values: {
      zipcode: "",
    },
  });

  emit("saved", true);
  closeModal();
});

onMounted(() => {
  if (props.show) {
    resetForm({
      values: {
        zipcode: "",
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
