<template>
  <div class="container py-5">
    <!-- Success Icon & Title -->
    <div class="text-center mb-5">
      <div class="success-icon mb-4">
        <i class="bi bi-check-circle-fill text-success"></i>
      </div>
      <h2 class="text-success mb-2">Reservation Confirmed!</h2>
      <p class="text-muted">Thank you for choosing our services. Your reservation has been successfully submitted.</p>
    </div>

    <!-- Reservation Details Card -->
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0">
              <i class="bi bi-calendar-check me-2"></i>
              Reservation Details
            </h5>
          </div>
          <div class="card-body">
            <!-- Reservation ID -->
            <div class="row mb-3" v-if="reservationData?.reservation?.id">
              <div class="col-sm-4">
                <strong>Reservation ID:</strong>
              </div>
              <div class="col-sm-8">
                <code class="text-primary">{{ reservationData.reservation.id }}</code>
              </div>
            </div>

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

            <!-- Price Breakdown -->
            <div class="row mb-3" v-if="reservationData?.calculation">
              <div class="col-12">
                <strong>Price Breakdown:</strong>
                <div class="mt-2">
                  <div class="d-flex justify-content-between">
                    <span>Service Price:</span>
                    <span>${{ formatPrice(reservationData.calculation.service_price) }}</span>
                  </div>
                  <div class="d-flex justify-content-between" v-if="reservationData.calculation.addons_total > 0">
                    <span>Add-ons:</span>
                    <span>${{ formatPrice(reservationData.calculation.addons_total) }}</span>
                  </div>
                  <div class="d-flex justify-content-between" v-if="reservationData.calculation.extra_children_total > 0">
                    <span>Extra Children:</span>
                    <span>${{ formatPrice(reservationData.calculation.extra_children_total) }}</span>
                  </div>
                  <div class="d-flex justify-content-between" v-if="reservationData.calculation.surcharge_amount > 0">
                    <span>Surcharge:</span>
                    <span>${{ formatPrice(reservationData.calculation.surcharge_amount) }}</span>
                  </div>
                  <hr>
                  <div class="d-flex justify-content-between fw-bold text-success">
                    <span>Total Amount:</span>
                    <span>${{ formatPrice(reservationData.calculation.grand_total) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Status -->
            <div class="row mb-3">
              <div class="col-sm-4">
                <strong>Status:</strong>
              </div>
              <div class="col-sm-8">
                <span class="badge bg-warning text-dark">{{ reservationData?.reservation?.status || 'Pending' }}</span>
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
          <ul class="mb-0">
            <li>You will receive a confirmation email shortly with all the details</li>
            <li>Our team will contact you within 24 hours to confirm the booking</li>
            <li>Payment instructions will be included in the confirmation email</li>
            <li>If you need to make changes, please contact us as soon as possible</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="text-center mt-4">
      <button
        type="button"
        class="btn btn-primary btn-lg me-3"
        @click="printDetails"
      >
        <i class="bi bi-printer me-2"></i>
        Print Details
      </button>
      <button
        type="button"
        class="btn btn-outline-secondary btn-lg"
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
function formatDate(dateString) {
  if (!dateString) return 'Not specified';

  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  } catch (error) {
    return dateString;
  }
}

function formatPrice(amount) {
  if (amount === null || amount === undefined) return '0.00';
  return parseFloat(amount).toFixed(2);
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

.btn-lg {
  font-size: 1.1rem;
  font-weight: 600;
  border-radius: 50px;
  padding: 0.75rem 2rem;
  transition: all 0.3s ease;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

@media (max-width: 768px) {
  .success-icon {
    font-size: 4rem;
  }

  h2 {
    font-size: 2rem;
  }

  .btn-lg {
    font-size: 1rem;
    padding: 0.65rem 1.5rem;
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
