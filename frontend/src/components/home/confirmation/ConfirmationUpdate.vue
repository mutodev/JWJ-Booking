<template>
  <div class="container py-4">
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-3">Loading reservation details...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger" role="alert">
      {{ error }}
    </div>

    <!-- Already confirmed: show only tip selection + pay button -->
    <div v-else-if="confirmedAlready" class="py-4">
      <div class="d-flex align-items-center mb-4">
        <i class="bi bi-check-circle-fill fs-3 me-2 text-success"></i>
        <h2 class="mb-0">Ready to Pay</h2>
      </div>
      <p class="text-muted mb-4">Your event details are confirmed. Add a gratuity for your performer(s) below, then proceed to payment.</p>

      <div class="tip-section mb-4">
        <h5 class="tip-title">Add a Gratuity <span class="text-muted small fw-normal">(optional)</span></h5>
        <p class="text-muted small mb-3">100% goes to your performer(s).</p>
        <div class="tip-options d-flex flex-wrap gap-2 mb-3">
          <button
            v-for="opt in tipOptions"
            :key="opt.label"
            type="button"
            class="btn tip-btn"
            :class="selectedTip === opt.value ? 'btn-primary' : 'btn-outline-secondary'"
            @click="selectTip(opt.value)"
          >
            {{ opt.label }}
          </button>
        </div>
        <div v-if="selectedTip === 'custom'" class="input-group mb-2" style="max-width: 200px;">
          <span class="input-group-text">$</span>
          <input
            v-model.number="customTipAmount"
            type="number"
            min="0"
            step="0.01"
            class="form-control"
            placeholder="0.00"
          />
        </div>
        <div v-if="computedTip > 0" class="tip-amount-display">
          Gratuity: <strong>${{ computedTip.toFixed(2) }}</strong>
        </div>
      </div>

      <div class="text-center">
        <button
          class="btn btn-success btn-lg"
          :disabled="submitting"
          @click="handlePayOnly"
        >
          <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
          {{ submitting ? 'Redirecting...' : 'Proceed to Payment' }}
        </button>
      </div>
    </div>

    <!-- Form -->
    <div v-else>
      <!-- Título -->
      <div class="d-flex align-items-center mb-4">
        <i class="bi bi-person-fill fs-3 me-2 text-success"></i>
        <h2 class="mb-0">Information</h2>
      </div>

      <!-- Subtitle -->
      <p class="text-muted mb-4">
        In case the event is not a birthday, please fill the boxes with N/A.
      </p>

      <form @submit.prevent="handleSubmit">
        <div class="row">
          <!-- Date -->
          <div class="col-md-6 mb-3">
            <label for="eventDate" class="form-label">
              Date <span class="text-danger">*</span>
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

          <!-- Full address -->
          <div class="col-md-6 mb-3">
            <label for="fullAddress" class="form-label">
              Reconfirm Address <span class="text-danger">*</span>
            </label>
            <p class="text-muted small mb-1">If unknown at this time, please list an address for reference, and ensure the zip code below is correct.</p>
            <input
              v-model="form.fullAddress"
              type="text"
              class="form-control"
              id="fullAddress"
              placeholder="123 Main St, Apt #4"
              autocomplete="street-address"
              @blur="validateField('fullAddress')"
            />
            <div v-if="errors.fullAddress" class="text-danger small mt-1">
              {{ errors.fullAddress }}
            </div>
          </div>
        </div>

        <div class="row">
          <!-- Event start time -->
          <div class="col-md-6 mb-3">
            <label class="form-label">
              Event Start Time <span class="text-danger">*</span>
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
          <div class="col-md-6 mb-3">
            <label class="form-label">Entertainment Start Time</label>
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

        <!-- Arrival, parking, and additional instructions -->
        <div class="mb-3">
          <label for="instructions" class="form-label">
            Provide detailed arrival and parking instructions
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

        <!-- Number and age range of children attending -->
        <div class="mb-3">
          <label for="ageRange" class="form-label">
            Number of children and their age range <span class="text-danger">*</span>
          </label>
          <p class="text-muted small mb-1">Please include all children attending</p>
          <el-tooltip
            content="Please include all children attending (e.g., 12 children, ages 4-8)"
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

        <div class="row">
          <!-- Birthday child's name -->
          <div class="col-md-6 mb-3">
            <label for="birthdayChildName" class="form-label">
              Birthday child’s name <span class="text-danger">*</span>
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
          <div class="col-md-6 mb-3">
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

        <!-- Submit Button -->
        <div class="text-center mt-4">
          <button
            type="submit"
            class="btn btn-success btn-lg"
            :disabled="submitting"
          >
            <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
            {{ submitting ? 'Saving...' : 'Save and Continue' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import api from "@/services/axios";
import * as yup from "yup";

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const error = ref(null);
const submitting = ref(false);
const reservation = ref({});
const confirmedAlready = ref(false);

const form = reactive({
  eventDate: "",
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

// Tip / Gratuity
const selectedTip = ref(null);
const customTipAmount = ref(0);
const tipOptions = [
  { label: 'No tip', value: 0 },
  { label: '10%',    value: 0.10 },
  { label: '15%',    value: 0.15 },
  { label: '20%',    value: 0.20 },
  { label: 'Custom', value: 'custom' },
];

const computedTip = computed(() => {
  if (selectedTip.value === null || selectedTip.value === 0) return 0;
  if (selectedTip.value === 'custom') return Math.max(0, customTipAmount.value || 0);
  const base = parseFloat(reservation.value.total_amount || 0);
  return Math.round(base * selectedTip.value * 100) / 100;
});

const selectTip = (value) => {
  selectedTip.value = value;
};

const isTrueValue = (value) => value === true || value === 1 || value === '1';

const toDateInputValue = (value) => {
  if (!value) return '';
  let dateStr = value;
  if (typeof dateStr === 'object' && dateStr.date) {
    dateStr = dateStr.date;
  }

  const match = String(dateStr).match(/^(\d{4}-\d{2}-\d{2})/);
  return match ? match[1] : '';
};

const saveGratuityAndRedirect = async (reservationId) => {
  if (computedTip.value > 0) {
    await api.patch(`/reservations/${reservationId}/gratuity`, { amount: computedTip.value });
  }
  const paymentResponse = await api.post(`/reservations/${reservationId}/regenerate-payment`);
  const paymentData = paymentResponse.data?.data || paymentResponse.data;
  if (paymentData?.payment_url) {
    window.location.href = paymentData.payment_url;
  } else {
    error.value = 'Unable to generate payment link. Please contact support.';
  }
};

// Validation schema (mantener igual)
const schema = yup.object({
  eventDate: yup.string().required("Date is required"),
  fullAddress: yup.string().required("Full address is required"),
  instructions: yup.string(),
  startTime: yup.string().required("Start time is required"),
  birthdayChildName: yup.string().required("Birthday child's name is required (or N/A)"),
  childAge: yup.mixed().test('valid-age', 'Age must be between 1-18 or N/A', function(value) {
    if (!value || value === '' || value.toString().toUpperCase() === 'N/A') return true;
    const num = Number(value);
    return !isNaN(num) && num >= 1 && num <= 18;
  }).required("Child age is required (or N/A)"),
  ageRange: yup.string().required("Age range is required"),
  songRequests: yup.string(),
  happyBirthdayRequest: yup.string().oneOf(["yes", "no"], "Please select an option").required("Please select an option"),
});

// Fetch reservation data
async function fetchReservation() {
  try {
    loading.value = true;
    const reservationId = route.params.id;

    if (!reservationId) {
      error.value = "Invalid reservation ID";
      return;
    }

    const response = await api.get(`/reservations/${reservationId}`);

    // Handle both response.data and response.data.data formats
    const resData = response.data?.data || response.data;
    reservation.value = resData;

    console.log('Reservation data:', reservation.value);

    // Already paid → redirect to payment success page (always, regardless of confirmation status)
    if (isTrueValue(reservation.value.is_paid)) {
      const sessionId = reservation.value.stripe_session_id;
      router.push(sessionId ? `/payment-success?session_id=${sessionId}` : '/payment-success');
      return;
    }

    // Cancelled → show error
    if (reservation.value.status === 'cancelled') {
      error.value = 'This reservation has been cancelled.';
      return;
    }

    // Customer already submitted the confirmation form → show tip selection, then pay
    if (isTrueValue(reservation.value.customer_confirmed)) {
      loading.value = false;
      // Keep page visible with just the tip section (form hidden via confirmedAlready flag)
      confirmedAlready.value = true;
      return;
    }

    // Pre-fill form with data already entered by admin (if any)
    const r = reservation.value;
    const cleanTime  = (t) => (t && t.length > 5 ? t.substring(0, 5) : (t || ''));
    const cleanDash  = (v) => (v && v !== '-' ? v : '');
    const boolToYesNo = (v) => {
      if (v === null || v === undefined || v === '') return '';
      return (v == 1 || v === true || v === 'yes') ? 'yes' : 'no';
    };

    form.eventDate               = toDateInputValue(r.event_date);
    form.fullAddress             = cleanDash(r.event_address);
    form.instructions            = cleanDash(r.arrival_parking_instructions);
    form.startTime               = cleanTime(r.event_time);
    form.entertainmentStartTime  = cleanTime(r.entertainment_start_time);
    form.birthdayChildName       = cleanDash(r.birthday_child_name);
    form.childAge                = (r.birthday_child_age && r.birthday_child_age > 0) ? String(r.birthday_child_age) : '';
    form.ageRange                = cleanDash(r.children_age_range);
    form.songRequests            = cleanDash(r.song_requests);
    form.happyBirthdayRequest    = boolToYesNo(r.sing_happy_birthday);

  } catch (err) {
    console.error("Error fetching reservation:", err);
    error.value = "Failed to load reservation details. Please check the link and try again.";
  } finally {
    loading.value = false;
  }
}

// Validate field
async function validateField(field) {
  try {
    await schema.validateAt(field, form);
    errors[field] = "";
  } catch (e) {
    errors[field] = e.message;
  }
}

// Submit form
async function handleSubmit() {
  // Step 1: validate — yup errors stay here, don't reach the API calls
  try {
    await schema.validate(form, { abortEarly: false });
  } catch (validationErrors) {
    Object.keys(errors).forEach((key) => (errors[key] = ""));
    if (validationErrors?.inner) {
      validationErrors.inner.forEach((err) => {
        errors[err.path] = err.message;
      });
    }
    return;
  }

  Object.keys(errors).forEach((key) => (errors[key] = ""));
  submitting.value = true;
  const reservationId = route.params.id;

  // Step 2: save confirmation data, then show gratuity before payment
  try {
    const updateData = {
      event_date: form.eventDate,
      event_address: form.fullAddress,
      arrival_parking_instructions: form.instructions || null,
      event_time: form.startTime,
      entertainment_start_time: form.entertainmentStartTime || null,
      birthday_child_name: form.birthdayChildName,
      birthday_child_age: form.childAge,
      children_age_range: form.ageRange,
      song_requests: form.songRequests || null,
      sing_happy_birthday: form.happyBirthdayRequest === "yes",
    };

    await api.patch(`/reservations/${reservationId}/confirmation`, updateData);

    // Show tip screen. The Stripe session is generated from there.
    confirmedAlready.value = true;
  } catch (err) {
    console.error('Submission error:', err);
    error.value = 'An error occurred while saving. Please try again or contact support.';
  } finally {
    submitting.value = false;
  }
}

// Handler for already-confirmed path (skip form, just save tip + pay)
async function handlePayOnly() {
  submitting.value = true;
  try {
    await saveGratuityAndRedirect(route.params.id);
  } catch (err) {
    console.error('Payment error:', err);
    error.value = 'An error occurred. Please try again or contact support.';
  } finally {
    submitting.value = false;
  }
}

onMounted(() => {
  fetchReservation();
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

.btn-success {
  background-color: #FF74B7;
  border-color: #FF74B7;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.tip-section {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.25rem 1.5rem;
}

.tip-title {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.tip-btn {
  border-radius: 50px;
  font-size: 0.9rem;
  font-weight: 500;
  padding: 0.4rem 1rem;
}

.tip-amount-display {
  font-size: 0.95rem;
  color: #374151;
  margin-top: 0.5rem;
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
