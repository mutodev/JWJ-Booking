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

    <!-- Contact Info Display (Auto-filled from Step 1) -->
    <div class="info-display mb-4">
      <div class="row">
        <div class="col-md-6">
          <div class="info-item">
            <span class="info-label">Contact Name:</span>
            <span class="info-value">{{ contactName }}</span>
          </div>
        </div>
        <div class="col-md-6">
          <div class="info-item">
            <span class="info-label">Event Date:</span>
            <span class="info-value">{{ eventDateDisplay }}</span>
          </div>
        </div>
      </div>
    </div>

    <form @submit.prevent="validateForm">
      <div class="row">
        <!-- Left Column -->
        <div class="col-md-6">
          <!-- Full address -->
          <div class="mb-3">
            <label for="fullAddress" class="form-label">
              Full address <span class="text-danger">*</span>
            </label>
            <el-tooltip
              content="Enter the complete address where the event will take place"
              placement="right"
              effect="dark"
              trigger="focus"
            >
              <input
                v-model="form.fullAddress"
                type="text"
                class="form-control"
                id="fullAddress"
                @blur="validateField('fullAddress')"
              />
            </el-tooltip>
            <div v-if="errors.fullAddress" class="text-danger small mt-1">
              {{ errors.fullAddress }}
            </div>
          </div>

          <!-- Arrival, parking, and additional instructions -->
          <div class="mb-3">
            <label for="instructions" class="form-label">
              Arrival, parking, and additional instructions
            </label>
            <el-tooltip
              content="Provide details about parking, access, or any special instructions for arrival (optional)"
              placement="right"
              effect="dark"
              trigger="focus"
            >
              <textarea
                v-model="form.instructions"
                class="form-control"
                id="instructions"
                rows="3"
                @blur="validateField('instructions')"
              ></textarea>
            </el-tooltip>
            <div v-if="errors.instructions" class="text-danger small mt-1">
              {{ errors.instructions }}
            </div>
            <div class="parking-note mt-2">
              <i class="bi bi-info-circle me-1"></i>
              If there is valet or other parking that costs over $10, the amount will be added to your invoice. If we are informed of these charges after the event, the credit card on file will be charged accordingly.
            </div>
          </div>

          <!-- Event start time -->
          <div class="mb-3">
            <label class="form-label">
              Event start time <span class="text-danger">*</span>
            </label>
            <el-tooltip
              content="Select the start time for the event"
              placement="right"
              effect="dark"
              trigger="focus"
            >
              <el-time-picker
                v-model="form.startTime"
                placeholder="Start time"
                format="hh:mm A"
                value-format="HH:mm"
                style="width: 100%"
                size="large"
                @blur="validateField('startTime')"
              />
            </el-tooltip>
            <div v-if="errors.startTime" class="text-danger small mt-1">
              {{ errors.startTime }}
            </div>
          </div>

          <!-- Entertainment start time -->
          <div class="mb-3">
            <label class="form-label">Entertainment start time</label>
            <el-tooltip
              content="Recommended at least 30 minutes after the party start time"
              placement="right"
              effect="dark"
              trigger="focus"
            >
              <el-time-picker
                v-model="form.entertainmentStartTime"
                placeholder="Start time"
                format="hh:mm A"
                value-format="HH:mm"
                style="width: 100%"
                size="large"
              />
            </el-tooltip>
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
            <el-tooltip
              content="Enter the name of the birthday child (or N/A if not applicable)"
              placement="right"
              effect="dark"
              trigger="focus"
            >
              <input
                v-model="form.birthdayChildName"
                type="text"
                class="form-control"
                id="birthdayChildName"
                @blur="validateField('birthdayChildName')"
              />
            </el-tooltip>
            <div v-if="errors.birthdayChildName" class="text-danger small mt-1">
              {{ errors.birthdayChildName }}
            </div>
          </div>

          <!-- Age they are turning -->
          <div class="mb-3">
            <label for="childAge" class="form-label">
              Age they are turning <span class="text-danger">*</span>
            </label>
            <el-tooltip
              content="Enter the age the child is turning (1-18, or N/A if not a birthday)"
              placement="right"
              effect="dark"
              trigger="focus"
            >
              <input
                v-model="form.childAge"
                type="text"
                class="form-control"
                id="childAge"
                placeholder="Enter age (1-18) or N/A"
                @blur="validateField('childAge')"
              />
            </el-tooltip>
            <div v-if="errors.childAge" class="text-danger small mt-1">
              {{ errors.childAge }}
            </div>
          </div>

          <!-- Age range of children attending -->
          <div class="mb-3">
            <label for="ageRange" class="form-label">
              Age range of children attending <span class="text-danger">*</span>
            </label>
            <el-tooltip
              content="Enter the age range of children attending (e.g., 5-10 years)"
              placement="right"
              effect="dark"
              trigger="focus"
            >
              <input
                v-model="form.ageRange"
                type="text"
                class="form-control"
                id="ageRange"
                @blur="validateField('ageRange')"
              />
            </el-tooltip>
            <div v-if="errors.ageRange" class="text-danger small mt-1">
              {{ errors.ageRange }}
            </div>
          </div>

          <!-- Song requests -->
          <div class="mb-3">
            <label for="songRequests" class="form-label">
              Song requests, up to 3 (provide links)
            </label>
            <el-tooltip
              content="Provide YouTube or Spotify links for up to 3 songs (one per line) - Optional"
              placement="right"
              effect="dark"
              trigger="focus"
            >
              <textarea
                v-model="form.songRequests"
                class="form-control"
                id="songRequests"
                rows="4"
                @blur="validateField('songRequests')"
              ></textarea>
            </el-tooltip>
            <div v-if="errors.songRequests" class="text-danger small mt-1">
              {{ errors.songRequests }}
            </div>
          </div>

          <!-- Happy Birthday song -->
          <div class="mb-3">
            <label for="happyBirthdayRequest" class="form-label">
              Would you like Happy Birthday to be sung at the end of the set? <span class="text-danger">*</span>
            </label>
            <el-tooltip
              content="Select whether you want the Happy Birthday song performed"
              placement="right"
              effect="dark"
              trigger="focus"
            >
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
            </el-tooltip>
            <div v-if="errors.happyBirthdayRequest" class="text-danger small mt-1">
              {{ errors.happyBirthdayRequest }}
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

const emit = defineEmits(["setData"]);

// Form data (campos que no están en Step1)
const form = reactive({
  fullAddress: "",
  instructions: "",
  startTime: "",
  entertainmentStartTime: "",
  birthdayChildName: "",
  childAge: "",
  ageRange: "",
  songRequests: "",
  happyBirthdayRequest: "",
});

const errors = reactive({});

// Computed properties para mostrar datos de Step1
const contactName = computed(() => {
  if (!props.customer) return "N/A";
  return `${props.customer.firstName || ""} ${props.customer.lastName || ""}`.trim() || "N/A";
});

const eventDateDisplay = computed(() => {
  if (!props.customer || !props.customer.eventDateTime) return "N/A";
  const date = new Date(props.customer.eventDateTime);
  return date.toLocaleDateString('en-US', {
    month: '2-digit',
    day: '2-digit',
    year: 'numeric'
  });
});

// Validation schema
const schema = yup.object({
  fullAddress: yup.string().required("Full address is required"),
  instructions: yup.string(), // Optional field
  startTime: yup.string().required("Start time is required"),
  birthdayChildName: yup.string().required("Birthday child's name is required (or N/A)"),
  childAge: yup.mixed().test('valid-age', 'Age must be between 1-18 or N/A', function(value) {
    if (!value || value === '' || value.toString().toUpperCase() === 'N/A') return true;
    const num = Number(value);
    return !isNaN(num) && num >= 1 && num <= 18;
  }).required("Child age is required (or N/A)"),
  ageRange: yup.string().required("Age range is required"),
  songRequests: yup.string(), // Optional field
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
      restoreFormData();
      emitFormData();
    }
  },
  { immediate: true }
);

onMounted(() => {
  if (props.active) {
    restoreFormData();
  }
});
</script>

<style scoped>
/* Info Display */
.info-display {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 1.5rem;
  border-radius: 12px;
  border: 2px solid #d1d5db;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-value {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1f2937;
}

/* Form Controls */
.form-control {
  border-radius: 8px;
  font-size: 0.95rem;
  padding: 0.6rem 0.75rem;
  border: 1px solid #d1d5db;
}

.form-control:focus {
  border-color: #FF74B7;
  box-shadow: 0 0 0 0.2rem rgba(255, 116, 183, 0.25);
}

/* Element Plus Overrides */
:deep(.el-input__wrapper) {
  border-radius: 8px;
  border: 1px solid #d1d5db;
  box-shadow: none;
  padding: 0.6rem 0.75rem;
}

:deep(.el-input__wrapper:hover) {
  border-color: #9ca3af;
}

:deep(.el-input__wrapper.is-focus) {
  border-color: #FF74B7;
  box-shadow: 0 0 0 0.2rem rgba(255, 116, 183, 0.25);
}

:deep(.el-input__inner) {
  font-size: 0.95rem;
}

:deep(.el-picker-panel) {
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

:deep(.el-date-picker__header-label:hover),
:deep(.el-picker-panel__icon-btn:hover) {
  color: #FF74B7;
}

:deep(.el-date-table td.available:hover) {
  color: #FF74B7;
}

:deep(.el-date-table td.today span) {
  color: #FF74B7;
  font-weight: bold;
}

:deep(.el-date-table td.current:not(.disabled) span) {
  background-color: #FF74B7;
  color: white;
}

:deep(.el-time-panel__btn.confirm) {
  color: #FF74B7;
}

:deep(.el-time-spinner__item:hover:not(.disabled):not(.active)) {
  background: rgba(255, 116, 183, 0.1);
}

:deep(.el-time-spinner__item.active:not(.disabled)) {
  color: #FF74B7;
  font-weight: bold;
}

.form-label {
  font-size: 0.9rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
}

.parking-note {
  font-size: 0.85rem;
  color: #6b7280;
  background-color: #f9fafb;
  padding: 0.75rem 1rem;
  border-left: 3px solid #3b82f6;
  border-radius: 6px;
  line-height: 1.5;
  display: flex;
  align-items: flex-start;
}

.parking-note i {
  color: #3b82f6;
  margin-top: 2px;
  flex-shrink: 0;
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
  background-color: #FF74B7;
  border-color: #FF74B7;
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