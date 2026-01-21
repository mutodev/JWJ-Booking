<template>
  <div class="container py-5">
    <!-- Success Icon & Title -->
    <div class="text-center mb-5">
      <div class="success-icon mb-4">
        <i class="bi bi-check-circle-fill text-dark"></i>
      </div>
      <h2 class="text-dark mb-2">Thank you for your reservation!</h2>
      <p class="text-muted">We will be in touch with you to confirm availability and send a link for payment.</p>
    </div>

    <!-- Reservation Details Card -->
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-dark text-white">
            <h5 class="mb-0">
              <i class="bi bi-calendar-check me-2"></i>
              Reservation Details
            </h5>
          </div>
          <div class="card-body">

            <!-- Event Details -->
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Event Date:</strong>
              </div>
              <div class="col-sm-8">
                {{ formatDate(reservationData?.reservation?.event_date) }}
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Event Time:</strong>
              </div>
              <div class="col-sm-8">
                {{ reservationData?.reservation?.event_time || 'Not specified' }}
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Event Address:</strong>
              </div>
              <div class="col-sm-8">
                {{ reservationData?.reservation?.event_address || 'Not specified' }}
              </div>
            </div>

            <!-- Birthday Child Info -->
            <div class="row mb-3" v-if="reservationData?.reservation?.birthday_child_name">
              <div class="col-sm-4">
                <strong>Birthday Child:</strong>
              </div>
              <div class="col-sm-8">
                {{ reservationData.reservation.birthday_child_name }}
                <span v-if="reservationData?.reservation?.birthday_child_age">({{ reservationData.reservation.birthday_child_age }} years old)</span>
              </div>
            </div>

            <!-- Service Selected -->
            <div class="row mb-3" v-if="reservationData?.service">
              <div class="col-sm-4">
                <strong>Service:</strong>
              </div>
              <div class="col-sm-8">
                {{ reservationData.service.name }}
                <span class="text-muted">({{ reservationData.service.performers_count }} performer{{ reservationData.service.performers_count > 1 ? 's' : '' }})</span>
              </div>
            </div>

            <!-- Add-ons Selected -->
            <div class="row mb-3" v-if="reservationData?.addons && reservationData.addons.length > 0">
              <div class="col-sm-4">
                <strong>Add-ons:</strong>
              </div>
              <div class="col-sm-8">
                <ul class="list-unstyled mb-0">
                  <li v-for="(addon, index) in reservationData.addons" :key="index">
                    <i class="bi bi-check-circle-fill text-success me-1"></i>
                    {{ addon.name }}
                    <span class="text-muted" v-if="addon.base_price > 0">(+${{ formatPrice(addon.base_price) }})</span>
                    <span class="badge bg-info ms-1" v-else>Referral</span>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Price Breakdown -->
            <div class="row mb-3" v-if="reservationData?.calculation">
              <div class="col-12">
                <strong>Price Breakdown:</strong>
                <div class="mt-2 breakdown-list">
                  <div class="d-flex justify-content-between breakdown-item">
                    <span>Base Service Rate ({{ reservationData?.service?.name || 'Service' }}):</span>
                    <span>${{ formatPrice(reservationData.calculation.service_price) }}</span>
                  </div>
                  <!-- Detalle de cada addon -->
                  <template v-if="reservationData?.addons && reservationData.addons.length > 0">
                    <div
                      v-for="(addon, index) in reservationData.addons"
                      :key="index"
                      class="d-flex justify-content-between breakdown-item"
                      v-show="addon.base_price > 0"
                    >
                      <span>{{ addon.name }}:</span>
                      <span>${{ formatPrice(addon.base_price) }}</span>
                    </div>
                  </template>
                  <div class="d-flex justify-content-between breakdown-item" v-if="reservationData.calculation.extra_children_total > 0">
                    <span>Extra Children ({{ reservationData.calculation.extra_children_count }} kids):</span>
                    <span>${{ formatPrice(reservationData.calculation.extra_children_total) }}</span>
                  </div>
                  <div class="d-flex justify-content-between breakdown-item border-top pt-2 mt-2 fw-semibold">
                    <span>Subtotal (Service + Add-ons):</span>
                    <span>${{ formatPrice(reservationData.calculation.base_total) }}</span>
                  </div>
                  <div class="d-flex justify-content-between breakdown-item text-success" v-if="reservationData.calculation.discount > 0">
                    <span>Discount:</span>
                    <span>-${{ formatPrice(reservationData.calculation.discount) }}</span>
                  </div>
                  <div class="d-flex justify-content-between breakdown-item" v-if="reservationData.calculation.travel_fee > 0">
                    <span>Travel Fee:</span>
                    <span>${{ formatPrice(reservationData.calculation.travel_fee) }}</span>
                  </div>
                  <div class="d-flex justify-content-between breakdown-item" v-if="reservationData.calculation.surcharge_amount > 0">
                    <span>Surcharge:</span>
                    <span>${{ formatPrice(reservationData.calculation.surcharge_amount) }}</span>
                  </div>
                  <hr class="my-2">
                  <div class="d-flex justify-content-between fw-bold breakdown-total">
                    <span>Grand Total:</span>
                    <span>${{ formatPrice(reservationData.calculation.grand_total) }}</span>
                  </div>
                  <div class="d-flex justify-content-between breakdown-item text-muted" v-if="reservationData.calculation.total_duration_hours">
                    <span>Total Duration:</span>
                    <span>{{ formatDuration(reservationData.calculation.total_duration_hours) }}</span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- Next Steps -->
    <div class="row justify-content-center mt-4">
      <div class="col-lg-8">
        <div class="alert alert-info">
          <h6 class="alert-heading">
            <i class="bi bi-info-circle me-2"></i>
            What's Next?
          </h6>
          <p class="mb-2">
            You will receive a confirmation email with your reservation request details. Our team will contact you within 48 business hours to let you know if we have confirmed availability and finalize the booking. At that time, we will ask for additional event information and you will have the opportunity to share any special requests and important details. We will then send a link with payment instructions to secure the service.
          </p>
          <p class="mb-0">
            <strong>We operate Monday–Friday, 9:00 a.m. to 5:00 p.m. EST.</strong>
          </p>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="text-center mt-4">
      <button
        type="button"
        class="btn custom-btn custom-btn-primary btn-lg me-3"
        @click="printDetails"
      >
        <i class="bi bi-printer me-2"></i>
        Print Details
      </button>
      <button
        type="button"
        class="btn custom-btn btn-lg"
        @click="startNewReservation"
      >
        <i class="bi bi-plus-circle me-2"></i>
        New Reservation
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  reservationData: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["newReservation"]);

// Methods
function formatDate(dateInput) {
  if (!dateInput) return 'Not specified';

  try {
    let dateString;

    // Si es un objeto DateTime de PHP serializado
    if (typeof dateInput === 'object' && dateInput.date) {
      dateString = dateInput.date;
    }
    // Si es un string directo
    else if (typeof dateInput === 'string') {
      dateString = dateInput;
    }
    // Si es otro tipo, convertir a string
    else {
      dateString = dateInput.toString();
    }

    const date = new Date(dateString);

    // Verificar que la fecha sea válida
    if (isNaN(date.getTime())) {
      return 'Invalid date format';
    }

    return date.toLocaleDateString('en-US', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  } catch (error) {
    console.error('Date formatting error:', error);
    return 'Date format error';
  }
}

function formatPrice(amount) {
  if (amount === null || amount === undefined) return '0.00';
  return parseFloat(amount).toFixed(2);
}

function formatDuration(hours) {
  if (hours === null || hours === undefined) return '0 hours';

  const totalHours = parseFloat(hours);
  const wholeHours = Math.floor(totalHours);
  const minutes = Math.round((totalHours - wholeHours) * 60);

  if (minutes === 0) {
    return `${wholeHours} hour${wholeHours !== 1 ? 's' : ''}`;
  } else if (wholeHours === 0) {
    return `${minutes} minute${minutes !== 1 ? 's' : ''}`;
  } else {
    return `${wholeHours} hour${wholeHours !== 1 ? 's' : ''} ${minutes} minute${minutes !== 1 ? 's' : ''}`;
  }
}

function printDetails() {
  window.print();
}

function startNewReservation() {
  emit("newReservation");
}
</script>

<style scoped>
.success-icon {
  font-size: 5rem;
  animation: bounce 1s ease-in-out;
}

@keyframes bounce {
  0%, 20%, 50%, 80%, 100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-10px);
  }
  60% {
    transform: translateY(-5px);
  }
}

h2 {
  font-size: 2.5rem;
  font-weight: 600;
}

.card {
  border-radius: 12px;
}

.card-header {
  border-radius: 12px 12px 0 0 !important;
}

code {
  font-size: 0.9rem;
  padding: 0.25rem 0.5rem;
  background-color: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 4px;
}

/* Breakdown list styles - matching Step4 */
.breakdown-list {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.breakdown-item {
  padding: 0.4rem 0;
  color: #6c757d;
}

.breakdown-total {
  font-size: 1.1rem;
  color: #FF74B7;
}

/* Custom button styles consistent with home wizard */
.custom-btn {
  border-radius: 8px !important;
  font-weight: 600 !important;
  padding: 12px 24px !important;
  height: auto !important;
  transition: all 0.2s ease !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
  border: 2px solid #d1d5db !important;
  background: white !important;
  color: #6b7280 !important;
}

.custom-btn:hover {
  border-color: #9ca3af !important;
  background: #f9fafb !important;
  color: #4b5563 !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
}

.custom-btn:active {
  transform: translateY(0) !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}

.custom-btn-primary {
  border: 2px solid #FF74B7 !important;
  background: #FF74B7 !important;
  color: black !important;
}

.custom-btn-primary:hover {
  border-color: #FF74B7 !important;
  background: #FF74B7 !important;
  color: black !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 6px rgba(255, 116, 183, 0.3) !important;
}

.custom-btn-primary:active {
  transform: translateY(0) !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}

@media (max-width: 768px) {
  .success-icon {
    font-size: 4rem;
  }

  h2 {
    font-size: 2rem;
  }

  .custom-btn {
    width: 100%;
    margin-bottom: 0.5rem;
  }

  .me-3 {
    margin-right: 0 !important;
  }
}

@media print {
  .btn, .alert {
    display: none !important;
  }
}
</style>
