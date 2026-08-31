<template>
  <div
    v-if="show"
    class="admin-modal admin-form modal fade show d-block"
    tabindex="-1"
    role="dialog"
  >
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-link-45deg"></i> New Payment Link</h5>
          <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <!-- Step 1: form -->
          <form v-if="!created" @submit.prevent="save">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Amount (USD) <span class="text-danger">*</span></label>
                <input
                  v-model.number="formData.amount"
                  type="number"
                  class="form-control"
                  min="0.01"
                  max="10000"
                  step="0.01"
                  placeholder="e.g. 75.00"
                  required
                />
                <small class="text-muted">Maximum $10,000 per link.</small>
              </div>

              <div class="col-md-6">
                <label class="form-label">Customer Email <span class="text-danger">*</span></label>
                <input
                  v-model.trim="formData.customer_email"
                  type="email"
                  class="form-control"
                  placeholder="customer@example.com"
                  required
                />
              </div>

              <div class="col-12">
                <label class="form-label">Description <span class="text-danger">*</span></label>
                <input
                  v-model="formData.description"
                  type="text"
                  class="form-control"
                  maxlength="255"
                  placeholder="e.g. Late fee for July 12 event"
                  required
                />
                <small class="text-muted">{{ (formData.description || '').length }}/255</small>
              </div>

              <div class="col-md-6">
                <label class="form-label">Customer Name</label>
                <input
                  v-model.trim="formData.customer_name"
                  type="text"
                  class="form-control"
                  maxlength="150"
                  placeholder="Optional"
                />
              </div>

              <div class="col-md-6">
                <label class="form-label">Reservation ID</label>
                <input
                  v-model.trim="formData.reservation_id"
                  type="text"
                  class="form-control"
                  placeholder="Optional — link this payment to a reservation"
                />
                <small class="text-muted">Leave blank for a standalone payment.</small>
              </div>
            </div>

            <div v-if="errorMessage" class="alert alert-danger mt-3 mb-0 py-2 small">
              <i class="bi bi-exclamation-circle me-1"></i>{{ errorMessage }}
            </div>
          </form>

          <!-- Step 2: created, show URL -->
          <div v-else>
            <div class="alert alert-success py-2 small mb-3">
              <i class="bi bi-check-circle me-1"></i>
              Payment link created and emailed to {{ created.customer_email }}.
            </div>
            <label class="form-label">Payment URL</label>
            <div class="input-group">
              <input type="text" class="form-control" :value="created.payment_url" readonly />
              <button class="btn btn-outline-primary" type="button" @click="copyUrl">
                <i class="bi" :class="copied ? 'bi-check-lg' : 'bi-clipboard'"></i>
                {{ copied ? 'Copied' : 'Copy' }}
              </button>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            <i class="bi bi-x-circle"></i> {{ created ? 'Close' : 'Cancel' }}
          </button>
          <button
            v-if="!created"
            type="button"
            class="btn btn-primary"
            @click="save"
            :disabled="saving"
          >
            <i class="bi bi-check-circle"></i>
            {{ saving ? 'Creating...' : 'Create Link' }}
          </button>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import api from "@/services/axios";

const emit = defineEmits(["close", "saved"]);
const props = defineProps({ show: Boolean });

const emptyForm = () => ({
  amount: null,
  customer_email: "",
  description: "",
  customer_name: "",
  reservation_id: "",
});

const formData = ref(emptyForm());
const saving = ref(false);
const created = ref(null);
const copied = ref(false);
const errorMessage = ref("");

const closeModal = () => {
  emit("close");
};

const save = async () => {
  errorMessage.value = "";
  saving.value = true;
  try {
    const payload = { ...formData.value };
    if (!payload.reservation_id) delete payload.reservation_id;
    if (!payload.customer_name) delete payload.customer_name;

    const response = await api.post("/payment-links", payload);
    created.value = response.data;
    emit("saved");
  } catch (error) {
    errorMessage.value =
      error.response?.data?.message || "Could not create the payment link.";
  } finally {
    saving.value = false;
  }
};

const copyUrl = async () => {
  if (!created.value?.payment_url) return;
  try {
    await navigator.clipboard.writeText(created.value.payment_url);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
  } catch {
    /* clipboard unavailable */
  }
};

watch(
  () => props.show,
  (visible) => {
    if (visible) {
      formData.value = emptyForm();
      created.value = null;
      copied.value = false;
      errorMessage.value = "";
    }
  }
);
</script>
