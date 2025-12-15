<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create Service Price</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="submitForm">

            <!-- 🎯 SERVICE & LOCATION -->
            <div class="form-section">
              <h6 class="section-title">
                <i class="bi bi-geo-alt me-2"></i>Service & Location
              </h6>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label required">Service</label>
                  <Multiselect
                    v-model="selectedService"
                    :options="services"
                    label="name"
                    track-by="id"
                    placeholder="Choose a service..."
                    @select="onSelectService"
                    :searchable="true"
                    :show-labels="false"
                    :allow-empty="false"
                  />
                  <small class="text-danger small">{{ errors.service_id }}</small>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label required">County</label>
                  <Multiselect
                    v-model="selectedCounty"
                    :options="counties"
                    label="name"
                    track-by="id"
                    placeholder="Choose a county..."
                    @select="onSelectCounty"
                    :searchable="true"
                    :show-labels="false"
                    :allow-empty="false"
                  />
                  <small class="text-danger small">{{ errors.county_id }}</small>
                </div>
              </div>
            </div>

            <!-- 💰 PRICING DETAILS -->
            <div class="form-section">
              <h6 class="section-title">
                <i class="bi bi-cash-stack me-2"></i>Pricing Details
              </h6>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="amount" class="form-label required">Base Price (USD)</label>
                  <input
                    type="number"
                    class="form-control"
                    id="amount"
                    v-model.number="form.amount"
                    min="0"
                    step="0.01"
                    placeholder="0.00"
                    required
                  />
                  <small class="text-danger small">{{ errors.amount }}</small>
                </div>

                <div class="col-md-6 mb-3">
                  <label for="extra_child_fee" class="form-label required">Extra Child Fee (USD)</label>
                  <input
                    type="number"
                    class="form-control"
                    id="extra_child_fee"
                    v-model.number="form.extra_child_fee"
                    min="0"
                    step="0.01"
                    placeholder="0.00"
                    required
                  />
                  <small class="text-danger small">{{ errors.extra_child_fee }}</small>
                </div>
              </div>
            </div>

            <!-- 👥 PERFORMERS & TARGET -->
            <div class="form-section">
              <h6 class="section-title">
                <i class="bi bi-people me-2"></i>Performers & Target Audience
              </h6>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="performers_count" class="form-label required">Performers Count</label>
                  <input
                    type="number"
                    class="form-control"
                    id="performers_count"
                    v-model.number="form.performers_count"
                    min="1"
                    required
                  />
                  <small class="text-danger small">{{ errors.performers_count }}</small>
                </div>

                <div class="col-md-6 mb-3">
                  <label for="range_age" class="form-label required">Age Range</label>
                  <input
                    type="text"
                    class="form-control"
                    id="range_age"
                    v-model="form.range_age"
                    placeholder="e.g., 3-8 years"
                    required
                  />
                  <small class="text-danger small">{{ errors.range_age }}</small>
                </div>
              </div>
            </div>

            <!-- 👶 CHILDREN RANGES -->
            <div class="form-section">
              <h6 class="section-title">
                <i class="bi bi-people me-2"></i>Children Ranges
              </h6>
              <div class="duration-container">
                <!-- Current ranges display -->
                <div v-if="selectedChildrenRanges.length > 0" class="selected-durations mb-3">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-medium text-secondary">
                      <i class="bi bi-check-circle-fill text-success me-1"></i>
                      {{ selectedChildrenRanges.length }} Range{{ selectedChildrenRanges.length > 1 ? 's' : '' }} Added
                    </span>
                  </div>
                  <div class="duration-tags">
                    <div
                      v-for="(range, index) in selectedChildrenRanges"
                      :key="index"
                      class="duration-tag"
                    >
                      <span class="duration-time">{{ range.min_children }} - {{ range.max_children }} kids</span>
                      <button
                        type="button"
                        class="duration-remove"
                        @click="removeChildrenRange(index)"
                        title="Remove range"
                      >
                        <i class="bi bi-x"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Add new children range -->
                <div class="row align-items-end g-2">
                  <div class="col-md-5">
                    <label for="newRangeMin" class="form-label">Min Children</label>
                    <input
                      type="number"
                      class="form-control"
                      id="newRangeMin"
                      v-model.number="newChildrenRange.min_children"
                      placeholder="1"
                      min="1"
                    />
                  </div>
                  <div class="col-md-5">
                    <label for="newRangeMax" class="form-label">Max Children</label>
                    <input
                      type="number"
                      class="form-control"
                      id="newRangeMax"
                      v-model.number="newChildrenRange.max_children"
                      placeholder="10"
                      min="1"
                    />
                  </div>
                  <div class="col-md-2">
                    <button
                      type="button"
                      class="btn btn-outline-primary w-100"
                      @click="addChildrenRange"
                      :disabled="!isValidChildrenRange"
                    >
                      <i class="bi bi-plus me-1"></i>Add
                    </button>
                  </div>
                </div>
                <small v-if="errors.childrenRanges" class="text-danger small mt-1">{{ errors.childrenRanges }}</small>
              </div>
            </div>

            <!-- ⏰ DURATION OPTIONS -->
            <div class="form-section">
              <h6 class="section-title">
                <i class="bi bi-clock me-2"></i>Available Durations
              </h6>
              <div class="duration-container">
                <!-- Current durations display -->
                <div v-if="selectedDurations.length > 0" class="selected-durations mb-3">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-medium text-secondary">
                      <i class="bi bi-check-circle-fill text-success me-1"></i>
                      {{ selectedDurations.length }} Duration{{ selectedDurations.length > 1 ? 's' : '' }} Added
                    </span>
                  </div>
                  <div class="duration-tags">
                    <div
                      v-for="(duration, index) in selectedDurations"
                      :key="index"
                      class="duration-tag"
                    >
                      <span class="duration-time">{{ duration.minutes }} min</span>
                      <span class="duration-hours">({{ (duration.minutes / 60).toFixed(1) }}h)</span>
                      <button
                        type="button"
                        class="duration-remove"
                        @click="removeDuration(index)"
                        title="Remove duration"
                      >
                        <i class="bi bi-x"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Add new duration -->
                <div class="row align-items-end">
                  <div class="col-md-8">
                    <label for="newDurationMinutes" class="form-label">Add Duration (minutes)</label>
                    <input
                      type="number"
                      class="form-control"
                      id="newDurationMinutes"
                      v-model.number="newDuration.minutes"
                      placeholder="60, 90, 120"
                      min="30"
                      step="15"
                    />
                  </div>
                  <div class="col-md-4">
                    <button
                      type="button"
                      class="btn btn-outline-primary w-100"
                      @click="addDuration"
                      :disabled="!newDuration.minutes || newDuration.minutes < 30"
                    >
                      <i class="bi bi-plus me-1"></i>Add
                    </button>
                  </div>
                </div>
                <small v-if="errors.durations" class="text-danger small mt-1">{{ errors.durations }}</small>
              </div>
            </div>

            <!-- 📝 NOTES -->
            <div class="form-section">
              <h6 class="section-title">
                <i class="bi bi-card-text me-2"></i>Additional Information
              </h6>
              <div class="row">
                <div class="col-md-12 mb-3">
                  <label for="notes" class="form-label required">Additional Notes</label>
                  <textarea
                    class="form-control"
                    id="notes"
                    v-model="form.notes"
                    rows="3"
                    placeholder="Required notes about this service..."
                    required
                  ></textarea>
                  <small v-if="errors.notes" class="text-danger small">{{ errors.notes }}</small>
                </div>
              </div>
            </div>

          </form>
        </div>

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
            <i class="bi bi-save"></i>
            <span v-if="loading" class="spinner-border spinner-border-sm ms-2"></span>
            Save
          </button>
        </div>
      </div>
    </div>

    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from "vue";
import api from "@/services/axios";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";
import * as yup from "yup";

const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
  services: { type: Array, default: () => [] },
  counties: { type: Array, default: () => [] },
});

// Esquema de validación con Yup
const validationSchema = yup.object().shape({
  service_id: yup.string().required("Service is required"),
  county_id: yup.string().required("County is required"),
  performers_count: yup.number()
    .required("Performers count is required")
    .min(1, "At least 1 performer is required")
    .integer("Must be a whole number"),
  amount: yup.number()
    .required("Base price is required")
    .min(0.01, "Price must be greater than 0"),
  extra_child_fee: yup.number()
    .required("Extra child fee is required")
    .min(0, "Fee cannot be negative"),
  range_age: yup.string()
    .required("Age range is required")
    .min(1, "Age range cannot be empty"),
  notes: yup.string()
    .required("Notes are required")
    .min(1, "Notes cannot be empty"),
});

const form = ref({
  service_id: "",
  county_id: "",
  performers_count: 1,
  amount: 0,
  is_available: true,
  notes: "",
  extra_child_fee: 0,
  range_age: "",
});

const selectedService = ref(null);
const selectedCounty = ref(null);
const selectedDurations = ref([]);
const newDuration = ref({ minutes: null });
const selectedChildrenRanges = ref([]);
const newChildrenRange = ref({ min_children: null, max_children: null });
const errors = ref({});
const loading = ref(false);

// Computed para validar rango de niños
const isValidChildrenRange = computed(() => {
  return newChildrenRange.value.min_children &&
         newChildrenRange.value.max_children &&
         newChildrenRange.value.min_children > 0 &&
         newChildrenRange.value.max_children >= newChildrenRange.value.min_children;
});

watch(
  () => props.show,
  (newVal) => {
    if (newVal) resetForm();
  }
);

const resetForm = () => {
  form.value = {
    service_id: "",
    county_id: "",
    performers_count: 1,
    amount: 0,
    is_available: true,
    notes: "",
    extra_child_fee: 0,
    range_age: "",
  };
  selectedService.value = null;
  selectedCounty.value = null;
  selectedDurations.value = [];
  newDuration.value = { minutes: null };
  selectedChildrenRanges.value = [];
  newChildrenRange.value = { min_children: null, max_children: null };
  errors.value = {};
};

const onSelectService = (service) => {
  form.value.service_id = service?.id || "";
};

const onSelectCounty = (county) => {
  form.value.county_id = county?.id || "";
};

const addDuration = () => {
  if (!newDuration.value.minutes || newDuration.value.minutes < 30) return;

  // Verificar que no exista ya esa duración
  const exists = selectedDurations.value.some(d => d.minutes === newDuration.value.minutes);
  if (exists) {
    errors.value.durations = "This duration already exists";
    return;
  }

  selectedDurations.value.push({
    minutes: newDuration.value.minutes,
    is_active: true
  });

  // Limpiar el input
  newDuration.value.minutes = null;
  errors.value.durations = "";
};

/**
 * Elimina una duración de la lista
 * @param {number} index - Index of the duration to remove
 */
const removeDuration = (index) => {
  selectedDurations.value.splice(index, 1);
};

/**
 * Agrega un nuevo rango de cantidad de niños a la lista
 * Valida que no exista duplicado antes de agregar
 */
const addChildrenRange = () => {
  if (!isValidChildrenRange.value) return;

  // Verificar que no exista ya ese rango
  const exists = selectedChildrenRanges.value.some(range =>
    range.min_children === newChildrenRange.value.min_children &&
    range.max_children === newChildrenRange.value.max_children
  );

  if (exists) {
    errors.value.childrenRanges = "This children range already exists";
    return;
  }

  selectedChildrenRanges.value.push({
    min_children: newChildrenRange.value.min_children,
    max_children: newChildrenRange.value.max_children,
    is_active: true
  });

  // Limpiar el input
  newChildrenRange.value = { min_children: null, max_children: null };
  errors.value.childrenRanges = "";
};

/**
 * Elimina un rango de cantidad de niños de la lista
 * @param {number} index - Index of the range to remove
 */
const removeChildrenRange = (index) => {
  selectedChildrenRanges.value.splice(index, 1);
};

/**
 * Crea las duraciones asociadas al service price
 * @param {string} servicePriceId - ID del service price
 */
const createDurations = async (servicePriceId) => {
  try {
    // Validar que servicePriceId esté definido
    if (!servicePriceId) {
      throw new Error('Service Price ID is required to create durations');
    }

    // Create durations in cascade (one by one) to ensure relationship
    for (let i = 0; i < selectedDurations.value.length; i++) {
      const duration = selectedDurations.value[i];

      // Validar que la duración tenga los datos necesarios
      if (!duration.minutes) {
        console.error('Invalid duration data:', duration);
        continue;
      }

      const payload = {
        service_price_id: servicePriceId,
        minutes: duration.minutes,
        is_active: true
      };

      await api.post("/durations", payload);
    }
  } catch (error) {
    console.error('Error creating durations:', error);
    // No lanzamos el error para no interrumpir el flujo principal
  }
};

/**
 * Crea los rangos de cantidad de niños asociados al service price
 * @param {string} servicePriceId - ID del service price
 */
const createChildrenRanges = async (servicePriceId) => {
  try {
    // Validar que servicePriceId esté definido
    if (!servicePriceId) {
      throw new Error('Service Price ID is required to create children ranges');
    }

    // Create children ranges in cascade (one by one) to ensure relationship
    for (let i = 0; i < selectedChildrenRanges.value.length; i++) {
      const range = selectedChildrenRanges.value[i];

      // Validar que el range tenga los datos necesarios
      if (!range.min_children || !range.max_children) {
        console.error('Invalid range data:', range);
        continue;
      }

      const payload = {
        service_price_id: servicePriceId,
        min_age: range.min_children,      // El backend usa min_age para cantidad mínima
        max_age: range.max_children,      // El backend usa max_age para cantidad máxima
        is_active: true
      };

      await api.post("/children-ranges", payload);
    }
  } catch (error) {
    console.error('Error creating children ranges:', error);
    // No lanzamos el error para no interrumpir el flujo principal
  }
};

/**
 * Valida el formulario usando el esquema Yup
 * @returns {boolean} true si es válido, false si hay errores
 */
const validateForm = async () => {
  try {
    await validationSchema.validate(form.value, { abortEarly: false });
    errors.value = {};
    return true;
  } catch (error) {
    const validationErrors = {};
    error.inner.forEach((err) => {
      validationErrors[err.path] = err.message;
    });
    errors.value = validationErrors;
    return false;
  }
};

/**
 * Cierra el modal de creación
 */
const closeModal = () => emit("close");

/**
 * Procesa el envío del formulario de creación de service price
 * Incluye validación, creación del service price, duraciones y rangos de niños
 */
const submitForm = async () => {
  try {
    loading.value = true;
    errors.value = {};

    // Validación con Yup
    const isValid = await validateForm();
    if (!isValid) {
      return;
    }

    // Enviar datos como JSON
    const response = await api.post("/service-prices", form.value);

    // Procesar creación de duraciones y rangos si el service price se creó exitosamente
    if (response.data) {
      // Extraer el ID del service price creado
      let servicePriceId = response.data.data || response.data.id || response.data;

      // Validar que tenemos un ID válido
      if (!servicePriceId || typeof servicePriceId !== 'string') {
        throw new Error('Could not extract valid service price ID from response');
      }

      // Create durations if any selected
      if (selectedDurations.value.length > 0) {
        await createDurations(servicePriceId);
      }

      // Create children ranges if any selected
      if (selectedChildrenRanges.value.length > 0) {
        await createChildrenRanges(servicePriceId);
      }
    }

    emit("saved", true);
    closeModal();
  } catch (error) {
    console.error(error);
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else {
      errors.value = { general: error.response?.data?.message || "An error occurred" };
    }
  } finally {
    loading.value = false;
  }
};
</script>

