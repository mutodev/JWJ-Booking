<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <!-- 🟢 Header -->
        <div class="modal-header">
          <h5 class="modal-title">Edit Service Price</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <!-- 📝 Body -->
        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <!-- Service -->
            <div class="mb-3">
              <label for="service_id" class="form-label">Service</label>
              <select
                class="form-select"
                id="service_id"
                v-model="service_id"
                required
              >
                <option value="" disabled>-- Select Service --</option>
                <option v-for="srv in services" :key="srv.id" :value="srv.id">
                  {{ srv.name }}
                </option>
              </select>
              <small class="text-danger">{{ service_id_error }}</small>
            </div>

            <!-- County -->
            <div class="mb-3">
              <label for="county_id" class="form-label">County</label>
              <select
                class="form-select"
                id="county_id"
                v-model="county_id"
                required
              >
                <option value="" disabled>-- Select County --</option>
                <option v-for="c in counties" :key="c.id" :value="c.id">
                  {{ c.name }}
                </option>
              </select>
              <small class="text-danger">{{ county_id_error }}</small>
            </div>

            <!-- Performers Count -->
            <div class="mb-3">
              <label for="performers_count" class="form-label"
                >Performers Count</label
              >
              <input
                type="number"
                class="form-control"
                id="performers_count"
                v-model="performers_count"
                min="1"
              />
              <small class="text-danger">{{ performers_count_error }}</small>
            </div>

            <!-- Price Type -->
            <div class="mb-3">
              <label for="price_type" class="form-label">Price Type</label>
              <select class="form-select" id="price_type" v-model="price_type">
                <option value="" disabled>-- Select Type --</option>
                <option value="standard">Standard</option>
                <option value="jukebox">Jukebox</option>
              </select>
              <small class="text-danger">{{ price_type_error }}</small>
            </div>

            <!-- Amount -->
            <div class="mb-3">
              <label for="amount" class="form-label">Amount (USD)</label>
              <input
                type="number"
                class="form-control"
                id="amount"
                v-model="amount"
                min="0"
                step="0.01"
              />
              <small class="text-danger">{{ amount_error }}</small>
            </div>

            <!-- Min Duration -->
            <div class="mb-3">
              <label for="min_duration_hours" class="form-label"
                >Min Duration (Hours)</label
              >
              <input
                type="number"
                class="form-control"
                id="min_duration_hours"
                v-model="min_duration_hours"
                min="1"
              />
              <small class="text-danger">{{ min_duration_hours_error }}</small>
            </div>

            <!-- Notes -->
            <div class="mb-3">
              <label for="notes" class="form-label">Notes</label>
              <textarea
                class="form-control"
                id="notes"
                v-model="notes"
                placeholder="Optional notes"
              ></textarea>
              <small class="text-danger">{{ notes_error }}</small>
            </div>

            <!-- Availability -->
            <div class="form-check form-switch mb-3">
              <input
                class="form-check-input"
                type="checkbox"
                id="is_available"
                v-model="is_available"
              />
              <label class="form-check-label" for="is_available">
                Active
              </label>
            </div>
          </form>
        </div>

        <!-- 🔵 Footer -->
        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            <i class="bi bi-arrow-90deg-down"></i> Back
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="submitForm"
            :disabled="loading"
          >
            <i class="bi bi-save"></i> Save Changes
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
  data: { type: Object, default: () => ({}) },
  services: { type: Array, default: () => [] },
  counties: { type: Array, default: () => [] },
});

const loading = ref(false);

// ✅ Esquema de validación con Yup
const schema = yup.object({
  service_id: yup.string().uuid().required("Service is required"),
  county_id: yup.string().required(),
  performers_count: yup.number().min(1, "At least 1").required(),
  price_type: yup
    .string()
    .oneOf(["standard", "jukebox"])
    .required("Type is required"),
  amount: yup.number().min(0, "Must be positive").required(),
  min_duration_hours: yup.number().min(1, "Min 1 hour").required(),
  notes: yup.string().max(255, "Max 255 characters").nullable(),
  is_available: yup.boolean(),
});

const { handleSubmit, setValues, resetForm } = useForm({
  validationSchema: schema,
});

const { value: service_id, errorMessage: service_id_error } =
  useField("service_id");
const { value: county_id, errorMessage: county_id_error } =
  useField("county_id");
const { value: performers_count, errorMessage: performers_count_error } =
  useField("performers_count");
const { value: price_type, errorMessage: price_type_error } =
  useField("price_type");
const { value: amount, errorMessage: amount_error } = useField("amount");
const { value: min_duration_hours, errorMessage: min_duration_hours_error } =
  useField("min_duration_hours");
const { value: notes, errorMessage: notes_error } = useField("notes");
const { value: is_available } = useField("is_available");

// 🎯 Cargar datos cuando se abre el modal
watch(
  () => props.data,
  (newData) => {
    if (newData) {
        console.log(newData);
        
      setValues({
        service_id: newData.service_id,
        county_id: newData.county_id,
        performers_count: newData.performers_count,
        price_type: newData.price_type,
        amount: newData.amount,
        min_duration_hours: newData.min_duration_hours,
        notes: newData.notes,
        is_available: newData.is_available,
      });
    } else {
      resetForm();
    }
  },
  { immediate: true }
);

const closeModal = () => emit("close");

const submitForm = handleSubmit(async (values) => {
  loading.value = true;
  try {
    await api.put(`/service-prices/${props.data.id}`, values);
    emit("saved");
    closeModal();
  } finally {
    loading.value = false;
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
