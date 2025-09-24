<template>
  <div class="container py-4">
    <!-- Título -->
    <div class="d-flex align-items-center mb-4">
      <i class="bi bi-person-fill fs-3 me-2 text-success"></i>
      <h2 class="mb-0">Information</h2>
    </div>

    <!-- Subtitle -->
    <p class="text-muted mb-4">
      In case the event is not a birthday, please fill the boxes with N/A.
    </p>

    <form @submit.prevent="validateForm">
      <div class="row">
        <!-- Left Column -->
        <div class="col-md-6">
          <!-- Name -->
          <div class="mb-3">
            <label for="name" class="form-label">
              Name <span class="text-danger">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              class="form-control"
              id="name"
              placeholder="Elena"
              @blur="validateField('name')"
            />
            <div v-if="errors.name" class="text-danger small mt-1">
              {{ errors.name }}
            </div>
          </div>

          <!-- Last name -->
          <div class="mb-3">
            <label for="lastName" class="form-label">
              Last name <span class="text-danger">*</span>
            </label>
            <input
              v-model="form.lastName"
              type="text"
              class="form-control"
              id="lastName"
              placeholder="Guzmán"
              @blur="validateField('lastName')"
            />
            <div v-if="errors.lastName" class="text-danger small mt-1">
              {{ errors.lastName }}
            </div>
          </div>

          <!-- Full address -->
          <div class="mb-3">
            <label for="fullAddress" class="form-label">
              Full address <span class="text-danger">*</span>
            </label>
            <input
              v-model="form.fullAddress"
              type="text"
              class="form-control"
              id="fullAddress"
              placeholder="Address Example"
              @blur="validateField('fullAddress')"
            />
            <div v-if="errors.fullAddress" class="text-danger small mt-1">
              {{ errors.fullAddress }}
            </div>
          </div>

          <!-- Arrival, parking, and additional instructions -->
          <div class="mb-3">
            <label for="instructions" class="form-label">
              Arrival, parking, and additional instructions <span class="text-danger">*</span>
            </label>
            <textarea
              v-model="form.instructions"
              class="form-control"
              id="instructions"
              rows="3"
              placeholder="Instruction example"
              @blur="validateField('instructions')"
            ></textarea>
            <div v-if="errors.instructions" class="text-danger small mt-1">
              {{ errors.instructions }}
            </div>
          </div>

          <!-- Date of the event -->
          <div class="mb-3">
            <label for="eventDate" class="form-label">
              Date of the event <span class="text-danger">*</span>
            </label>
            <input
              v-model="form.eventDate"
              type="date"
              class="form-control"
              id="eventDate"
              @blur="validateField('eventDate')"
            />
            <div v-if="errors.eventDate" class="text-danger small mt-1">
              {{ errors.eventDate }}
            </div>
          </div>

          <!-- Time frame of the event -->
          <div class="mb-3">
            <label class="form-label">
              Time frame of the event <span class="text-danger">*</span>
            </label>
            <div class="row">
              <div class="col-6">
                <input
                  v-model="form.startTime"
                  type="time"
                  class="form-control"
                  placeholder="Start time"
                  @blur="validateField('startTime')"
                />
              </div>
              <div class="col-6">
                <input
                  v-model="form.endTime"
                  type="time"
                  class="form-control"
                  placeholder="End time"
                  @blur="validateField('endTime')"
                />
              </div>
            </div>
            <div v-if="errors.startTime || errors.endTime" class="text-danger small mt-1">
              {{ errors.startTime || errors.endTime }}
            </div>
          </div>

          <!-- Entertainment start time -->
          <div class="mb-3">
            <label class="form-label">Entertainment start time</label>
            <div class="row">
              <div class="col-6">
                <input
                  v-model="form.entertainmentStartTime"
                  type="time"
                  class="form-control"
                  placeholder="Start time"
                />
              </div>
              <div class="col-6">
                <input
                  v-model="form.entertainmentEndTime"
                  type="time"
                  class="form-control"
                  placeholder="End time"
                />
              </div>
            </div>
            <p class="text-muted small mt-1">
              (recommended at least 30 minutes after the party start time)
            </p>
          </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-6">
          <!-- Birthday child's name -->
          <div class="mb-3">
            <label for="birthdayChildName" class="form-label">
              Birthday child's name <span class="text-danger">*</span>
            </label>
            <input
              v-model="form.birthdayChildName"
              type="text"
              class="form-control"
              id="birthdayChildName"
              placeholder="Leslie Parker"
              @blur="validateField('birthdayChildName')"
            />
            <div v-if="errors.birthdayChildName" class="text-danger small mt-1">
              {{ errors.birthdayChildName }}
            </div>
          </div>

          <!-- Age they are turning -->
          <div class="mb-3">
            <label for="childAge" class="form-label">
              Age they are turning <span class="text-danger">*</span>
            </label>
            <input
              v-model="form.childAge"
              type="number"
              class="form-control"
              id="childAge"
              placeholder="Parker"
              min="1"
              max="18"
              @blur="validateField('childAge')"
            />
            <div v-if="errors.childAge" class="text-danger small mt-1">
              {{ errors.childAge }}
            </div>
          </div>

          <!-- Age range of children attending -->
          <div class="mb-3">
            <label for="ageRange" class="form-label">
              Age range of children attending <span class="text-danger">*</span>
            </label>
            <input
              v-model="form.ageRange"
              type="text"
              class="form-control"
              id="ageRange"
              placeholder="Address Example"
              @blur="validateField('ageRange')"
            />
            <div v-if="errors.ageRange" class="text-danger small mt-1">
              {{ errors.ageRange }}
            </div>
          </div>

          <!-- Song requests -->
          <div class="mb-3">
            <label for="songRequests" class="form-label">
              Song requests, up to 3 (provide links) <span class="text-danger">*</span>
            </label>
            <textarea
              v-model="form.songRequests"
              class="form-control"
              id="songRequests"
              rows="4"
              placeholder="Link a&#10;Link b&#10;Link c"
              @blur="validateField('songRequests')"
            ></textarea>
            <div v-if="errors.songRequests" class="text-danger small mt-1">
              {{ errors.songRequests }}
            </div>
          </div>

          <!-- Happy Birthday song -->
          <div class="mb-3">
            <label for="happyBirthdayRequest" class="form-label">
              Would you like Happy Birthday to be sung at the end of the set? <span class="text-danger">*</span>
            </label>
            <select
              v-model="form.happyBirthdayRequest"
              class="form-control"
              id="happyBirthdayRequest"
              @blur="validateField('happyBirthdayRequest')"
            >
              <option value="">Please select</option>
              <option value="yes">Yes</option>
              <option value="no">No</option>
            </select>
            <div v-if="errors.happyBirthdayRequest" class="text-danger small mt-1">
              {{ errors.happyBirthdayRequest }}
            </div>
          </div>
        </div>
      </div>

      <!-- Submit Button -->
      <div class="text-center mt-4">
        <button
          v-if="!isValid"
          type="button"
          class="btn btn-primary btn-lg px-5"
          @click="validateForm"
        >
          Validate Information
          <i class="bi ms-2 bi-arrow-right"></i>
        </button>

        <button
          v-else
          type="button"
          class="btn btn-success btn-lg px-5"
          @click="showConfirmationModal"
          :disabled="isSubmitting || isCompleted"
        >
          <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-if="isCompleted && !isSubmitting" class="bi bi-check-circle me-2"></i>
          {{ isCompleted ? 'Reservation Completed!' : (isSubmitting ? 'Submitting...' : 'Submit Reservation') }}
          <i v-if="!isSubmitting && !isCompleted" class="bi ms-2 bi-send"></i>
        </button>
      </div>

      <!-- Confirmation Modal -->
      <div class="modal fade" id="confirmationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                Confirm Reservation
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <p class="mb-3">Please review your reservation details before submitting:</p>

              <div class="row g-3">
                <div class="col-12">
                  <strong>Event Details:</strong>
                  <ul class="list-unstyled ms-3">
                    <li>📅 Date: {{ form.eventDate }}</li>
                    <li>⏰ Time: {{ form.startTime }} - {{ form.endTime }}</li>
                    <li>📍 Address: {{ form.fullAddress }}</li>
                  </ul>
                </div>

                <div class="col-12">
                  <strong>Birthday Child:</strong>
                  <ul class="list-unstyled ms-3">
                    <li>👶 Name: {{ form.birthdayChildName }}</li>
                    <li>🎂 Age: {{ form.childAge }} years old</li>
                  </ul>
                </div>
              </div>

              <div class="alert alert-warning mt-3">
                <small>
                  <i class="bi bi-info-circle me-1"></i>
                  Once submitted, you will receive a confirmation email with your reservation details.
                </small>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                Cancel
              </button>
              <button
                type="button"
                class="btn btn-success"
                @click="submitReservation"
                :disabled="isSubmitting || isCompleted"
              >
                <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
                <i v-if="isCompleted && !isSubmitting" class="bi bi-check-circle me-2"></i>
                {{ isCompleted ? 'Completed!' : (isSubmitting ? 'Processing...' : 'Confirm & Submit') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from "vue";
import * as yup from "yup";
import { Modal } from "bootstrap";
import { ElMessage } from 'element-plus';
import api from "@/services/axios";

const props = defineProps({
  active: {
    type: Boolean,
    default: false,
  },
  customer: {
    type: Object,
    default: null,
  },
  information: {
    type: Object,
    default: null,
  },
  // Agregamos los props necesarios para el envío
  zipcode: {
    type: Object,
    default: null,
  },
  service: {
    type: Object,
    default: null,
  },
  kids: {
    type: Object,
    default: null,
  },
  hours: {
    type: Object,
    default: null,
  },
  addons: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["setData", "reservationSuccess"]);

// Form data
const form = reactive({
  name: "",
  lastName: "",
  fullAddress: "",
  instructions: "",
  eventDate: "",
  startTime: "",
  endTime: "",
  entertainmentStartTime: "",
  entertainmentEndTime: "",
  birthdayChildName: "",
  childAge: "",
  ageRange: "",
  songRequests: "",
  happyBirthdayRequest: "",
});

const errors = reactive({});
const isSubmitting = ref(false);
const isCompleted = ref(false);
let confirmationModal = null;

// Validation schema
const schema = yup.object({
  name: yup.string().required("Name is required"),
  lastName: yup.string().required("Last name is required"),
  fullAddress: yup.string().required("Full address is required"),
  instructions: yup.string().required("Instructions are required"),
  eventDate: yup.date().required("Event date is required"),
  startTime: yup.string().required("Start time is required"),
  endTime: yup.string().required("End time is required"),
  birthdayChildName: yup.string().required("Birthday child's name is required"),
  childAge: yup.number().min(1, "Age must be at least 1").max(18, "Age must be 18 or less").required("Child age is required"),
  ageRange: yup.string().required("Age range is required"),
  songRequests: yup.string().required("Song requests are required"),
  happyBirthdayRequest: yup.string().oneOf(["yes", "no"], "Please select an option").required("Please select an option"),
});

// Computed properties
const isValid = computed(() => {
  try {
    schema.validateSync(form);
    return true;
  } catch {
    return false;
  }
});

// Methods
async function validateField(field) {
  try {
    await schema.validateAt(field, form);
    errors[field] = "";
  } catch (e) {
    errors[field] = e.message;
  }
  emitFormData();
}

async function validateForm() {
  try {
    await schema.validate(form, { abortEarly: false });
    Object.keys(errors).forEach((key) => (errors[key] = ""));
    emitFormData();
  } catch (validationErrors) {
    Object.keys(errors).forEach((key) => (errors[key] = ""));
    if (validationErrors?.inner) {
      validationErrors.inner.forEach((err) => {
        errors[err.path] = err.message;
      });
    }
  }
}

function emitFormData() {
  const informationData = {
    ...form,
    isValid: isValid.value
  };

  emit("setData", { information: informationData });
}

// Modal y envío de datos
function showConfirmationModal() {
  // Prevenir si ya está completado
  if (isCompleted.value) return;

  const modalElement = document.getElementById('confirmationModal');
  if (!modalElement) {
    console.error('Modal element not found');
    return;
  }

  if (!confirmationModal) {
    confirmationModal = new Modal(modalElement);
  }
  confirmationModal.show();
}

async function submitReservation() {
  if (isSubmitting.value || isCompleted.value) return;

  isSubmitting.value = true;

  try {
    // Preparar datos para enviar al endpoint
    const reservationData = {
      customer: props.customer,
      zipcode: props.zipcode,
      service: props.service,
      kids: props.kids,
      hours: props.hours,
      addons: props.addons,
      information: {
        ...form,
        isValid: isValid.value
      }
    };

    console.log('Sending reservation data:', reservationData);

    // Enviar al endpoint
    const response = await api.post('/home/reservation', reservationData);

    console.log('API Response:', response);

    if (response.status === 201 || response.status === 200) {
      // Marcar como completado inmediatamente
      isCompleted.value = true;

      // Cerrar modal inmediatamente
      if (confirmationModal) {
        confirmationModal.hide();
      }

      // Mostrar mensaje de éxito
      ElMessage.success('Reservation submitted successfully!');

      // Dar tiempo para que el modal se cierre y luego emitir el evento
      setTimeout(() => {
        // Cleanup modal
        if (confirmationModal) {
          try {
            confirmationModal.dispose();
          } catch (e) {
            console.log('Modal already disposed or does not exist');
          }
          confirmationModal = null;
        }

        // Emitir evento de éxito con los datos de respuesta
        const responseData = response.data?.data || response.data;

        emit("reservationSuccess", {
          reservation: responseData.reservation || responseData,
          calculation: responseData.calculation || null
        });
      }, 500);

    } else {
      throw new Error('Unexpected response status: ' + response.status);
    }

  } catch (error) {
    console.error('Error submitting reservation:', error);

    let errorMessage = 'Failed to submit reservation. Please try again.';

    if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    } else if (error.message) {
      errorMessage = error.message;
    }

    ElMessage.error(errorMessage);
    isSubmitting.value = false; // Solo resetear isSubmitting en caso de error
  }
  // No resetear isSubmitting ni isCompleted en caso de éxito para evitar múltiples envíos
}

// Auto-complete from Step1 customer data
function autoCompleteFromCustomer() {
  if (props.customer) {
    form.name = props.customer.firstName || "";
    form.lastName = props.customer.lastName || "";
  }
}

// Restore form data if available
function restoreFormData() {
  if (props.information) {
    Object.keys(form).forEach(key => {
      if (props.information[key] !== undefined) {
        form[key] = props.information[key];
      }
    });
  }
}

// Watchers
watch(
  () => props.active,
  (active) => {
    if (active) {
      // Resetear estados cuando se activa el step
      isCompleted.value = false;
      isSubmitting.value = false;

      autoCompleteFromCustomer();
      restoreFormData();
      emitFormData();
    }
  },
  { immediate: true }
);

watch(
  () => props.customer,
  () => {
    if (props.active) {
      autoCompleteFromCustomer();
    }
  },
  { deep: true }
);

onMounted(() => {
  if (props.active) {
    autoCompleteFromCustomer();
    restoreFormData();
  }
});
</script>

<style scoped>
.form-control {
  border-radius: 8px;
  font-size: 0.95rem;
  padding: 0.6rem 0.75rem;
  border: 1px solid #d1d5db;
}

.form-control:focus {
  border-color: #198754;
  box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
}

.form-label {
  font-size: 0.9rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
}

h2 {
  font-size: 1.8rem;
  font-weight: 600;
  color: #333;
}

textarea.form-control {
  resize: vertical;
  min-height: 80px;
}

.btn-lg {
  font-size: 1.1rem;
  font-weight: 600;
  border-radius: 50px;
  padding: 0.75rem 2rem;
  transition: all 0.3s ease;
}

.btn-primary {
  background-color: #6c757d;
  border-color: #6c757d;
}

.btn-success {
  background-color: #198754;
  border-color: #198754;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

@media (max-width: 768px) {
  .container {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  h2 {
    font-size: 1.5rem;
  }
}
</style>