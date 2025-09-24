<template>
  <div v-if="show" class="admin-modal admin-form modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <!-- Header -->
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title">Edit Reservation</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <!-- Body -->
        <div class="modal-body p-4">
          <form @submit.prevent="saveReservation">

            <!-- Segment: Basic Info -->
            <div class="segment mb-3">
              <h6 class="segment-title">Basic Information</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Event Address</label>
                  <input
                    v-model="editData.event_address"
                    type="text"
                    class="form-control"
                    placeholder="Event address"
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Event Date</label>
                  <input
                    v-model="editData.event_date"
                    type="date"
                    class="form-control"
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Event Time</label>
                  <input
                    v-model="editData.event_time"
                    type="time"
                    class="form-control"
                  />
                </div>
              </div>
            </div>

            <!-- Segment: Event Details -->
            <div class="segment mb-3">
              <h6 class="segment-title">Event Details</h6>
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label">Children Count</label>
                  <input
                    v-model.number="editData.children_count"
                    type="number"
                    class="form-control"
                    min="0"
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Birthday Child Age</label>
                  <input
                    v-model.number="editData.birthday_child_age"
                    type="number"
                    class="form-control"
                    min="0"
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Performers</label>
                  <input
                    v-model.number="editData.performers_count"
                    type="number"
                    class="form-control"
                    min="1"
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Duration (hours)</label>
                  <input
                    v-model.number="editData.duration_hours"
                    type="number"
                    class="form-control"
                    min="1"
                  />
                </div>
              </div>
            </div>

            <!-- Segment: Status & Payments -->
            <div class="segment mb-3">
              <h6 class="segment-title">Status & Payments</h6>
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Status</label>
                  <select v-model="editData.status" class="form-select">
                    <option value="new">New</option>
                    <option value="under_review">Under Review</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="cancelled">Cancelled</option>
                  </select>
                  <small v-if="statusChangedByPayment" class="text-success">
                    <i class="bi bi-check-circle"></i> Status automatically changed to confirmed
                  </small>
                </div>
                <div class="col-md-4">
                  <div class="form-check mt-4">
                    <input
                      v-model="editData.is_paid"
                      class="form-check-input"
                      type="checkbox"
                      id="isPaid"
                      @change="handlePaymentChange"
                    >
                    <label class="form-check-label" for="isPaid">
                      Payment Received
                    </label>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-check mt-4">
                    <input
                      v-model="editData.is_invoiced"
                      class="form-check-input"
                      type="checkbox"
                      id="isInvoiced"
                    >
                    <label class="form-check-label" for="isInvoiced">
                      Invoice Sent
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Segment: Notes & Instructions -->
            <div class="segment mb-3">
              <h6 class="segment-title">Notes & Instructions</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Arrival/Parking Instructions</label>
                  <textarea
                    v-model="editData.arrival_parking_instructions"
                    class="form-control"
                    rows="3"
                    placeholder="Special instructions for arrival and parking"
                  ></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Song Requests</label>
                  <textarea
                    v-model="editData.song_requests"
                    class="form-control"
                    rows="3"
                    placeholder="Special song requests"
                  ></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Customer Notes</label>
                  <textarea
                    v-model="editData.customer_notes"
                    class="form-control"
                    rows="3"
                    placeholder="Notes from customer"
                  ></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Internal Notes</label>
                  <textarea
                    v-model="editData.internal_notes"
                    class="form-control"
                    rows="3"
                    placeholder="Internal team notes"
                  ></textarea>
                </div>
              </div>
            </div>

            <!-- Read-only Information -->
            <div class="segment mb-3">
              <h6 class="segment-title">Read-Only Information</h6>
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label">Customer</label>
                  <input
                    :value="editData.customer_name || editData.full_name || 'N/A'"
                    type="text"
                    class="form-control"
                    readonly
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Service</label>
                  <input
                    :value="editData.service_name || 'N/A'"
                    type="text"
                    class="form-control"
                    readonly
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Total Amount</label>
                  <input
                    :value="formatCurrency(editData.total_amount)"
                    type="text"
                    class="form-control"
                    readonly
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Location</label>
                  <input
                    :value="editData.location || `${editData.city_name || ''}, ${editData.county_name || ''}` || 'N/A'"
                    type="text"
                    class="form-control"
                    readonly
                  />
                </div>
              </div>
            </div>

          </form>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            <i class="bi bi-x-circle"></i> Cancel
          </button>
          <button type="button" class="btn btn-warning" @click="saveReservation" :disabled="saving">
            <i class="bi bi-check-circle"></i>
            {{ saving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
import { watch, ref } from "vue";
import api from "@/services/axios";

const editData = ref({});
const saving = ref(false);
const statusChangedByPayment = ref(false);

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
    editData.value = { ...newData };
    statusChangedByPayment.value = false; // Reset notification
    // Convert date format if needed
    if (editData.value.event_date && typeof editData.value.event_date === 'object') {
      const date = new Date(editData.value.event_date);
      editData.value.event_date = date.toISOString().split('T')[0];
    }
  },
  { deep: true, immediate: true }
);

const closeModal = () => {
  emit("close");
};

const handlePaymentChange = () => {
  // Si se marca como pagado, cambiar automáticamente el estado a confirmado
  if (editData.value.is_paid && editData.value.status !== 'confirmed') {
    editData.value.status = 'confirmed';
    statusChangedByPayment.value = true;
    // Ocultar el mensaje después de 3 segundos
    setTimeout(() => {
      statusChangedByPayment.value = false;
    }, 3000);
  }
};

const saveReservation = async () => {
  saving.value = true;
  try {
    const dataToSave = {
      event_address: editData.value.event_address,
      event_date: editData.value.event_date,
      event_time: editData.value.event_time,
      children_count: editData.value.children_count,
      birthday_child_age: editData.value.birthday_child_age,
      performers_count: editData.value.performers_count,
      duration_hours: editData.value.duration_hours,
      status: editData.value.status,
      is_paid: editData.value.is_paid ? 1 : 0,
      is_invoiced: editData.value.is_invoiced ? 1 : 0,
      arrival_parking_instructions: editData.value.arrival_parking_instructions,
      song_requests: editData.value.song_requests,
      customer_notes: editData.value.customer_notes,
      internal_notes: editData.value.internal_notes,
    };

    await api.put(`/reservations/${editData.value.id}`, dataToSave);
    emit("saved");
    emit("close");
  } catch (error) {
    console.error("Error saving reservation:", error);
    alert("Error saving reservation. Please try again.");
  } finally {
    saving.value = false;
  }
};

const formatCurrency = (amount) => {
  if (amount == null) return "-";
  return parseFloat(amount).toLocaleString("en-US", {
    style: "currency",
    currency: "USD"
  });
};
</script>

