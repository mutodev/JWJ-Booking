<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Custom Service</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <!-- Name -->
            <div class="mb-3">
              <label for="customServiceEditName" class="form-label">Name</label>
              <input
                type="text"
                class="form-control"
                id="customServiceEditName"
                v-model="name"
                required
                placeholder="Enter service name"
              />
              <small class="text-danger small">{{ nameError }}</small>
            </div>

            <!-- Detail -->
            <div class="mb-3">
              <label for="customServiceEditDetail" class="form-label">Detail</label>
              <textarea
                id="customServiceEditDetail"
                class="form-control"
                v-model="detail"
                placeholder="Enter service detail"
                rows="3"
              ></textarea>
              <small class="text-danger small">{{ detailError }}</small>
            </div>

            <!-- Price -->
            <div class="mb-3">
              <label for="customServiceEditPrice" class="form-label">Price</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input
                  type="number"
                  class="form-control"
                  id="customServiceEditPrice"
                  v-model.number="price"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                />
              </div>
              <small class="text-danger small">{{ priceError }}</small>
            </div>

            <!-- Active -->
            <div class="mb-3 form-check">
              <input
                type="checkbox"
                class="form-check-input"
                id="customServiceEditActive"
                v-model="is_active"
              />
              <label class="form-check-label" for="customServiceEditActive">
                Active
              </label>
            </div>

            <!-- Auditoría -->
            <div v-if="data?.created_by || data?.updated_by" class="small text-muted">
              <div v-if="data.created_by">Created by: {{ data.created_by }}</div>
              <div v-if="data.updated_by">Last updated by: {{ data.updated_by }}</div>
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
import { ref, watch } from "vue";
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
    .required("Name is required")
    .min(2, "Minimum 2 characters")
    .max(255, "Maximum 255 characters"),
  detail: yup.string().nullable(),
  price: yup
    .number()
    .typeError("Price is required")
    .required("Price is required")
    .min(0, "Price cannot be negative"),
  is_active: yup.boolean(),
});

const { handleSubmit, setValues, resetForm } = useForm({
  validationSchema: schema,
});

const { value: name, errorMessage: nameError } = useField("name");
const { value: detail, errorMessage: detailError } = useField("detail");
const { value: price, errorMessage: priceError } = useField("price");
const { value: is_active } = useField("is_active");

const loading = ref(false);

watch(
  () => props.data,
  (newData) => {
    if (newData) {
      setValues({
        name: newData.name,
        detail: newData.detail ?? "",
        price: parseFloat(newData.price) || 0,
        is_active: !!newData.is_active,
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
  loading.value = true;
  try {
    await api.put(`/custom-services/${props.data.id}`, values);
    emit("saved");
    closeModal();
  } finally {
    loading.value = false;
  }
});
</script>
