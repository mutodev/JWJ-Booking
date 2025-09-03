<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create Zip code</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <div class="mb-3">
              <label for="zipcode" class="form-label">zipcode</label>
              <input
                type="text"
                class="form-control"
                id="zipcode"
                v-model="zipcode"
                required
                placeholder="Enter zipcode"
              />
              <small class="text-danger">{{ zipcode_error }}</small>
            </div>

            <div class="mb-3">
              <label for="city_id" class="form-label">City</label>
              <select
                class="form-select"
                id="city_id"
                v-model="city_id"
                required
              >
                <option value="" disabled>Select a city</option>
                <option
                  v-for="item in dataCities"
                  :key="item.id"
                  :value="item.id"
                >
                  {{ item.name }}
                </option>
              </select>
              <small class="text-danger">{{
                city_id_error
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
  cities: {
    type: Array,
    default: () => [],
  },
});

const dataCities = ref([]);
watch(
  () => props.cities,
  (newData) => {
    dataCities.value = [...newData];
  },
  { deep: true, immediate: true }
);

const schema = yup.object({
  zipcode: yup
    .string()
    .required("Zipcode is required")
    .matches(/^\d{5}(-\d{4})?$/, "Zipcode must be valid (5 or 9 digits)")
    .max(10, "Zipcode must not exceed 10 characters"),
  city_id: yup
    .string()
    .required("Is required"),
});

const { handleSubmit, resetForm } = useForm({
  validationSchema: schema,
});

const { value: zipcode, errorMessage: zipcode_error } = useField("zipcode");
const { value: city_id, errorMessage: city_id_error } = useField("city_id");

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      // Resetear el formulario cuando la modal se abre
      resetForm({
        values: {
          zipcode: "",
          city_id: ""
        },
      });
    }
  }
);

const closeModal = () => {
  emit("close");
};

const submitForm = handleSubmit(async (values) => {
  await api.post(`/zipcodes`, values);
  emit("saved", true);
  closeModal();
});

onMounted(() => {
  if (props.show) {
    resetForm({
      values: {
        zipcode: "",
        city_id: ""
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
