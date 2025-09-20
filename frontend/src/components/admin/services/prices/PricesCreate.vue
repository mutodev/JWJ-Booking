<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog">
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
                  <div v-if="imagePreview" class="image-preview">
                    <div class="preview-container">
                      <img :src="imagePreview" alt="Preview" class="preview-image" />
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
    img: "",
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
  selectedImageFile.value = null;
  imagePreview.value = null;
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

const removeDuration = (index) => {
  selectedDurations.value.splice(index, 1);
};

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

  // Crear preview
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

// Función removida - ahora la imagen se envía junto con el formulario

const createDurations = async (servicePriceId) => {
  try {
    console.log('createDurations called with servicePriceId:', servicePriceId);
    console.log('selectedDurations.value:', selectedDurations.value);

    // Validar que servicePriceId esté definido
    if (!servicePriceId) {
      console.error('servicePriceId is undefined or null:', servicePriceId);
      throw new Error('Service Price ID is required to create durations');
    }

    // Crear duraciones en cascada (una por una) para asegurar el service_price_id
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

      console.log(`Creating duration ${i + 1}:`, payload);
      console.log(`service_price_id value:`, servicePriceId);
      console.log(`servicePriceId type:`, typeof servicePriceId);
      console.log(`Payload being sent to backend:`, JSON.stringify(payload, null, 2));

      const result = await api.post("/durations", payload);
      console.log(`Duration ${i + 1} created:`, result.data);
    }
    console.log('All durations created successfully');
  } catch (error) {
    console.error('Error creating durations:', error);
    console.error('Error details:', error.response?.data);
    // No lanzamos el error para no interrumpir el flujo principal
  }
};

const createChildrenRanges = async (servicePriceId) => {
  try {
    console.log('createChildrenRanges called with servicePriceId:', servicePriceId);
    console.log('selectedChildrenRanges.value:', selectedChildrenRanges.value);

    // Validar que servicePriceId esté definido
    if (!servicePriceId) {
      console.error('servicePriceId is undefined or null:', servicePriceId);
      throw new Error('Service Price ID is required to create children ranges');
    }

    // Crear rangos de niños en cascada (uno por uno) para asegurar el service_price_id
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

      console.log(`Creating range ${i + 1}:`, payload);
      console.log(`service_price_id value:`, servicePriceId);
      console.log(`servicePriceId type:`, typeof servicePriceId);
      console.log(`Payload being sent to backend:`, JSON.stringify(payload, null, 2));

      const result = await api.post("/children-ranges", payload);
      console.log(`Range ${i + 1} created:`, result.data);
    }
    console.log('All children ranges created successfully');
  } catch (error) {
    console.error('Error creating children ranges:', error);
    console.error('Error details:', error.response?.data);
    // No lanzamos el error para no interrumpir el flujo principal
  }
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

    // Crear FormData para enviar datos y archivo
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

    // Enviar datos como FormData
    const response = await api.post("/service-prices", formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    // Si se creó exitosamente, crear duraciones y rangos de niños
    if (response.data) {
      console.log('Service price creation response:', response.data);
      console.log('Full response object:', response);

      // Intentar diferentes formas de extraer el ID
      let servicePriceId = response.data.data || response.data.id || response.data;

      console.log('Extracted servicePriceId:', servicePriceId);
      console.log('servicePriceId type:', typeof servicePriceId);
      console.log('selectedDurations.value.length:', selectedDurations.value.length);
      console.log('selectedChildrenRanges.value.length:', selectedChildrenRanges.value.length);

      // Validar que tenemos un ID válido
      if (!servicePriceId || typeof servicePriceId !== 'string') {
        console.error('Invalid servicePriceId extracted:', servicePriceId);
        throw new Error('Could not extract valid service price ID from response');
      }

      // Crear duraciones en cascada si hay seleccionadas
      if (selectedDurations.value.length > 0) {
        console.log('About to call createDurations with servicePriceId:', servicePriceId);
        console.log('servicePriceId value before calling createDurations:', servicePriceId);
        console.log('servicePriceId type before calling createDurations:', typeof servicePriceId);
        console.log('Durations to create:', selectedDurations.value);

        await createDurations(servicePriceId);
        console.log('Durations creation completed');
      } else {
        console.log('No durations to create');
      }

      // Crear rangos de niños en cascada si hay seleccionados
      if (selectedChildrenRanges.value.length > 0) {
        console.log('About to call createChildrenRanges with servicePriceId:', servicePriceId);
        console.log('servicePriceId value before calling createChildrenRanges:', servicePriceId);
        console.log('servicePriceId type before calling createChildrenRanges:', typeof servicePriceId);
        console.log('Children ranges to create:', selectedChildrenRanges.value);

        await createChildrenRanges(servicePriceId);
        console.log('Children ranges creation completed');
      } else {
        console.log('No children ranges to create');
      }
    } else {
      console.log('No response.data received');
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

.form-section {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e9ecef;
}

.form-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.section-title {
  color: #495057;
  font-weight: 600;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
}

.form-label.required::after {
  content: " *";
  color: #dc3545;
}

.duration-container {
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  padding: 1rem;
}

.duration-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.duration-tag {
  background: #0d6efd;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
}

.duration-remove {
  background: none;
  border: none;
  color: white;
  cursor: pointer;
  padding: 0;
  margin-left: 0.25rem;
}

.image-preview {
  margin-top: 0.5rem;
}

.preview-container {
  position: relative;
  display: inline-block;
}

.preview-image {
  max-width: 100px;
  max-height: 100px;
  border-radius: 0.375rem;
}

.preview-remove {
  position: absolute;
  top: -0.25rem;
  right: -0.25rem;
  width: 24px;
  height: 24px;
  border-radius: 50%;
}
</style>
