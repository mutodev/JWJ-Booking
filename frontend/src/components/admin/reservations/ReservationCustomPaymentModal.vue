<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" style="z-index: 1055">
    <div class="modal-dialog modal-md" style="z-index: 1056"><div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-sliders me-2"></i>Personalized payment</h5>
        <button type="button" class="btn-close" @click="close" />
      </div>
      <div class="modal-body">
        <div class="alert alert-light border small mb-3">
          <div class="d-flex justify-content-between"><span>Total</span><strong>{{ money(reservation?.total_amount) }}</strong></div>
          <div class="d-flex justify-content-between"><span>Already paid</span><strong>{{ money(reservation?.amount_paid) }}</strong></div>
          <div class="d-flex justify-content-between text-primary"><span>Outstanding balance</span><strong>{{ money(outstanding) }}</strong></div>
        </div>
        <div v-if="pending" class="alert alert-warning">
          A personalized payment link for {{ money(pending.amount) }} is already pending.
          <button class="btn btn-sm btn-outline-dark ms-2" :disabled="busy" @click="resend">Resend / reactivate</button>
        </div>
        <template v-else>
          <div class="mb-2"><label class="form-label">Amount</label><input v-model.number="form.amount" type="number" min="0.01" :max="outstanding" step="0.01" class="form-control" /></div>
          <div class="mb-2"><label class="form-label">Customer name</label><input v-model="form.customer_name" maxlength="150" class="form-control" /></div>
          <div class="mb-2"><label class="form-label">Customer email</label><input v-model="form.customer_email" type="email" class="form-control" /></div>
          <div><label class="form-label">Description</label><textarea v-model="form.description" maxlength="255" rows="2" class="form-control" /></div>
        </template>
        <p v-if="error" class="text-danger small mt-2 mb-0">{{ error }}</p>
        <p v-if="created" class="text-success small mt-2 mb-0">The personalized link was created and emailed to the customer.</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" @click="close">Close</button>
        <button v-if="!pending" class="btn btn-success" :disabled="busy || outstanding <= 0" @click="save">
          <span v-if="busy" class="spinner-border spinner-border-sm me-1" /> Create and email link
        </button>
      </div>
    </div></div>
    <div class="modal-backdrop fade show" style="z-index: 1054" @click="close" />
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import api from "@/services/axios";

const props = defineProps({ show: Boolean, reservation: { type: Object, default: () => ({}) } });
const emit = defineEmits(["close", "saved"]);
const links = ref([]); const busy = ref(false); const error = ref(""); const created = ref(false);
const num = (value) => Number.parseFloat(value) || 0;
const outstanding = computed(() => Math.max(0, num(props.reservation?.outstanding_balance ?? props.reservation?.balance_due)));
const pending = computed(() => links.value.find((link) => link.status === "pending") || null);
const form = ref({ amount: 0, customer_name: "", customer_email: "", description: "" });
const money = (value) => num(value).toLocaleString("en-US", { style: "currency", currency: "USD" });

async function load() {
  if (!props.reservation?.id) return;
  try {
    const response = await api.get(`/reservations/${props.reservation.id}/payment-links`);
    links.value = response.data?.data ?? response.data ?? [];
  } catch { links.value = []; }
}
function reset() {
  error.value = ""; created.value = false;
  form.value = { amount: outstanding.value, customer_name: props.reservation?.full_name || props.reservation?.customer_name || "", customer_email: props.reservation?.email || "", description: `Personalized payment for reservation ${props.reservation?.id || ""}` };
}
async function save() {
  if (num(form.value.amount) <= 0 || num(form.value.amount) > outstanding.value) { error.value = "The amount must be within the outstanding balance."; return; }
  busy.value = true; error.value = "";
  try { await api.post("/payment-links", { ...form.value, reservation_id: props.reservation.id }); created.value = true; await load(); emit("saved"); }
  catch (e) { error.value = e.response?.data?.message || "Could not create the personalized link."; }
  finally { busy.value = false; }
}
async function resend() {
  busy.value = true; error.value = "";
  try { await api.post(`/payment-links/${pending.value.id}/send-email`); created.value = true; }
  catch (e) { error.value = e.response?.data?.message || "Could not resend the personalized link."; }
  finally { busy.value = false; }
}
function close() { emit("close"); }
watch(() => props.show, async (visible) => { if (visible) { reset(); await load(); } });
</script>
