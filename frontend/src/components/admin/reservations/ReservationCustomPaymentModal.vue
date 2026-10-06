<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" style="z-index: 1055">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" style="z-index: 1056">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Additional payment</h5>
            <small class="text-muted">Independent charge for an already-paid reservation</small>
          </div>
          <button type="button" class="btn-close" @click="close" />
        </div>

        <div class="modal-body">
          <div class="payment-summary mb-4">
            <div><span>Reservation total</span><strong>{{ money(reservation?.total_amount) }}</strong></div>
            <div><span>Additional paid</span><strong class="text-success">{{ money(paidAdditional) }}</strong></div>
            <div class="payment-summary__combined"><span>Combined paid total</span><strong>{{ money(combinedTotal) }}</strong></div>
          </div>

          <div v-if="pending" class="alert alert-info d-flex align-items-start gap-2">
            <i class="bi bi-info-circle mt-1"></i>
            <div>
              <strong>One additional payment is currently active.</strong>
              <div class="small">You can update and resend it, or cancel it before creating another one.</div>
            </div>
          </div>

          <div class="section-heading">
            <i class="bi" :class="pending ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
            <span>{{ pending ? 'Edit active payment' : 'Create additional payment' }}</span>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Amount <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input v-model.number="form.amount" type="number" min="0.01" max="10000" step="0.01" class="form-control" />
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Customer name</label>
              <input v-model="form.customer_name" maxlength="150" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Customer email <span class="text-danger">*</span></label>
              <input v-model="form.customer_email" type="email" class="form-control" />
            </div>
            <div class="col-12">
              <label class="form-label">Description <span class="text-danger">*</span></label>
              <textarea v-model="form.description" maxlength="255" rows="3" class="form-control" placeholder="Describe the additional service or charge" />
            </div>
          </div>

          <p v-if="error" class="text-danger small mt-3 mb-0">{{ error }}</p>
          <p v-if="message" class="text-success small mt-3 mb-0"><i class="bi bi-check-circle me-1"></i>{{ message }}</p>
        </div>

        <div class="modal-footer justify-content-between">
          <div class="d-flex gap-2">
            <button v-if="pending" class="btn btn-outline-danger" :disabled="busy" @click="cancelPending">
              <i class="bi bi-x-circle me-1"></i>Cancel active link
            </button>
            <button v-if="pending" class="btn btn-outline-secondary" :disabled="busy" @click="resend">
              <i class="bi bi-envelope me-1"></i>Resend
            </button>
          </div>
          <div class="d-flex gap-2 ms-auto">
            <button class="btn btn-light" @click="close">Close</button>
            <button class="btn btn-success" :disabled="busy || !formValid" @click="save">
              <span v-if="busy" class="spinner-border spinner-border-sm me-1" />
              <i v-else class="bi" :class="pending ? 'bi-arrow-repeat' : 'bi-send'"></i>
              {{ pending ? 'Update and email link' : 'Create and email link' }}
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show" style="z-index: 1054" @click="close" />
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import api from "@/services/axios";

const props = defineProps({ show: Boolean, reservation: { type: Object, default: () => ({}) } });
const emit = defineEmits(["close", "saved"]);
const links = ref([]);
const busy = ref(false);
const error = ref("");
const message = ref("");
const form = ref({ amount: null, customer_name: "", customer_email: "", description: "" });
const num = (value) => Number.parseFloat(value) || 0;
const money = (value) => num(value).toLocaleString("en-US", { style: "currency", currency: "USD" });
const pending = computed(() => links.value.find((link) => link.status === "pending") || null);
const paidAdditional = computed(() => num(props.reservation?.custom_payment_paid) || links.value.filter((l) => l.status === "paid").reduce((sum, l) => sum + num(l.amount), 0));
const combinedTotal = computed(() => num(props.reservation?.total_amount) + paidAdditional.value);
const formValid = computed(() => num(form.value.amount) > 0 && num(form.value.amount) <= 10000 && /\S+@\S+\.\S+/.test(form.value.customer_email || "") && Boolean(form.value.description?.trim()));

function defaultForm() {
  return {
    amount: null,
    customer_name: props.reservation?.full_name || props.reservation?.customer_name || "",
    customer_email: props.reservation?.email || "",
    description: "",
  };
}

function syncForm() {
  form.value = pending.value
    ? { amount: num(pending.value.amount), customer_name: pending.value.customer_name || "", customer_email: pending.value.customer_email || "", description: pending.value.description || "" }
    : defaultForm();
}

async function load() {
  if (!props.reservation?.id) return;
  try {
    const response = await api.get(`/reservations/${props.reservation.id}/payment-links`);
    links.value = response.data?.data ?? response.data ?? [];
  } catch {
    links.value = [];
  }
  syncForm();
}

async function save() {
  if (!formValid.value || busy.value) return;
  const isEditing = Boolean(pending.value);
  busy.value = true;
  error.value = "";
  message.value = "";
  try {
    const payload = { ...form.value, reservation_id: props.reservation.id };
    if (isEditing) {
      await api.put(`/payment-links/${pending.value.id}`, payload);
      message.value = "The active link was updated and emailed to the customer.";
    } else {
      await api.post("/payment-links", payload);
      message.value = "The additional payment link was created and emailed to the customer.";
    }
    await load();
    emit("saved");
    if (!isEditing) close();
  } catch (e) {
    error.value = e.response?.data?.message || "Could not save the additional payment link.";
  } finally {
    busy.value = false;
  }
}

async function resend() {
  if (!pending.value) return;
  busy.value = true;
  error.value = "";
  message.value = "";
  try {
    await api.post(`/payment-links/${pending.value.id}/send-email`);
    message.value = "The active link was emailed again.";
  } catch (e) {
    error.value = e.response?.data?.message || "Could not resend the link.";
  } finally {
    busy.value = false;
  }
}

async function cancelPending() {
  if (!pending.value || !window.confirm("Cancel this active payment link? The Stripe checkout will stop working.")) return;
  busy.value = true;
  error.value = "";
  message.value = "";
  try {
    await api.post(`/payment-links/${pending.value.id}/cancel`);
    await load();
    message.value = "The payment link was cancelled. You can now create another one.";
    emit("saved");
  } catch (e) {
    error.value = e.response?.data?.message || "Could not cancel the link.";
  } finally {
    busy.value = false;
  }
}

function close() { emit("close"); }
watch(() => props.show, async (visible) => {
  if (visible) {
    error.value = "";
    message.value = "";
    await load();
  }
});
</script>

<style scoped>
.payment-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); overflow: hidden; border: 1px solid #e5e7eb; border-radius: 10px; }
.payment-summary > div { display: flex; flex-direction: column; gap: 3px; padding: 14px 16px; border-right: 1px solid #e5e7eb; }
.payment-summary > div:last-child { border-right: 0; }
.payment-summary span { color: #6b7280; font-size: 0.75rem; }
.payment-summary strong { font-size: 1rem; }
.payment-summary__combined { background: #f8fbff; }
.section-heading { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; color: #374151; font-size: 0.9rem; font-weight: 700; }
.section-heading i { color: #0d6efd; }
@media (max-width: 768px) {
  .payment-summary { grid-template-columns: 1fr; }
  .payment-summary > div { border-right: 0; border-bottom: 1px solid #e5e7eb; }
  .payment-summary > div:last-child { border-bottom: 0; }
}
</style>
