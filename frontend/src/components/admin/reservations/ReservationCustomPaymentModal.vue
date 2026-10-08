<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" style="z-index: 1055">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="z-index: 1056">
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
            <div><span>Reservation total</span><strong>{{ money(reservation?.reservation_total ?? reservation?.total_amount) }}</strong></div>
            <div><span>Additional charges paid</span><strong class="text-success">{{ money(paidAdditional) }}</strong></div>
            <div class="payment-summary__combined"><span>Reservation + additional charges</span><strong>{{ money(combinedTotal) }}</strong></div>
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
            <div class="col-md-6">
              <label class="form-label">Customer name</label>
              <input v-model="form.customer_name" maxlength="150" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Customer email <span class="text-danger">*</span></label>
              <input v-model="form.customer_email" type="email" class="form-control" />
            </div>
          </div>

          <div class="mt-4">
            <label class="form-label fw-semibold">Payment purpose</label>
            <div class="purpose-options">
              <label class="purpose-option" :class="{ 'purpose-option--active': form.purpose === 'balance' }">
                <input
                  v-model="form.purpose"
                  type="radio"
                  value="balance"
                  :disabled="balanceDue <= 0 && pending?.purpose !== 'balance'"
                  @change="applyPurposeDefaults"
                />
                <span><strong>Reservation balance</strong><small>Collects an amount already included in the reservation total.</small></span>
              </label>
              <label class="purpose-option" :class="{ 'purpose-option--active': form.purpose === 'additional' }">
                <input v-model="form.purpose" type="radio" value="additional" />
                <span><strong>Additional charge</strong><small>Adds a new charge on top of the reservation total.</small></span>
              </label>
            </div>
            <small v-if="balanceDue > 0" class="text-warning-emphasis d-block mt-1">Current reservation balance: {{ money(balanceDue) }}</small>
          </div>

          <!-- Optional items -->
          <section v-if="form.purpose === 'additional'" class="items-panel mt-4">
            <header class="items-panel__header">
              <div class="items-panel__icon"><i class="bi bi-bag-plus"></i></div>
              <div class="flex-grow-1">
                <strong>
                  Add-ons &amp; custom services
                  <span class="badge rounded-pill text-bg-light fw-normal ms-1">Optional</span>
                </strong>
                <small>Charge catalog items in this link. The amount is calculated automatically from the items you add.</small>
              </div>
              <span v-if="form.items.length" class="badge rounded-pill text-bg-primary">{{ form.items.length }} added</span>
            </header>

            <div class="items-panel__body">
              <div v-if="catalogLoading" class="text-muted small">
                <span class="spinner-border spinner-border-sm me-1"></span> Loading catalog…
              </div>
              <div v-else class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold mb-1"><i class="bi bi-plus-circle me-1"></i>Add an add-on</label>
                  <CustomServicePicker
                    :catalog="addonCatalog"
                    :exclude-ids="selectedIds('addon')"
                    placeholder="Search add-ons…"
                    empty-text="No active add-ons available."
                    @select="(item) => addItem('addon', item)"
                  />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold mb-1"><i class="bi bi-stars me-1"></i>Add a custom service</label>
                  <CustomServicePicker
                    :catalog="customServiceCatalog"
                    :exclude-ids="selectedIds('custom_service')"
                    placeholder="Search custom services…"
                    @select="(item) => addItem('custom_service', item)"
                  />
                </div>
              </div>

              <div v-if="form.items.length" class="table-responsive mt-3">
                <table class="table table-sm align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Item</th>
                      <th class="text-end" style="width: 120px">Catalog price</th>
                      <th style="width: 180px">Price for this link</th>
                      <th style="width: 44px"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in form.items" :key="`${item.item_type}-${item.item_id}`" :class="{ 'is-zero-price': !(num(item.price) > 0) }">
                      <td>
                        <span class="fw-semibold">{{ item.name }}</span>
                        <span class="badge ms-1" :class="item.item_type === 'addon' ? 'text-bg-info' : 'text-bg-secondary'">
                          {{ item.item_type === 'addon' ? 'Add-on' : 'Custom service' }}
                        </span>
                        <span v-if="num(item.price) !== num(item.catalog_price) && validMoney(item.price)" class="badge text-bg-warning ms-1">Custom</span>
                        <small v-if="item.detail" class="text-muted d-block">{{ item.detail }}</small>
                      </td>
                      <td class="text-end text-muted">{{ money(item.catalog_price) }}</td>
                      <td>
                        <div class="input-group input-group-sm">
                          <span class="input-group-text">$</span>
                          <input
                            v-model="item.price"
                            type="number"
                            min="0"
                            step="0.01"
                            class="form-control"
                            :class="{ 'is-invalid': !validMoney(item.price) }"
                            :aria-label="`${item.name} price for this link`"
                          />
                        </div>
                      </td>
                      <td class="text-end">
                        <button type="button" class="btn btn-sm btn-link text-danger p-0" :title="`Remove ${item.name}`" @click="removeItem(item)">
                          <i class="bi bi-x-circle-fill"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p v-else-if="!catalogLoading" class="text-muted small mt-3 mb-0">
                No items added. Leave it empty to charge a manual amount only.
              </p>
            </div>
          </section>

          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label">
                <template v-if="hasItems">Other charge <small class="text-muted">(optional)</small></template>
                <template v-else>{{ form.purpose === 'balance' ? 'Balance to collect' : 'Amount' }} <span class="text-danger">*</span></template>
              </label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input v-model="form.manual_amount" type="number" min="0" max="10000" step="0.01" class="form-control" :readonly="form.purpose === 'balance'" />
              </div>
              <small v-if="hasItems" class="text-muted">Extra amount on top of the items (e.g. late fee).</small>
            </div>
            <div class="col-md-8">
              <label class="form-label">
                Description <span v-if="!hasItems" class="text-danger">*</span>
              </label>
              <textarea
                v-model="form.description"
                maxlength="255"
                rows="2"
                class="form-control"
                :placeholder="hasItems ? suggestedDescription : 'Describe the additional service or charge'"
              />
              <small v-if="hasItems && !form.description?.trim()" class="text-muted">If empty, the item names will be used.</small>
            </div>
          </div>

          <div class="link-total mt-3">
            <div v-if="hasItems" class="link-total__row"><span>Items subtotal</span><span>{{ money(itemsSubtotal) }}</span></div>
            <div v-if="hasItems && num(form.manual_amount) > 0" class="link-total__row"><span>Other charge</span><span>{{ money(form.manual_amount) }}</span></div>
            <div class="link-total__row link-total__row--total"><span>Total to charge</span><strong>{{ money(totalAmount) }}</strong></div>
            <small v-if="totalAmount > 10000" class="text-danger">The total must not exceed $10,000.00.</small>
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
import CustomServicePicker from "./create/CustomServicePicker.vue";

const props = defineProps({ show: Boolean, reservation: { type: Object, default: () => ({}) } });
const emit = defineEmits(["close", "saved"]);
const links = ref([]);
const busy = ref(false);
const error = ref("");
const message = ref("");
const form = ref({ purpose: "additional", manual_amount: null, customer_name: "", customer_email: "", description: "", items: [] });
const addonCatalog = ref([]);
const customServiceCatalog = ref([]);
const catalogLoading = ref(false);

const num = (value) => Number.parseFloat(value) || 0;
const round2 = (value) => Math.round(num(value) * 100) / 100;
const money = (value) => num(value).toLocaleString("en-US", { style: "currency", currency: "USD" });
const validMoney = (value) => value !== null && value !== "" && Number.isFinite(Number(value)) && Number(value) >= 0;

const pending = computed(() => links.value.find((link) => link.status === "pending") || null);
const balanceDue = computed(() => round2(props.reservation?.balance_due));
const paidAdditional = computed(() => num(props.reservation?.custom_payment_paid) || links.value.filter((l) => l.status === "paid" && (l.purpose || "additional") === "additional").reduce((sum, l) => sum + num(l.amount), 0));
const paymentLinksTotal = computed(() => num(props.reservation?.custom_payment_total) || links.value.filter((l) => ["pending", "paid"].includes(l.status)).reduce((sum, l) => sum + num(l.amount), 0));
const combinedTotal = computed(() => num(props.reservation?.reservation_total ?? props.reservation?.total_amount) + paymentLinksTotal.value);

const hasItems = computed(() => form.value.items.length > 0);
const itemsSubtotal = computed(() => round2(form.value.items.reduce((sum, item) => sum + num(item.price), 0)));
const totalAmount = computed(() => round2(hasItems.value ? itemsSubtotal.value + num(form.value.manual_amount) : num(form.value.manual_amount)));
const suggestedDescription = computed(() => form.value.items.map((item) => item.name).join(", "));

const formValid = computed(() => {
  const amountOk = totalAmount.value > 0 && totalAmount.value <= 10000;
  const emailOk = /\S+@\S+\.\S+/.test(form.value.customer_email || "");
  const descriptionOk = hasItems.value || Boolean(form.value.description?.trim());
  const itemsOk = form.value.items.every((item) => validMoney(item.price));
  const extraOk = !hasItems.value || form.value.manual_amount === null || form.value.manual_amount === "" || validMoney(form.value.manual_amount);
  return amountOk && emailOk && descriptionOk && itemsOk && extraOk;
});

const selectedIds = (type) => form.value.items.filter((item) => item.item_type === type).map((item) => item.item_id);

function addItem(type, catalogItem) {
  if (selectedIds(type).includes(catalogItem.id)) return;
  form.value.items.push({
    item_type: type,
    item_id: catalogItem.id,
    name: catalogItem.name,
    detail: catalogItem.detail || null,
    catalog_price: round2(catalogItem.price),
    price: round2(catalogItem.price),
  });
}

function removeItem(item) {
  form.value.items = form.value.items.filter((i) => !(i.item_type === item.item_type && i.item_id === item.item_id));
}

function defaultForm() {
  const isBalance = balanceDue.value > 0;
  return {
    purpose: isBalance ? "balance" : "additional",
    manual_amount: isBalance ? balanceDue.value : null,
    customer_name: props.reservation?.full_name || props.reservation?.customer_name || "",
    customer_email: props.reservation?.email || "",
    description: "",
    items: [],
  };
}

function syncForm() {
  if (!pending.value) {
    form.value = defaultForm();
    return;
  }
  const items = (pending.value.items || []).map((item) => ({
    item_type: item.item_type,
    item_id: item.item_id,
    name: item.name,
    detail: item.detail || null,
    catalog_price: round2(item.catalog_price),
    price: round2(item.price),
  }));
  form.value = {
    purpose: pending.value.purpose || "additional",
    manual_amount: items.length ? (pending.value.extra_amount != null ? num(pending.value.extra_amount) : null) : num(pending.value.amount),
    customer_name: pending.value.customer_name || "",
    customer_email: pending.value.customer_email || "",
    description: pending.value.description || "",
    items,
  };
}

function applyPurposeDefaults() {
  if (form.value.purpose !== "balance") return;
  form.value.items = [];
  form.value.manual_amount = balanceDue.value;
  if (!form.value.description?.trim()) {
    form.value.description = `Balance due for reservation ${props.reservation?.id || ""}`.trim();
  }
}

async function loadCatalog() {
  catalogLoading.value = true;
  try {
    const [addonsRes, customRes] = await Promise.all([
      api.get("/addons/active"),
      api.get("/custom-services/get-all-active"),
    ]);
    const grouped = addonsRes?.data ?? [];
    addonCatalog.value = (Array.isArray(grouped) ? grouped : []).flatMap((type) =>
      (type.addons || []).map((addon) => ({ id: addon.id, name: addon.name, detail: type.name || null, price: num(addon.base_price) }))
    );
    customServiceCatalog.value = Array.isArray(customRes?.data) ? customRes.data : [];
  } catch {
    addonCatalog.value = [];
    customServiceCatalog.value = [];
  } finally {
    catalogLoading.value = false;
  }
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

function buildPayload() {
  const description = form.value.description?.trim()
    || (form.value.purpose === "balance" ? `Balance due for reservation ${props.reservation.id}` : suggestedDescription.value);
  const payload = {
    customer_name: form.value.customer_name,
    customer_email: form.value.customer_email,
    description: description.slice(0, 255),
    reservation_id: props.reservation.id,
    purpose: form.value.purpose,
  };
  if (hasItems.value) {
    payload.items = form.value.items.map((item) => ({ item_type: item.item_type, item_id: item.item_id, price: round2(item.price) }));
    payload.extra_amount = round2(form.value.manual_amount);
    payload.amount = totalAmount.value;
  } else {
    payload.items = [];
    payload.amount = round2(form.value.manual_amount);
  }
  return payload;
}

async function save() {
  if (!formValid.value || busy.value) return;
  const isEditing = Boolean(pending.value);
  busy.value = true;
  error.value = "";
  message.value = "";
  try {
    const payload = buildPayload();
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
    await Promise.all([load(), loadCatalog()]);
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
.purpose-options { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.purpose-option { display: flex; gap: 10px; padding: 12px 14px; border: 1px solid #dfe3e8; border-radius: 9px; cursor: pointer; }
.purpose-option--active { border-color: #0d6efd; background: #f5f9ff; }
.purpose-option input { margin-top: 4px; }
.purpose-option span, .purpose-option small { display: block; }
.purpose-option small { margin-top: 2px; color: #6b7280; font-size: 0.75rem; }
.section-heading { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; color: #374151; font-size: 0.9rem; font-weight: 700; }
.section-heading i { color: #0d6efd; }
.items-panel { border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; }
.items-panel__header { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid #e5e7eb; border-radius: 10px 10px 0 0; background: #f8f9fa; }
.items-panel__header strong, .items-panel__header small { display: block; }
.items-panel__header small { margin-top: 2px; color: #6b7280; font-size: 0.75rem; }
.items-panel__icon { display: grid; width: 34px; height: 34px; flex: 0 0 34px; place-items: center; border-radius: 8px; background: #e7f1ff; color: #0d6efd; }
.items-panel__body { padding: 14px 16px 16px; }
.link-total { margin-left: auto; max-width: 360px; padding: 12px 16px; border: 1px solid #e5e7eb; border-radius: 10px; background: #f8fbff; }
.link-total__row { display: flex; justify-content: space-between; gap: 12px; padding: 2px 0; color: #4b5563; font-size: 0.85rem; }
.link-total__row--total { margin-top: 4px; padding-top: 8px; border-top: 1px solid #e5e7eb; color: #111827; font-size: 1rem; }
@media (max-width: 768px) {
  .payment-summary { grid-template-columns: 1fr; }
  .payment-summary > div { border-right: 0; border-bottom: 1px solid #e5e7eb; }
  .payment-summary > div:last-child { border-bottom: 0; }
  .link-total { max-width: none; }
  .purpose-options { grid-template-columns: 1fr; }
}
</style>
