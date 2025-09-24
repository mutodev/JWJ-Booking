<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Service Price</h5>
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
                    required
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
                    required
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

            <!-- 🖼️ IMAGE & NOTES -->
            <div class="form-section">
              <h6 class="section-title">
                <i class="bi bi-image me-2"></i>Additional Information
              </h6>
              <div class="row">
                <div class="col-md-12 mb-3">
                  <label for="img" class="form-label">Image</label>
                  <input
                    type="file"
                    class="form-control"
                    id="img"
                    accept="image/*"
                    @change="onImageSelected"
                  />
                  <small class="text-muted">JPG, PNG, GIF (max 2MB)</small>
                  <small v-if="errors.img" class="text-danger small d-block">{{ errors.img }}</small>

                  <!-- Image Preview -->
                  <div v-if="imagePreview || form.img" class="image-preview">
                    <div class="preview-container">
                      <img :src="imagePreview || form.img" alt="Preview" class="preview-image" />
                      <button
                        type="button"
                        class="btn btn-sm btn-danger preview-remove"
                        @click="removeImage"
                      >
                        <i class="bi bi-x"></i>
                      </button>
                    </div>
                  </div>
                </div>

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
            Save Changes
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
  data: { type: Object, default: () => ({}) },
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
  img: "",
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
const selectedImageFile = ref(null);
const imagePreview = ref(null);
const errors = ref({});
const loading = ref(false);

// Computed para validar rango de niños
const isValidChildrenRange = computed(() => {
  return newChildrenRange.value.min_children &&
         newChildrenRange.value.max_children &&
         newChildrenRange.value.min_children > 0 &&
         newChildrenRange.value.max_children >= newChildrenRange.value.min_children;
});

// Cargar datos cuando se abre el modal
watch(
  () => props.data,
  async (newData) => {
    if (newData && newData.id) {
      // Cargar datos del service price
      form.value = {
        service_id: newData.service_id || "",
        county_id: newData.county_id || "",
        img: newData.img || "",
        performers_count: newData.performers_count || 1,
        amount: newData.amount || 0,
        is_available: newData.is_available !== undefined ? newData.is_available : true,
        notes: newData.notes || "",
        extra_child_fee: newData.extra_child_fee || 0,
        range_age: newData.range_age || "",
      };

      // Buscar service y county seleccionados
      selectedService.value = props.services.find(s => s.id === newData.service_id) || null;
      selectedCounty.value = props.counties.find(c => c.id === newData.county_id) || null;

      // Cargar duraciones existentes
      await loadExistingDurations(newData.id);

      // Cargar rangos de niños existentes
      await loadExistingChildrenRanges(newData.id);

      errors.value = {};
    }
  },
  { immediate: true }
);

/**
 * Carga las duraciones existentes asociadas al service price
 * @param {string} servicePriceId - ID del service price
 */
const loadExistingDurations = async (servicePriceId) => {
  try {
    const response = await api.get(`/durations/by-service-price/${servicePriceId}`);
    selectedDurations.value = response.data || [];
  } catch (error) {
    console.error('Error loading durations:', error);
    selectedDurations.value = [];
  }
};

/**
 * Carga los rangos de cantidad de niños existentes asociados al service price
 * Mapea los campos del backend (min_age/max_age) al frontend (min_children/max_children)
 * @param {string} servicePriceId - ID del service price
 */
const loadExistingChildrenRanges = async (servicePriceId) => {
  try {
    const response = await api.get(`/children-ranges/by-service-price/${servicePriceId}`);
    // Mapear min_age/max_age del backend a min_children/max_children del frontend
    selectedChildrenRanges.value = (response.data || []).map(range => ({
      min_children: range.min_age,      // Backend usa min_age para cantidad
      max_children: range.max_age,      // Backend usa max_age para cantidad
      is_active: range.is_active
    }));
  } catch (error) {
    console.error('Error loading children ranges:', error);
    selectedChildrenRanges.value = [];
  }
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

const removeDuration = (index) => {
  selectedDurations.value.splice(index, 1);
};

const addChildrenRange = () => {
  console.log('addChildrenRange called');
  console.log('isValidChildrenRange:', isValidChildrenRange.value);
  console.log('newChildrenRange:', newChildrenRange.value);

  if (!isValidChildrenRange.value) {
    console.log('Invalid children range');
    return;
  }

  // Verificar que no exista ya ese rango
  const exists = selectedChildrenRanges.value.some(range =>
    range.min_children === newChildrenRange.value.min_children &&
    range.max_children === newChildrenRange.value.max_children
  );

  if (exists) {
    console.log('Range already exists');
    errors.value.childrenRanges = "This children range already exists";
    return;
  }

  console.log('Adding range to selectedChildrenRanges');
  selectedChildrenRanges.value.push({
    min_children: newChildrenRange.value.min_children,
    max_children: newChildrenRange.value.max_children,
    is_active: true
  });

  console.log('selectedChildrenRanges after add:', selectedChildrenRanges.value);

  // Limpiar el input
  newChildrenRange.value = { min_children: null, max_children: null };
  errors.value.childrenRanges = "";
};

const removeChildrenRange = (index) => {
  selectedChildrenRanges.value.splice(index, 1);
};

const onImageSelected = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  // Validar tipo de archivo
  const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
  if (!allowedTypes.includes(file.type)) {
    errors.value.img = 'Please select a valid image file (JPG, PNG, GIF)';
    return;
  }

  // Validar tamaño (2MB máximo)
  const maxSize = 2 * 1024 * 1024; // 2MB en bytes
  if (file.size > maxSize) {
    errors.value.img = 'Image size must be less than 2MB';
    return;
  }

  selectedImageFile.value = file;
  errors.value.img = '';

  // Create preview
  const reader = new FileReader();
  reader.onload = (e) => {
    imagePreview.value = e.target.result;
  };
  reader.readAsDataURL(file);
};

const removeImage = () => {
  selectedImageFile.value = null;
  imagePreview.value = null;
  form.value.img = "";
  // Limpiar el input file
  const fileInput = document.getElementById('img');
  if (fileInput) fileInput.value = '';
};

// Función de validación con Yup
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
 * Actualiza las duraciones asociadas al service price
 * Desactiva las existentes y crea las nuevas seleccionadas
 * @param {string} servicePriceId - ID del service price
 */
const updateDurations = async (servicePriceId) => {
  try {
    // Desactivar duraciones existentes
    await api.put(`/durations/deactivate-all/${servicePriceId}`);

    // Create new durations
    const durationPromises = selectedDurations.value.map(duration => {
      return api.post("/durations", {
        service_price_id: servicePriceId,
        minutes: duration.minutes,
        is_active: true
      });
    });

    await Promise.all(durationPromises);
  } catch (error) {
    console.error('Error updating durations:', error);
  }
};

const updateChildrenRanges = async (servicePriceId) => {
  try {
    // Desactivar rangos existentes
    await api.put(`/children-ranges/deactivate-all/${servicePriceId}`);

    // Create new ranges
    const rangePromises = selectedChildrenRanges.value.map(range => {
      return api.post("/children-ranges", {
        service_price_id: servicePriceId,
        min_age: range.min_children,      // El backend usa min_age para cantidad mínima
        max_age: range.max_children,      // El backend usa max_age para cantidad máxima
        is_active: true
      });
    });

    await Promise.all(rangePromises);
  } catch (error) {
    console.error('Error updating children ranges:', error);
  }
};

const closeModal = () => emit("close");

const submitForm = async () => {
  try {
    loading.value = true;
    errors.value = {};

    // Validación con Yup
    const isValid = await validateForm();
    if (!isValid) {
      return;
    }

    // Create FormData to send data and file
    const formData = new FormData();

    // Agregar todos los campos del formulario
    Object.keys(form.value).forEach(key => {
      if (form.value[key] !== null && form.value[key] !== undefined) {
        formData.append(key, form.value[key]);
      }
    });

    // Agregar imagen si existe
    if (selectedImageFile.value) {
      formData.append('image', selectedImageFile.value);
    }

    // Actualizar service price usando el endpoint con manejo de imagen
    const response = await api.post(`/service-prices/update/${props.data.id}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    // Actualizar duraciones y rangos de niños
    if (response.data) {
      await updateDurations(props.data.id);
      await updateChildrenRanges(props.data.id);
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

