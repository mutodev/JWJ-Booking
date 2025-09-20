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
                  <label for="extra_child_fee" class="form-label">Extra Child Fee (USD)</label>
                  <input
                    type="number"
                    class="form-control"
                    id="extra_child_fee"
                    v-model.number="form.extra_child_fee"
                    min="0"
                    step="0.01"
                    placeholder="0.00"
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
                  <label for="range_age" class="form-label">Age Range</label>
                  <input
                    type="text"
                    class="form-control"
                    id="range_age"
                    v-model="form.range_age"
                    placeholder="e.g., 3-8 years"
                  />
                  <small class="text-danger small">{{ errors.range_age }}</small>
                </div>
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
                  <label for="notes" class="form-label">Additional Notes</label>
                  <textarea
                    class="form-control"
                    id="notes"
                    v-model="form.notes"
                    rows="3"
                    placeholder="Optional notes about this service..."
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
import { ref, watch } from "vue";
import api from "@/services/axios";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";

const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
  services: { type: Array, default: () => [] },
  counties: { type: Array, default: () => [] },
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
const selectedImageFile = ref(null);
const imagePreview = ref(null);
const errors = ref({});
const loading = ref(false);

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
    const durationPromises = selectedDurations.value.map(duration => {
      return api.post("/durations", {
        service_price_id: servicePriceId,
        minutes: duration.minutes,
        is_active: true
      });
    });

    await Promise.all(durationPromises);
  } catch (error) {
    console.error('Error creating durations:', error);
    // No lanzamos el error para no interrumpir el flujo principal
  }
};

const closeModal = () => emit("close");

const submitForm = async () => {
  try {
    loading.value = true;
    errors.value = {};

    // Validación básica
    if (!form.value.service_id) {
      errors.value.service_id = "Service is required";
      return;
    }
    if (!form.value.county_id) {
      errors.value.county_id = "County is required";
      return;
    }
    if (!form.value.amount || form.value.amount <= 0) {
      errors.value.amount = "Amount must be greater than 0";
      return;
    }
    if (!form.value.performers_count || form.value.performers_count <= 0) {
      errors.value.performers_count = "Performers count must be greater than 0";
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

    // Si se creó exitosamente y hay duraciones seleccionadas, crearlas
    if (response.data && selectedDurations.value.length > 0) {
      const servicePriceId = response.data.data; // Asumiendo que el ID viene en data.data
      await createDurations(servicePriceId);
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
