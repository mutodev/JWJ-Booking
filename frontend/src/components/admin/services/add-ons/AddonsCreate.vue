<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create New Addon</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <!-- Basic Information Section -->
            <div class="form-section mb-4">
              <h6 class="section-title">
                <i class="bi bi-info-circle me-2"></i>Basic Information
              </h6>

              <!-- Type Addon -->
              <div class="mb-3">
                <label for="type_addon_id" class="form-label required">Addon Type</label>
                <select
                  class="form-select"
                  id="type_addon_id"
                  v-model="type_addon_id"
                  required
                >
                  <option value="">Select addon type</option>
                  <option v-for="type in typeAddons" :key="type.id" :value="type.id">
                    {{ type.name }}
                  </option>
                </select>
                <small class="text-danger small">{{ typeAddonIdError }}</small>
              </div>

              <!-- Addon Name -->
              <div class="mb-3">
                <label for="name" class="form-label required">Name</label>
                <input
                  type="text"
                  class="form-control"
                  id="name"
                  v-model="name"
                  placeholder="Enter addon name"
                  required
                />
                <small class="text-danger small">{{ nameError }}</small>
              </div>
            </div>

            <!-- Pricing Section -->
            <div class="form-section mb-4">
              <h6 class="section-title">
                <i class="bi bi-currency-dollar me-2"></i>Pricing
              </h6>

              <div class="row">
                <!-- Base price -->
                <div class="col-md-6 mb-3">
                  <label for="base_price" class="form-label" :class="{ 'required': !is_referral_service }">Base Price (USD)</label>
                  <input
                    type="number"
                    step="0.01"
                    class="form-control"
                    id="base_price"
                    v-model="base_price"
                    placeholder="0.00"
                    :required="!is_referral_service"
                    :disabled="is_referral_service"
                  />
                  <small class="text-danger small">{{ basePriceError }}</small>
                </div>

                <!-- Duración estimada -->
                <div class="col-md-6 mb-3">
                  <label for="estimated_duration_minutes" class="form-label">
                    Estimated Duration (minutes)
                  </label>
                  <input
                    type="number"
                    class="form-control"
                    id="estimated_duration_minutes"
                    v-model="estimated_duration_minutes"
                    placeholder="Enter duration in minutes"
                    min="1"
                  />
                  <small class="text-danger small">{{ durationError }}</small>
                </div>
              </div>

              <!-- Is Referral Service -->
              <div class="row">
                <div class="col-md-12 mb-3">
                  <div class="form-check form-switch">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="is_referral_service"
                      v-model="is_referral_service"
                    />
                    <label class="form-check-label" for="is_referral_service">
                      <strong>{{ is_referral_service ? 'Referral Service' : 'Regular Addon' }}</strong>
                    </label>
                  </div>
                  <small class="form-text text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Referral services are informational only and don't have a direct price.
                  </small>
                </div>
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
/**
 * Addon Create Modal Component
 *
 * Modal form for creating new addons with validation.
 *
 * @emits close - When modal should be closed
 * @emits saved - When addon is successfully created
 */

import { useForm, useField } from "vee-validate";
import { watch, ref, onMounted } from "vue";
import api from "@/services/axios";
import * as yup from "yup";

// Component props and emits
const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
});

// Loading state
const loading = ref(false);
const typeAddons = ref([]);

/**
 * Yup validation schema for addon creation
 */
const schema = yup.object({
  type_addon_id: yup
    .string()
    .required("Addon type is required"),
  name: yup
    .string()
    .required("Addon name is required")
    .max(100, "Maximum 100 characters"),
  base_price: yup
    .number()
    .typeError("Base price must be a number")
    .when('is_referral_service', {
      is: false,
      then: (schema) => schema.required("Base price is required").min(0, "Price must be >= 0"),
      otherwise: (schema) => schema.nullable().min(0, "Price must be >= 0"),
    }),
  estimated_duration_minutes: yup
    .number()
    .typeError("Duration must be a number")
    .nullable()
    .min(1, "Must be at least 1 minute"),
  is_referral_service: yup
    .boolean()
    .default(false),
});

// Form setup with validation
const { handleSubmit, resetForm } = useForm({
  validationSchema: schema,
});

// Form fields with validation
const { value: type_addon_id, errorMessage: typeAddonIdError } = useField("type_addon_id");
const { value: name, errorMessage: nameError } = useField("name");
const { value: base_price, errorMessage: basePriceError } = useField("base_price");
const { value: estimated_duration_minutes, errorMessage: durationError } = useField("estimated_duration_minutes");
const { value: is_referral_service } = useField("is_referral_service");

/**
 * Obtiene los tipos de addons del backend
 */
const fetchTypeAddons = async () => {
  try {
    const response = await api.get("/type-addons");
    typeAddons.value = response.data;
  } catch (error) {
    console.error('Error loading type addons:', error);
  }
};

// Reinicia valores al abrir la modal
watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      fetchTypeAddons();
      resetForm({
        values: {
          type_addon_id: "",
          name: "",
          base_price: null,
          estimated_duration_minutes: null,
          is_referral_service: false,
        },
      });
    }
  }
);

/**
 * Cierra la modal emitiendo el evento close
 */
const closeModal = () => {
  emit("close");
};

/**
 * Envía el formulario para crear un nuevo addon
 */
const submitForm = handleSubmit(async (values) => {
  try {
    loading.value = true;

    const data = {
      ...values,
      is_active: true,
    };

    await api.post(`/addons`, data);

    emit("saved", true);
    closeModal();
  } catch (error) {
    console.error('Error creating addon:', error);
  } finally {
    loading.value = false;
  }
});

/**
 * Inicialización del componente
 */
onMounted(() => {
  fetchTypeAddons();
});
</script>

