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
                    <option
                      v-for="sp in serviceOptions"
                      :key="sp.id"
                      :value="sp.id"
                    >
                      {{ sp.label }}
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

            <!-- Segment: Add-ons (B6) -->
            <div class="segment mb-3">
              <h6 class="segment-title">Add-ons</h6>

              <div v-if="loadingAddons" class="text-muted small">
                <span class="spinner-border spinner-border-sm me-1"></span> Loading add-ons…
              </div>

              <div v-else>
                <div v-if="reservationAddons.length" class="table-responsive mb-3">
                  <table class="table table-sm align-middle mb-0">
                    <thead>
                      <tr>
                        <th>Add-on</th>
                        <th style="width: 120px" class="text-center">Qty</th>
                        <th style="width: 120px" class="text-end">Unit price</th>
                        <th style="width: 120px" class="text-end">Subtotal</th>
                        <th style="width: 48px"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in reservationAddons" :key="row.id">
                        <td>{{ addonName(row.addon_id) }}<span v-if="row.suboption" class="text-muted"> — {{ row.suboption }}</span></td>
                        <td class="text-center">
                          <input
                            type="number"
                            min="1"
                            class="form-control form-control-sm text-center"
                            :value="row.quantity"
                            :disabled="addonBusy"
                            @change="updateAddonQuantity(row, $event.target.value)"
                          />
                        </td>
                        <td class="text-end">{{ formatCurrency(row.price_at_time) }}</td>
                        <td class="text-end">{{ formatCurrency((Number(row.price_at_time) || 0) * (Number(row.quantity) || 1)) }}</td>
                        <td class="text-end">
                          <button
                            type="button"
                            class="btn btn-sm btn-outline-danger"
                            :disabled="addonBusy"
                            title="Remove add-on"
                            @click="removeAddon(row)"
                          >
                            <i class="bi bi-trash"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <p v-else class="text-muted small mb-3">No add-ons on this reservation yet.</p>

                <div class="row g-2 align-items-end">
                  <div class="col-md-6">
                    <label class="form-label">Add an add-on</label>
                    <select v-model="newAddonId" class="form-select form-select-sm" :disabled="addonBusy || !addonCatalog.length">
                      <option value="">{{ addonCatalog.length ? 'Select an add-on…' : 'No active add-ons available' }}</option>
                      <option v-for="a in addonCatalog" :key="a.id" :value="a.id">
                        {{ a.label }} — {{ formatCurrency(a.base_price) }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Quantity</label>
                    <input v-model.number="newAddonQty" type="number" min="1" class="form-control form-control-sm" :disabled="addonBusy" />
                  </div>
                  <div class="col-md-3">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-primary w-100"
                      :disabled="addonBusy || !newAddonId"
                      @click="addAddon"
                    >
                      <span v-if="addonBusy" class="spinner-border spinner-border-sm me-1"></span>
                      <i v-else class="bi bi-plus-lg me-1"></i>
                      Add
                    </button>
                  </div>
                </div>

                <div v-if="addonMessage" class="mt-2 small text-success">
                  <i class="bi bi-check-circle me-1"></i>{{ addonMessage }}
                </div>
                <div v-if="addonError" class="mt-2 small text-danger">
                  <i class="bi bi-x-circle me-1"></i>{{ addonError }}
                </div>
                <p class="text-muted small mt-2 mb-0">
                  Adding, changing or removing an add-on recalculates the reservation totals automatically.
                </p>
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
                  <label class="form-label">Email</label>
                  <input
                    :value="editData.email || editData.customer_email || 'N/A'"
                    type="text"
                    class="form-control"
                    readonly
                  />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Phone</label>
                  <input
                    :value="editData.phone || editData.customer_phone || 'N/A'"
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
// Servicio ya guardado en BD. props.data no se refresca dentro del modal, así
// que si el admin cambia el servicio, recalcula, y lo vuelve a cambiar en la
// misma sesión, comparar contra props.data mandaría el valor equivocado.
const persistedServicePriceId = ref(null);
// Servicio original de la reserva al abrir el modal (id + etiqueta); se mantiene
// siempre como opción aunque no esté en el catálogo del zipcode.
const originalService = ref(null);
const recalculating = ref(false);
const generatingLink = ref(false);
const sendingEmail = ref(false);
const actionMessage = ref('');
const actionError = ref('');
const confirmAction = ref(null);
const paymentLinkUrl = ref('');

// Add-ons (B6)
const reservationAddons = ref([]);
const addonCatalog = ref([]);        // catálogo plano: { id, label, base_price }
const loadingAddons = ref(false);
const addonBusy = ref(false);
const newAddonId = ref('');
const newAddonQty = ref(1);
const addonMessage = ref('');
const addonError = ref('');

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

// El servicio actual de la reserva puede no estar en el catálogo del zipcode
// (dato legacy, precio desactivado…). Lo mantenemos SIEMPRE como opción para que
// se pueda volver a él tras cambiarlo.
const serviceOptions = computed(() => {
  const opts = servicePrices.value.map((sp) => ({
    id: sp.id,
    label: `${sp.name} — ${sp.performers_count} performer(s) — ${formatCurrency(sp.amount)}`,
  }));
  const extras = [originalService.value, { id: editData.value.service_price_id, label: editData.value.service_name || 'Current service' }];
  extras.forEach((extra) => {
    if (extra?.id && !opts.some((o) => o.id === extra.id)) {
      opts.unshift({ id: extra.id, label: extra.label });
    }
  });
  return opts;
});

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
    persistedServicePriceId.value = newData?.service_price_id ?? null;
    originalService.value = newData?.service_price_id
      ? { id: newData.service_price_id, label: newData.service_name || 'Current service' }
      : null;
    confirmAction.value = null;
    reservationAddons.value = [];
    newAddonId.value = '';
    newAddonQty.value = 1;
    addonMessage.value = '';
    addonError.value = '';
    if (editData.value.event_date && typeof editData.value.event_date === 'object') {
      const date = new Date(editData.value.event_date);
      editData.value.event_date = date.toISOString().split('T')[0];
    }
    loadServicePrices();
    loadAddons();
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
    // El interceptor de axios.js ya devuelve el body desempaquetado; este
    // endpoint responde un array plano, así que `res` ES la lista.
    const res = await api.get(`/home/services/${zipcodeId}`);
    const list = Array.isArray(res) ? res : (res?.data?.data ?? res?.data ?? []);
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

// --- Add-ons (B6) -----------------------------------------------------------
// El interceptor de axios.js devuelve el body ya desempaquetado.
const unwrap = (res) => (res?.data?.data ?? res?.data ?? res);

const addonName = (addonId) => {
  const hit = addonCatalog.value.find((a) => a.id === addonId);
  return hit ? hit.label : 'Add-on';
};

const loadAddons = async () => {
  const reservationId = editData.value.id;
  if (!reservationId) return;
  loadingAddons.value = true;
  try {
    const [catalogRes, currentRes] = await Promise.all([
      api.get('/addons/active'),
      api.get(`/reservation-addons/by-reservation/${reservationId}`),
    ]);

    // El catálogo llega agrupado por tipo: [{ name, addons: [...] }]. Lo aplanamos.
    const grouped = unwrap(catalogRes);
    const flat = [];
    (Array.isArray(grouped) ? grouped : []).forEach((type) => {
      (type.addons || []).forEach((addon) => {
        flat.push({
          id: addon.id,
          label: type.name ? `${type.name}: ${addon.name}` : addon.name,
          base_price: Number(addon.base_price) || 0,
        });
      });
    });
    addonCatalog.value = flat;

    const current = unwrap(currentRes);
    reservationAddons.value = Array.isArray(current) ? current : [];
  } catch {
    addonCatalog.value = [];
    reservationAddons.value = [];
  } finally {
    loadingAddons.value = false;
  }
};

const addAddon = async () => {
  if (!newAddonId.value) return;
  const catalogEntry = addonCatalog.value.find((a) => a.id === newAddonId.value);
  const qty = Math.max(1, parseInt(newAddonQty.value, 10) || 1);
  addonBusy.value = true;
  addonMessage.value = '';
  addonError.value = '';
  try {
    const res = await api.post('/reservation-addons', {
      reservation_id: editData.value.id,
      addon_id: newAddonId.value,
      quantity: qty,
      price_at_time: catalogEntry ? catalogEntry.base_price : 0,
    });
    applyRecalcResult(unwrap(res)?.totals);
    newAddonId.value = '';
    newAddonQty.value = 1;
    addonMessage.value = 'Add-on added and totals recalculated.';
    await loadAddons();
    setTimeout(() => { addonMessage.value = ''; }, 4000);
  } catch (err) {
    addonError.value = err?.response?.data?.message ?? 'Could not add the add-on.';
  } finally {
    addonBusy.value = false;
  }
};

const updateAddonQuantity = async (row, rawValue) => {
  const qty = Math.max(1, parseInt(rawValue, 10) || 1);
  if (qty === Number(row.quantity)) return;
  addonBusy.value = true;
  addonMessage.value = '';
  addonError.value = '';
  try {
    const res = await api.put(`/reservation-addons/${row.id}`, { quantity: qty });
    applyRecalcResult(unwrap(res)?.totals);
    addonMessage.value = 'Quantity updated and totals recalculated.';
    await loadAddons();
    setTimeout(() => { addonMessage.value = ''; }, 4000);
  } catch (err) {
    addonError.value = err?.response?.data?.message ?? 'Could not update the quantity.';
    await loadAddons();
  } finally {
    addonBusy.value = false;
  }
};

const removeAddon = async (row) => {
  addonBusy.value = true;
  addonMessage.value = '';
  addonError.value = '';
  try {
    const res = await api.delete(`/reservation-addons/${row.id}`);
    applyRecalcResult(unwrap(res)?.totals);
    addonMessage.value = 'Add-on removed and totals recalculated.';
    await loadAddons();
    setTimeout(() => { addonMessage.value = ''; }, 4000);
  } catch (err) {
    addonError.value = err?.response?.data?.message ?? 'Could not remove the add-on.';
  } finally {
    addonBusy.value = false;
  }
};

const recalcTotals = async () => {
  actionMessage.value = '';
  actionError.value = '';
  recalculating.value = true;
  try {
    // Persist a service change first so the recalculation reads it from the DB.
    if (editData.value.service_price_id && editData.value.service_price_id !== persistedServicePriceId.value) {
      await api.put(`/reservations/${editData.value.id}`, {
        service_price_id: editData.value.service_price_id,
      });
      persistedServicePriceId.value = editData.value.service_price_id;
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
    paymentLinkUrl.value = link?.access_url ?? '';
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

    if (editData.value.service_price_id && editData.value.service_price_id !== persistedServicePriceId.value) {
      dataToSave.service_price_id = editData.value.service_price_id;
    }

    await api.put(`/reservations/${editData.value.id}`, dataToSave);
    persistedServicePriceId.value = editData.value.service_price_id ?? persistedServicePriceId.value;
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
