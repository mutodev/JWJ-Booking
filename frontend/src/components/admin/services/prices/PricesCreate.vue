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

            <!-- 📋 BASIC INFORMATION -->
            <div class="form-section">
              <h6 class="section-title">
                <i class="bi bi-info-circle me-2"></i>Basic Information
              </h6>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Service <span class="text-danger">*</span></label>
                  <Multiselect
                    v-model="selectedService"
                    :options="services"
                    label="name"
                    track-by="id"
                    placeholder="Select a service"
                    @select="onSelectService"
                    :searchable="true"
                    :show-labels="false"
                  />
                  <small class="text-danger small">{{ errors.service_id }}</small>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">County <span class="text-danger">*</span></label>
                  <Multiselect
                    v-model="selectedCounty"
                    :options="counties"
                    label="name"
                    track-by="id"
                    placeholder="Select a county"
                    @select="onSelectCounty"
                    :searchable="true"
                    :show-labels="false"
                  />
                  <small class="text-danger small">{{ errors.county_id }}</small>
                </div>
              </div>
            </div>

            <!-- 💰 PRICING & PERFORMERS -->
            <div class="form-section">
              <h6 class="section-title">
                <i class="bi bi-currency-dollar me-2"></i>Pricing & Performers
              </h6>
              <div class="row">
                <div class="col-md-4 mb-3">
                  <label for="performers_count" class="form-label">
                    Performers Count <span class="text-danger">*</span>
                  </label>
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

                <div class="col-md-4 mb-3">
                  <label for="amount" class="form-label">
                    Base Price (USD) <span class="text-danger">*</span>
                  </label>
                  <input
                    type="number"
                    class="form-control"
                    id="amount"
                    v-model.number="form.amount"
                    min="0"
                    step="0.01"
                    required
                  />
                  <small class="text-danger small">{{ errors.amount }}</small>
                </div>

                <div class="col-md-4 mb-3">
                  <label for="range_age" class="form-label">Age Range</label>
                  <input
                    type="text"
                    class="form-control"
                    id="range_age"
                    v-model="form.range_age"
                    placeholder="e.g., 1 - 10"
                  />
                  <small class="text-danger small">{{ errors.range_age }}</small>
                </div>
              </div>
            </div>

            <!-- 👶 CHILDREN SETTINGS -->
            <div class="form-section">
              <h6 class="section-title">
                <i class="bi bi-people me-2"></i>Children Settings
              </h6>
              <div class="row">
                <div class="col-md-4 mb-3">
                  <label for="max_children" class="form-label">Maximum Children</label>
                  <input
                    type="number"
                    class="form-control"
                    id="max_children"
                    v-model.number="form.max_children"
                    min="0"
                  />
                  <small class="text-danger small">{{ errors.max_children }}</small>
                </div>

                <div class="col-md-4 mb-3">
                  <label for="extra_child_fee" class="form-label">Extra Child Fee (USD)</label>
                  <input
                    type="number"
                    class="form-control"
                    id="extra_child_fee"
                    v-model.number="form.extra_child_fee"
                    min="0"
                    step="0.01"
                  />
                  <small class="text-danger small">{{ errors.extra_child_fee }}</small>
                </div>

                <div class="col-md-4 mb-3">
                  <label for="extra_children" class="form-label">Extra Children Count</label>
                  <input
                    type="number"
                    class="form-control"
                    id="extra_children"
                    v-model.number="form.extra_children"
                    min="0"
                  />
                  <small class="text-danger small">{{ errors.extra_children }}</small>
                </div>
              </div>
            </div>

            <!-- ⏰ DURATION OPTIONS -->
            <div class="form-section">
              <h6 class="section-title">
                <i class="bi bi-clock me-2"></i>Duration Options
              </h6>
              <div class="duration-manager border rounded p-3">
                <!-- Lista de duraciones agregadas -->
                <div v-if="selectedDurations.length > 0" class="mb-3">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <strong class="text-muted">Selected Durations:</strong>
                    <small class="text-muted">{{ selectedDurations.length }} option(s)</small>
                  </div>
                  <div class="d-flex flex-wrap gap-2">
                    <span
                      v-for="(duration, index) in selectedDurations"
                      :key="index"
                      class="badge bg-primary d-flex align-items-center"
                    >
                      {{ duration.minutes }} min ({{ (duration.minutes / 60).toFixed(1) }}h)
                      <button
                        type="button"
                        class="btn-close btn-close-white ms-2"
                        @click="removeDuration(index)"
                        style="font-size: 0.65em;"
                      ></button>
                    </span>
                  </div>
                </div>

                <!-- Formulario para agregar nueva duración -->
                <div class="row align-items-end">
                  <div class="col-md-8">
                    <label for="newDurationMinutes" class="form-label">Add Duration (minutes)</label>
                    <input
                      type="number"
                      class="form-control"
                      id="newDurationMinutes"
                      v-model.number="newDuration.minutes"
                      placeholder="e.g., 60, 90, 120"
                      min="30"
                      max="480"
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
                      <i class="bi bi-plus-circle me-1"></i>
                      Add
                    </button>
                  </div>
                </div>

                <small class="text-muted d-block mt-2">
                  Add multiple duration options for this service (minimum 30 minutes)
                </small>
                <small class="text-danger small">{{ errors.durations }}</small>
              </div>
            </div>

            <!-- 🖼️ IMAGE & NOTES -->
            <div class="form-section">
              <h6 class="section-title">
                <i class="bi bi-image me-2"></i>Image & Notes
              </h6>

              <!-- Image Upload -->
              <div class="mb-3">
                <label for="img" class="form-label">Service Image</label>
                <div class="row">
                  <div class="col-md-8">
                    <input
                      type="file"
                      class="form-control"
                      id="img"
                      @change="onImageSelected"
                      accept="image/*"
                    />
                    <small class="text-muted">Supported formats: JPG, PNG, GIF. Max size: 2MB</small>
                  </div>
                  <div class="col-md-4" v-if="imagePreview">
                    <div class="image-preview text-center">
                      <img :src="imagePreview" alt="Preview" class="img-thumbnail" style="max-height: 80px;">
                      <button type="button" class="btn btn-sm btn-outline-danger mt-1 w-100" @click="removeImage">
                        <i class="bi bi-trash me-1"></i> Remove
                      </button>
                    </div>
                  </div>
                </div>
                <small class="text-danger small">{{ errors.img }}</small>
              </div>

              <!-- Availability - Hidden, always true -->
              <!-- El servicio siempre se crea como disponible por defecto -->

              <!-- Notes -->
              <div class="mb-3">
                <label for="notes" class="form-label">Additional Notes</label>
                <textarea
                  class="form-control"
                  id="notes"
                  v-model="form.notes"
                  rows="3"
                  placeholder="Optional notes about this service price..."
                ></textarea>
                <small class="text-danger small">{{ errors.notes }}</small>
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
  max_children: 0,
  extra_child_fee: 0,
  extra_children: 0,
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
    max_children: 0,
    extra_child_fee: 0,
    extra_children: 0,
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

/* Form Section Styles */
.form-section {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
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
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e9ecef;
  display: flex;
  align-items: center;
}

.section-title i {
  color: #6c757d;
}

/* Duration Manager Styles */
.duration-manager {
  background-color: #f8f9fa;
  border: 1px solid #dee2e6 !important;
}

.badge {
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  font-weight: 500;
}

.badge .btn-close {
  padding: 0;
  margin: 0;
}

.gap-2 {
  gap: 0.5rem !important;
}

/* Form Controls */
.form-label {
  font-weight: 500;
  color: #495057;
  margin-bottom: 0.5rem;
}

.form-control:focus,
.form-select:focus {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Image Preview */
.image-preview img {
  border-radius: 0.375rem;
}

/* Switch Style */
.form-check-input:checked {
  background-color: #28a745;
  border-color: #28a745;
}

/* Responsive */
@media (max-width: 768px) {
  .form-section {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
  }

  .section-title {
    font-size: 1rem;
    margin-bottom: 0.75rem;
  }
}
</style>
