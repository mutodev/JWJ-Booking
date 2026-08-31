<template>
  <div v-if="show" class="admin-modal admin-form modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <!-- Header -->
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-calendar-check"></i> Edit Reservation</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <!-- Body -->
        <div class="modal-body p-4">
          <form @submit.prevent="saveReservation">

            <!-- Segment: Basic Info -->
            <div class="segment mb-3">
              <h6 class="segment-title">Event details</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Reconfirm Address</label>
                  <input
                    v-model="editData.event_address"
                    type="text"
                    class="form-control"
                    placeholder="Event address"
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Date</label>
                  <input
                    v-model="editData.event_date"
                    type="date"
                    class="form-control"
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Event Start Time</label>
                  <input
                    v-model="editData.event_time"
                    type="time"
                    class="form-control"
                  />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Entertainment Start Time</label>
                  <input
                    v-model="editData.entertainment_start_time"
                    type="time"
                    class="form-control"
                    placeholder="Optional"
                  />
                  <small class="text-muted">(recommended at least 30 minutes after the party start time)</small>
                </div>
              </div>
            </div>

            <!-- Segment: Event Details -->
            <div class="segment mb-3">
              <h6 class="segment-title">Event notes</h6>
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label">Number of Children</label>
                  <input
                    v-model.number="editData.children_count"
                    type="number"
                    class="form-control"
                    min="0"
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Number of children and their age range</label>
                  <input
                    v-model="editData.children_age_range"
                    type="text"
                    class="form-control"
                    placeholder="e.g., 12 children, ages 4-8"
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
                <div class="col-md-4">
                  <label class="form-label">Birthday child’s name</label>
                  <input
                    v-model="editData.birthday_child_name"
                    type="text"
                    class="form-control"
                    placeholder="Enter name or N/A"
                  />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Age they are turning</label>
                  <input
                    v-model="editData.birthday_child_age"
                    type="text"
                    class="form-control"
                    placeholder="Enter age (1-18) or N/A"
                  />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Would you like Happy Birthday to be sung at the end of the set?</label>
                  <select v-model="editData.sing_happy_birthday" class="form-select">
                    <option value="">Please select</option>
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Segment: Service & Pricing (B6) -->
            <div class="segment mb-3">
              <h6 class="segment-title">Service &amp; Pricing</h6>
              <div class="row g-3 align-items-end">
                <div class="col-md-8">
                  <label class="form-label">Service</label>
                  <select
                    v-model="editData.service_price_id"
                    class="form-select"
                    :disabled="loadingServices"
                  >
                    <option v-if="!servicePrices.length" :value="editData.service_price_id">
                      {{ editData.service_name || 'Current service' }}
                    </option>
                    <option
                      v-for="sp in servicePrices"
                      :key="sp.id"
                      :value="sp.id"
                    >
                      {{ sp.name }} — {{ sp.performers_count }} performer(s) — {{ formatCurrency(sp.amount) }}
                    </option>
                  </select>
                  <small v-if="loadingServices" class="text-muted">Loading services…</small>
                </div>
                <div class="col-md-4">
                  <button
                    type="button"
                    class="btn btn-outline-primary w-100"
                    :disabled="recalculating"
                    @click="recalcTotals"
                  >
                    <span v-if="recalculating" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="bi bi-arrow-repeat me-1"></i>
                    Recalculate totals
                  </button>
                </div>
              </div>

              <div class="row g-3 mt-1">
                <div class="col-md-3">
                  <label class="form-label">Base Service</label>
                  <input :value="formatCurrency(editData.base_price)" type="text" class="form-control" readonly />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Add-ons</label>
                  <input :value="formatCurrency(editData.addons_total)" type="text" class="form-control" readonly />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Additional Children</label>
                  <input :value="formatCurrency(editData.extra_children_fee)" type="text" class="form-control" readonly />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Travel Fee</label>
                  <input :value="formatCurrency(editData.travel_fee)" type="text" class="form-control" readonly />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Expedite Fee</label>
                  <input :value="formatCurrency(editData.expedite_fee)" type="text" class="form-control" readonly />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Discount</label>
                  <input
                    :value="editData.discount_amount > 0 ? '-' + formatCurrency(editData.discount_amount) : '$0.00'"
                    type="text"
                    class="form-control text-success fw-bold"
                    readonly
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Total Amount</label>
                  <input :value="formatCurrency(editData.total_amount)" type="text" class="form-control fw-bold" readonly />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Amount Paid</label>
                  <input :value="editData.amount_paid == null ? 'Not recorded' : formatCurrency(editData.amount_paid)" type="text" class="form-control" readonly />
                </div>
              </div>

              <div v-if="balanceDue > 0" class="alert alert-warning d-flex justify-content-between align-items-center mt-3 mb-0">
                <span><i class="bi bi-exclamation-triangle me-2"></i><strong>Balance due: {{ formatCurrency(balanceDue) }}</strong> — the customer owes the difference after the recalculation.</span>
                <button type="button" class="btn btn-sm btn-warning" :disabled="generatingLink" @click="confirmAction = 'link'">
                  <span v-if="generatingLink" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="bi bi-link-45deg me-1"></i>
                  Generate payment link for difference
                </button>
              </div>

              <div v-if="refundDue > 0" class="alert alert-info mt-3 mb-0">
                <i class="bi bi-cash-coin me-2"></i>
                <strong>Refund required: {{ formatCurrency(refundDue) }}</strong> — the new total is lower than what was paid. Process the refund manually in Stripe.
              </div>

              <div v-if="paymentLinkUrl" class="alert alert-success mt-3 mb-0">
                <i class="bi bi-check-circle me-2"></i>
                Payment link created and emailed to the customer.
                <div class="input-group input-group-sm mt-2">
                  <input type="text" class="form-control" :value="paymentLinkUrl" readonly />
                </div>
              </div>

              <div class="d-flex gap-2 mt-3">
                <button type="button" class="btn btn-outline-secondary" :disabled="sendingEmail" @click="confirmAction = 'email'">
                  <span v-if="sendingEmail" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="bi bi-envelope me-1"></i>
                  Send update email
                </button>
              </div>

              <div v-if="actionMessage" class="mt-2 small text-success">
                <i class="bi bi-check-circle me-1"></i>{{ actionMessage }}
              </div>
              <div v-if="actionError" class="mt-2 small text-danger">
                <i class="bi bi-x-circle me-1"></i>{{ actionError }}
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
                    <option value="checking_availability">Checking Availability</option>
                    <option value="availability_confirmed">Availability Confirmed</option>
                    <option value="follow_up">Follow-up</option>
                    <option value="ready_for_payment_link">Ready for Payment Link</option>
                    <option value="payment_link_sent">Payment Link Sent</option>
                    <option value="payment_reminder">Payment Reminder</option>
                    <option value="booked">Booked</option>
                    <option value="get_ready_to_jam">Get Ready to Jam</option>
                    <option value="thank_you_for_jamming">Thank you for jamming</option>
                    <option value="cancelled">Cancelled</option>
                  </select>
                  <small v-if="statusChangedByPayment" class="text-success">
                    <i class="bi bi-check-circle"></i> Status automatically changed to Booked
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
              <h6 class="segment-title">Birthday and music</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Provide detailed arrival and parking instructions</label>
                  <textarea
                    v-model="editData.arrival_parking_instructions"
                    class="form-control"
                    rows="3"
                    placeholder="Special instructions for arrival and parking"
                  ></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Song requests, up to 3 (provide links)</label>
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

            <!-- Segment: Promo Code -->
            <div class="segment mb-3">
              <h6 class="segment-title">Promo Code</h6>
              <div class="row g-3 align-items-end">
                <div class="col-md-4">
                  <label class="form-label">Promo Code (Optional)</label>
                  <div class="input-group">
                    <input
                      v-model="promoCodeInput"
                      type="text"
                      class="form-control text-uppercase"
                      placeholder="Enter code..."
                      :disabled="applyingPromo"
                      @keyup.enter="applyPromoCode"
                    />
                    <button
                      class="btn btn-outline-primary"
                      type="button"
                      @click="applyPromoCode"
                      :disabled="applyingPromo || !promoCodeInput.trim()"
                    >
                      <span v-if="applyingPromo" class="spinner-border spinner-border-sm"></span>
                      <span v-else>Apply</span>
                    </button>
                  </div>
                  <!-- Feedback -->
                  <div v-if="promoMessage" class="mt-1 small" :class="promoSuccess ? 'text-success' : 'text-danger'">
                    <i :class="promoSuccess ? 'bi bi-check-circle' : 'bi bi-x-circle'"></i>
                    {{ promoMessage }}
                  </div>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Current Code</label>
                  <div class="d-flex align-items-center gap-2">
                    <span v-if="editData.promo_code" class="badge bg-success fs-6 px-3 py-2">
                      {{ editData.promo_code }}
                    </span>
                    <span v-else class="text-muted small">None applied</span>
                    <button
                      v-if="editData.promo_code"
                      class="btn btn-sm btn-outline-danger"
                      type="button"
                      @click="removePromoCode"
                      :disabled="applyingPromo"
                      title="Remove promo code"
                    >
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                </div>
                <div class="col-md-2">
                  <label class="form-label">Discount</label>
                  <input
                    :value="editData.discount_amount > 0 ? '-' + formatCurrency(editData.discount_amount) : '$0.00'"
                    type="text"
                    class="form-control text-success fw-bold"
                    readonly
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Total Amount</label>
                  <input
                    :value="formatCurrency(editData.total_amount)"
                    type="text"
                    class="form-control fw-bold"
                    readonly
                  />
                </div>
              </div>
            </div>

            <!-- Read-only Information -->
            <div class="segment mb-3">
              <h6 class="segment-title">Read-Only Information</h6>
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Client</label>
                  <input
                    :value="editData.customer_name || editData.full_name || 'N/A'"
                    type="text"
                    class="form-control"
                    readonly
                  />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Service</label>
                  <input
                    :value="editData.service_name || 'N/A'"
                    type="text"
                    class="form-control"
                    readonly
                  />
                </div>
                <div class="col-md-4">
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
        <div class="modal-footer flex-column align-items-stretch">
          <div v-if="saveError" class="alert alert-danger py-2 small mb-2">
            <i class="bi bi-x-circle me-1"></i>{{ saveError }}
          </div>
          <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-light me-2" @click="closeModal">
              <i class="bi bi-x-circle"></i> Cancel
            </button>
            <button type="button" class="btn btn-warning" @click="saveReservation" :disabled="saving">
              <i class="bi bi-check-circle"></i>
              {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show"></div>

    <ConfirmModal
      :show="confirmAction === 'link'"
      title="Generate payment link"
      :message="`This will create a Stripe payment link for <strong>${formatCurrency(balanceDue)}</strong> and email it to the customer. Only one pending link per reservation is allowed.`"
      confirm-label="Generate link"
      @cancel="confirmAction = null"
      @confirm="doGenerateLink"
    />
    <ConfirmModal
      :show="confirmAction === 'email'"
      title="Send update email"
      message="This will email the customer the updated reservation breakdown (including any balance due)."
      confirm-label="Send email"
      @cancel="confirmAction = null"
      @confirm="doSendEmail"
    />
  </div>
</template>

<script setup>
import { watch, ref, computed } from "vue";
import api from "@/services/axios";
import ConfirmModal from "@/components/admin/shared/ConfirmModal.vue";

const editData = ref({});
const saving = ref(false);
const saveError = ref("");
const statusChangedByPayment = ref(false);
const promoCodeInput = ref('');
const applyingPromo = ref(false);
const promoMessage = ref('');
const promoSuccess = ref(false);

const servicePrices = ref([]);
const loadingServices = ref(false);
const recalculating = ref(false);
const generatingLink = ref(false);
const sendingEmail = ref(false);
const actionMessage = ref('');
const actionError = ref('');
const confirmAction = ref(null);
const paymentLinkUrl = ref('');

const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
  data: {
    type: Object,
    default: () => ({}),
  },
});

const num = (v) => (v == null || v === '' ? 0 : parseFloat(v) || 0);

const balanceDue = computed(() => Math.round(num(editData.value.balance_due) * 100) / 100);

const refundDue = computed(() => {
  if (editData.value.amount_paid == null || editData.value.amount_paid === '') return 0;
  // Stripe cobra total y propina como line items separados: el monto adeudado
  // real es total_amount + gratuity_amount.
  const diff = num(editData.value.amount_paid) - (num(editData.value.total_amount) + num(editData.value.gratuity_amount));
  return diff > 0.009 ? Math.round(diff * 100) / 100 : 0;
});

watch(
  () => props.data,
  (newData) => {
    editData.value = { ...newData };
    statusChangedByPayment.value = false;
    promoCodeInput.value = '';
    promoMessage.value = '';
    actionMessage.value = '';
    actionError.value = '';
    saveError.value = '';
    paymentLinkUrl.value = '';
    servicePrices.value = [];
    confirmAction.value = null;
    if (editData.value.event_date && typeof editData.value.event_date === 'object') {
      const date = new Date(editData.value.event_date);
      editData.value.event_date = date.toISOString().split('T')[0];
    }
    loadServicePrices();
  },
  { deep: true, immediate: true }
);

const closeModal = () => {
  emit("close");
};

const handlePaymentChange = () => {
  // Si se marca como pagado, cambiar automáticamente el estado a confirmado
  if (editData.value.is_paid && editData.value.status !== 'booked') {
    editData.value.status = 'booked';
    statusChangedByPayment.value = true;
    // Ocultar el mensaje después de 3 segundos
    setTimeout(() => {
      statusChangedByPayment.value = false;
    }, 3000);
  }
};

const loadServicePrices = async () => {
  const zipcodeId = editData.value.zipcode_id;
  if (!zipcodeId) return;
  loadingServices.value = true;
  try {
    const res = await api.get(`/home/services/${zipcodeId}`);
    const list = res.data?.data ?? res.data ?? [];
    servicePrices.value = Array.isArray(list) ? list : [];
  } catch {
    servicePrices.value = [];
  } finally {
    loadingServices.value = false;
  }
};

const applyRecalcResult = (reservation) => {
  if (!reservation || typeof reservation !== 'object') return;
  const keep = ['base_price', 'addons_total', 'extra_children_fee', 'travel_fee',
    'expedite_fee', 'expedition_fee', 'discount_amount', 'total_amount', 'amount_paid',
    'balance_due', 'duration_hours', 'price_type', 'promo_code', 'performers_count',
    'service_price_id', 'service_name'];
  keep.forEach((k) => {
    if (k in reservation) editData.value[k] = reservation[k];
  });
};

const recalcTotals = async () => {
  actionMessage.value = '';
  actionError.value = '';
  recalculating.value = true;
  try {
    // Persist a service change first so the recalculation reads it from the DB.
    if (editData.value.service_price_id && editData.value.service_price_id !== props.data.service_price_id) {
      await api.put(`/reservations/${editData.value.id}`, {
        service_price_id: editData.value.service_price_id,
      });
    }
    const res = await api.post(`/reservations/${editData.value.id}/recalculate`);
    applyRecalcResult(res.data?.data ?? res.data);
    actionMessage.value = 'Totals recalculated.';
  } catch (err) {
    actionError.value = err?.response?.data?.message ?? 'Could not recalculate totals.';
  } finally {
    recalculating.value = false;
  }
};

const doGenerateLink = async () => {
  confirmAction.value = null;
  actionMessage.value = '';
  actionError.value = '';
  generatingLink.value = true;
  try {
    const payload = {
      reservation_id: editData.value.id,
      amount: balanceDue.value,
      description: `Balance due for reservation ${editData.value.id}`,
      customer_email: editData.value.email || editData.value.customer_email,
      customer_name: editData.value.customer_name || editData.value.full_name || '',
    };
    const res = await api.post('/payment-links', payload);
    const link = res.data?.data ?? res.data;
    paymentLinkUrl.value = link?.payment_url ?? '';
    actionMessage.value = 'Payment link generated.';
  } catch (err) {
    actionError.value = err?.response?.data?.message ?? 'Could not generate the payment link.';
  } finally {
    generatingLink.value = false;
  }
};

const doSendEmail = async () => {
  confirmAction.value = null;
  actionMessage.value = '';
  actionError.value = '';
  sendingEmail.value = true;
  try {
    await api.post(`/reservations/${editData.value.id}/send-update-email`);
    actionMessage.value = 'Update email sent to the customer.';
  } catch (err) {
    actionError.value = err?.response?.data?.message ?? 'Could not send the update email.';
  } finally {
    sendingEmail.value = false;
  }
};

const saveReservation = async () => {
  saving.value = true;
  saveError.value = '';
  try {
    const dataToSave = {
      event_address: editData.value.event_address,
      event_date: editData.value.event_date,
      event_time: editData.value.event_time,
      entertainment_start_time: editData.value.entertainment_start_time,
      children_count: editData.value.children_count,
      children_age_range: editData.value.children_age_range,
      birthday_child_name: editData.value.birthday_child_name,
      birthday_child_age: editData.value.birthday_child_age,
      sing_happy_birthday: editData.value.sing_happy_birthday,
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

    if (editData.value.service_price_id && editData.value.service_price_id !== props.data.service_price_id) {
      dataToSave.service_price_id = editData.value.service_price_id;
    }

    await api.put(`/reservations/${editData.value.id}`, dataToSave);
    emit("saved");
    emit("close");
  } catch (error) {
    console.error("Error saving reservation:", error);
    saveError.value = "Error saving reservation. Please try again.";
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

const applyPromoCode = async () => {
  const code = promoCodeInput.value.trim().toUpperCase();
  if (!code) return;
  applyingPromo.value = true;
  promoMessage.value = '';
  try {
    const res = await api.post(`/reservations/${editData.value.id}/promo`, { promo_code: code });
    const result = res.data?.data ?? res.data;
    editData.value.promo_code      = result.promo_code;
    editData.value.discount_amount = result.discount_amount;
    editData.value.total_amount    = result.total_amount;
    promoCodeInput.value = '';
    promoSuccess.value = true;
    promoMessage.value = `Code applied! Discount: ${formatCurrency(result.discount_amount)}`;
    setTimeout(() => { promoMessage.value = ''; }, 4000);
  } catch (err) {
    promoSuccess.value = false;
    promoMessage.value = err?.response?.data?.message ?? 'Invalid promo code';
  } finally {
    applyingPromo.value = false;
  }
};

const removePromoCode = async () => {
  applyingPromo.value = true;
  promoMessage.value = '';
  try {
    const res = await api.post(`/reservations/${editData.value.id}/promo`, { promo_code: '' });
    const result = res.data?.data ?? res.data;
    editData.value.promo_code      = null;
    editData.value.discount_amount = 0;
    editData.value.total_amount    = result.total_amount;
    promoSuccess.value = true;
    promoMessage.value = 'Promo code removed.';
    setTimeout(() => { promoMessage.value = ''; }, 3000);
  } catch (err) {
    promoSuccess.value = false;
    promoMessage.value = err?.response?.data?.message ?? 'Error removing promo code';
  } finally {
    applyingPromo.value = false;
  }
};
</script>
