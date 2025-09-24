<template>
  <div v-if="show" class="admin-modal admin-form modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <!-- Header -->
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Reservation Details</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <!-- Body -->
        <div class="modal-body p-4">
          <!-- Segment: Basic Info -->
          <div class="segment mb-3">
            <h6 class="segment-title">Basic Info</h6>
            <div class="d-flex flex-wrap gap-2">
              <div class="mini-card">
                <strong>Customer:</strong>
                {{ data.customer_name || data.customer_id }}
              </div>
              <div class="mini-card">
                <strong>Service:</strong>
                {{ data.service_name || data.service_price_id }}
              </div>
              <div class="mini-card">
                <strong>Event Address:</strong> {{ data.event_address || "-" }}
              </div>
              <div class="mini-card">
                <strong>Date:</strong> {{ formatDate(data.event_date) }}
              </div>
              <div class="mini-card">
                <strong>Time:</strong> {{ data.event_time || "-" }}
              </div>
              <div class="mini-card">
                <strong>Start Time:</strong>
                {{ data.entertainment_start_time || "-" }}
              </div>
            </div>
          </div>

          <!-- Segment: Event Details -->
          <div class="segment mb-3">
            <h6 class="segment-title">Event Details</h6>
            <div class="d-flex flex-wrap gap-2">
              <div class="mini-card">
                <strong>Children:</strong> {{ data.children_count }}
              </div>
              <div class="mini-card">
                <strong>Birthday Age:</strong>
                {{ data.birthday_child_age || "-" }}
              </div>
              <div class="mini-card">
                <strong>Age Range:</strong> {{ data.children_age_range || "-" }}
              </div>
              <div class="mini-card">
                <strong>Performers:</strong> {{ data.performers_count }}
              </div>
              <div class="mini-card">
                <strong>Duration (hrs):</strong> {{ data.duration_hours }}
              </div>
            </div>
          </div>

          <!-- Segment: Pricing -->
          <div class="segment mb-3">
            <h6 class="segment-title">Pricing</h6>
            <div class="d-flex flex-wrap gap-2">
              <div class="mini-card">
                <strong>Base:</strong> {{ formatCurrency(data.base_price) }}
              </div>
              <div class="mini-card">
                <strong>Addons:</strong> {{ formatCurrency(data.addons_total) }}
              </div>
              <div class="mini-card">
                <strong>Extra Children:</strong>
                {{ formatCurrency(data.extra_children_fee) }}
              </div>
              <div class="mini-card">
                <strong>Expedition:</strong>
                {{ formatCurrency(data.expedition_fee) }}
              </div>
              <div class="mini-card total">
                <strong>Total:</strong> {{ formatCurrency(data.total_amount) }}
              </div>
            </div>
          </div>

          <!-- Segment: Status & Payments -->
          <div class="segment mb-3">
            <h6 class="segment-title">Status & Payments</h6>
            <div class="d-flex flex-wrap gap-2">
              <div
                class="mini-card"
                :class="{
                  'bg-warning text-dark': data.status === 'new',
                  'bg-success text-white': data.status === 'confirmed',
                  'bg-danger text-white': data.status === 'cancelled',
                }"
              >
                <strong>Status:</strong> {{ data.status }}
              </div>
              <div
                class="mini-card"
                :class="
                  data.is_paid
                    ? 'bg-success text-white'
                    : 'bg-danger text-white'
                "
              >
                <strong>Paid:</strong> {{ data.is_paid ? "Yes" : "No" }}
              </div>
              <div class="mini-card">
                <strong>Invoiced:</strong> {{ data.is_invoiced ? "Yes" : "No" }}
              </div>
            </div>
          </div>

          <!-- Segment: Notes & Requests -->
          <div class="segment">
            <h6 class="segment-title">Notes & Requests</h6>
            <div class="d-flex flex-wrap gap-2">
              <div class="mini-card w-100">
                <strong>Arrival / Parking:</strong>
                {{ data.arrival_parking_instructions || "-" }}
              </div>
              <div class="mini-card w-100">
                <strong>Song Requests:</strong> {{ data.song_requests || "-" }}
              </div>
              <div class="mini-card w-100">
                <strong>Customer Notes:</strong>
                {{ data.customer_notes || "-" }}
              </div>
              <div class="mini-card w-100">
                <strong>Internal Notes:</strong>
                {{ data.internal_notes || "-" }}
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            <i class="bi bi-arrow-90deg-down"></i> Back
          </button>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
import { watch, ref } from "vue";

const data = ref({});
const emit = defineEmits(["close", "saved"]);
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

const formatDate = (date) => {
  if (!date) return "-";
  const d = new Date(date);
  return d.toLocaleDateString("en-GB");
};

const formatCurrency = (amount) => {
  if (amount == null) return "-";
  return amount.toLocaleString("en-US", { style: "currency", currency: "USD" });
};
</script>

