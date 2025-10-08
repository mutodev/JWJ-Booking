<template>
  <div v-if="show" class="admin-modal admin-form modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <!-- Header -->
        <div class="modal-header">
          <div class="d-flex align-items-center">
            <i class="bi bi-calendar-event fs-4 me-3"></i>
            <div>
              <h5 class="modal-title mb-0">Reservation Details</h5>
              <small class="text-muted">ID: {{ data.id || 'N/A' }}</small>
            </div>
          </div>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <!-- Status Banner -->
          <div class="alert alert-light border mb-4">
            <div class="row align-items-center">
              <div class="col-md-8">
                <div class="d-flex align-items-center">
                  <i class="bi" :class="getStatusIcon()" style="font-size: 1.5rem; margin-right: 12px;"></i>
                  <div>
                    <h6 class="mb-1 fw-bold">Status: {{ data.status?.toUpperCase() || 'UNKNOWN' }}</h6>
                    <div class="d-flex gap-3">
                      <span class="badge" :class="data.is_paid ? 'bg-success' : 'bg-danger'">
                        <i class="bi bi-credit-card me-1"></i>
                        {{ data.is_paid ? 'PAID' : 'UNPAID' }}
                      </span>
                      <span class="badge" :class="data.is_invoiced ? 'bg-info' : 'bg-secondary'">
                        <i class="bi bi-receipt me-1"></i>
                        {{ data.is_invoiced ? 'INVOICED' : 'NOT INVOICED' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4 text-end">
                <div class="total-amount">
                  <small class="text-muted d-block">Total Amount</small>
                  <h4 class="mb-0 fw-bold">{{ formatCurrency(data.total_amount) }}</h4>
                </div>
              </div>
            </div>
          </div>
          <!-- Customer & Event Info -->
          <div class="row mb-4">
            <div class="col-lg-6">
              <div class="card h-100">
                <div class="card-header">
                  <i class="bi bi-person-fill me-2"></i>
                  <strong>Customer Information</strong>
                </div>
                <div class="card-body">
                  <div class="row mb-2">
                    <div class="col-sm-5"><strong>Name:</strong></div>
                    <div class="col-sm-7">{{ data.customer_name || 'Not specified' }}</div>
                  </div>
                  <div class="row mb-2">
                    <div class="col-sm-5"><strong>Email:</strong></div>
                    <div class="col-sm-7">{{ data.email || 'Not specified' }}</div>
                  </div>
                  <div class="row mb-2">
                    <div class="col-sm-5"><strong>Birthday Child:</strong></div>
                    <div class="col-sm-7">{{ data.birthday_child_name || 'Not specified' }}</div>
                  </div>
                  <div class="row mb-2" v-if="data.birthday_child_age">
                    <div class="col-sm-5"><strong>Turning Age:</strong></div>
                    <div class="col-sm-7">{{ data.birthday_child_age }} years old</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="card h-100">
                <div class="card-header">
                  <i class="bi bi-geo-alt-fill me-2"></i>
                  <strong>Event Information</strong>
                </div>
                <div class="card-body">
                  <div class="row mb-2">
                    <div class="col-sm-5"><strong>Date & Time:</strong></div>
                    <div class="col-sm-7">{{ formatDate(data.event_date) }} at {{ data.event_time || 'TBD' }}</div>
                  </div>
                  <div class="row mb-2">
                    <div class="col-sm-5"><strong>Entertainment:</strong></div>
                    <div class="col-sm-7">{{ data.entertainment_start_time || 'Not specified' }}</div>
                  </div>
                  <div class="row mb-2">
                    <div class="col-sm-5"><strong>Address:</strong></div>
                    <div class="col-sm-7">{{ data.event_address || 'Not specified' }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Service Details -->
          <div class="row mb-4">
            <div class="col-lg-4">
              <div class="card h-100">
                <div class="card-header">
                  <i class="bi bi-music-note-beamed me-2"></i>
                  <strong>Service Details</strong>
                </div>
                <div class="card-body">
                  <div class="row mb-2">
                    <div class="col-6"><strong>Service:</strong></div>
                    <div class="col-6">{{ data.service_name || 'Not specified' }}</div>
                  </div>
                  <div class="row mb-2">
                    <div class="col-6"><strong>Duration:</strong></div>
                    <div class="col-6">{{ data.duration_hours || 0 }} hours</div>
                  </div>
                  <div class="row mb-2">
                    <div class="col-6"><strong>Performers:</strong></div>
                    <div class="col-6">{{ data.performers_count || 0 }} performer(s)</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="card h-100">
                <div class="card-header">
                  <i class="bi bi-people-fill me-2"></i>
                  <strong>Children Details</strong>
                </div>
                <div class="card-body">
                  <div class="row mb-2">
                    <div class="col-6"><strong>Total Children:</strong></div>
                    <div class="col-6">{{ data.children_count || 0 }} children</div>
                  </div>
                  <div class="row mb-2">
                    <div class="col-6"><strong>Age Range:</strong></div>
                    <div class="col-6">{{ data.children_age_range || 'Not specified' }}</div>
                  </div>
                  <div class="row mb-2" v-if="getExtraChildren() > 0">
                    <div class="col-6"><strong>Extra Children:</strong></div>
                    <div class="col-6 fw-bold">{{ getExtraChildren() }} over 40</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="card h-100">
                <div class="card-header">
                  <i class="bi bi-calculator me-2"></i>
                  <strong>Pricing Breakdown</strong>
                </div>
                <div class="card-body">
                  <div class="d-flex justify-content-between mb-1">
                    <span>Base Service:</span>
                    <strong>{{ formatCurrency(data.base_price) }}</strong>
                  </div>
                  <div class="d-flex justify-content-between mb-1" v-if="data.addons_total > 0">
                    <span>Add-ons:</span>
                    <strong>{{ formatCurrency(data.addons_total) }}</strong>
                  </div>
                  <div class="d-flex justify-content-between mb-1" v-if="data.extra_children_fee > 0">
                    <span>Extra Children:</span>
                    <strong>{{ formatCurrency(data.extra_children_fee) }}</strong>
                  </div>
                  <div class="d-flex justify-content-between mb-1" v-if="data.expedition_fee > 0">
                    <span>Expedition Fee:</span>
                    <strong>{{ formatCurrency(data.expedition_fee) }}</strong>
                  </div>
                  <hr class="my-2">
                  <div class="d-flex justify-content-between">
                    <strong>Total Amount:</strong>
                    <strong>{{ formatCurrency(data.total_amount) }}</strong>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Special Requests & Notes -->
          <div class="row mb-4">
            <div class="col-12">
              <div class="card">
                <div class="card-header">
                  <i class="bi bi-chat-text me-2"></i>
                  <strong>Special Requests & Notes</strong>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-lg-6">
                      <div class="mb-3">
                        <strong><i class="bi bi-music-note me-2"></i>Song Requests</strong>
                        <div class="mt-1 p-2 bg-light border rounded">{{ data.song_requests || 'None specified' }}</div>
                      </div>
                      <div class="mb-3">
                        <strong><i class="bi bi-car-front me-2"></i>Arrival & Parking Instructions</strong>
                        <div class="mt-1 p-2 bg-light border rounded">{{ data.arrival_parking_instructions || 'None specified' }}</div>
                      </div>
                    </div>
                    <div class="col-lg-6">
                      <div class="mb-3">
                        <strong><i class="bi bi-person-heart me-2"></i>Customer Notes</strong>
                        <div class="mt-1 p-2 bg-light border rounded">{{ data.customer_notes || 'None specified' }}</div>
                      </div>
                      <div class="mb-3">
                        <strong><i class="bi bi-bookmark-fill me-2"></i>Internal Notes</strong>
                        <div class="mt-1 p-2 bg-light border rounded">{{ data.internal_notes || 'None specified' }}</div>
                      </div>
                    </div>
                  </div>
                  <div class="mt-3 d-flex align-items-center">
                    <i class="bi bi-cake2 me-2"></i>
                    <span>Happy Birthday Song: <strong>{{ data.sing_happy_birthday ? 'Requested' : 'Not Requested' }}</strong></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <div class="d-flex justify-content-between align-items-center w-100">
            <small class="text-muted">
              <i class="bi bi-calendar-plus me-1"></i>
              Created: {{ formatDate(data.created_at) }}
              <span class="mx-2">•</span>
              <i class="bi bi-pencil me-1"></i>
              Updated: {{ formatDate(data.updated_at) }}
            </small>
            <div>
              <button type="button" class="btn btn-secondary me-2" @click="closeModal">
                <i class="bi bi-x-lg me-1"></i> Close
              </button>
              <button type="button" class="btn btn-primary" @click="editReservation">
                <i class="bi bi-pencil me-1"></i> Edit Reservation
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
import { watch, ref } from "vue";

const data = ref({});
const emit = defineEmits(["close", "saved", "edit"]);
const props = defineProps({
  show: Boolean,
  data: {
    type: Object,
    default: () => ({}),
  },
});

watch(
  () => props.data,
  (newData) => {
    data.value = { ...newData };
  },
  { deep: true, immediate: true }
);

const closeModal = () => {
  emit("close");
};

// Métodos de formato
const formatDate = (date) => {
  if (!date) return "N/A";
  const d = new Date(date);
  return d.toLocaleDateString("en-US", {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const formatCurrency = (amount) => {
  if (amount == null || amount === 0) return "$0.00";
  return amount.toLocaleString("en-US", { style: "currency", currency: "USD" });
};

// Métodos de estado y cálculo
const getStatusBannerClass = () => {
  const status = data.value.status;
  switch (status) {
    case 'new':
      return 'bg-warning text-dark';
    case 'confirmed':
      return 'bg-success text-white';
    case 'cancelled':
      return 'bg-danger text-white';
    case 'completed':
      return 'bg-info text-white';
    default:
      return 'bg-secondary text-white';
  }
};

const getStatusIcon = () => {
  const status = data.value.status;
  switch (status) {
    case 'new':
      return 'bi-hourglass-split';
    case 'confirmed':
      return 'bi-check-circle-fill';
    case 'cancelled':
      return 'bi-x-circle-fill';
    case 'completed':
      return 'bi-trophy-fill';
    default:
      return 'bi-question-circle';
  }
};

const getExtraChildren = () => {
  const totalChildren = data.value.children_count || 0;
  return Math.max(0, totalChildren - 40);
};

const editReservation = () => {
  // Emit edit event or navigation logic
  emit('edit', data.value);
};
</script>

<style scoped>
.total-amount {
  text-align: right;
}

/* Responsive Design */
@media (max-width: 768px) {
  .modal-dialog {
    margin: 0;
    max-width: 100%;
    height: 100vh;
  }

  .modal-content {
    height: 100vh;
    border-radius: 0;
  }

  .total-amount {
    text-align: center;
    margin-top: 1rem;
  }

  .row .col-6,
  .row .col-sm-5,
  .row .col-sm-7 {
    margin-bottom: 0.5rem;
  }
}
</style>

