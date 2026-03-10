<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-pencil-square me-2"></i>Update Base Price by Jam
          </h5>
          <button type="button" class="btn-close" @click="close"></button>
        </div>

        <div class="modal-body">
          <p class="text-muted small mb-3">
            Updates the <strong>Base Price</strong> for <strong>all counties</strong> of the selected Jam.
            Travel fees remain unchanged.
          </p>

          <div class="mb-3">
            <label class="form-label required">Jam (Service)</label>
            <Multiselect
              v-model="selectedService"
              :options="services"
              label="name"
              track-by="id"
              placeholder="Choose a Jam..."
              :searchable="true"
              :show-labels="false"
              :allow-empty="false"
              @select="onSelectService"
            />
            <small class="text-danger small">{{ errors.service_id }}</small>
          </div>

          <div class="mb-3">
            <label for="bulkAmount" class="form-label required">New Base Price (USD)</label>
            <input
              type="number"
              class="form-control"
              id="bulkAmount"
              v-model.number="amount"
              min="0"
              step="0.01"
              placeholder="0.00"
            />
            <div v-if="currentPrice !== null" class="form-text text-muted">
              Current price: <strong>{{ formatCurrency(currentPrice) }}</strong>
              &nbsp;·&nbsp; affects <strong>{{ countiesAffected }}</strong> counties
            </div>
            <small class="text-danger small">{{ errors.amount }}</small>
          </div>

          <div v-if="successMessage" class="alert alert-success py-2 small mb-0">
            <i class="bi bi-check-circle me-1"></i>{{ successMessage }}
          </div>
          <div v-if="errors.general" class="alert alert-danger py-2 small mb-0">
            <i class="bi bi-exclamation-circle me-1"></i>{{ errors.general }}
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="close">
            <i class="bi bi-arrow-90deg-down"></i> Cancel
          </button>
          <button type="button" class="btn btn-primary" @click="submit" :disabled="loading || !selectedService">
            <i class="bi bi-save"></i> Update All Counties
            <span v-if="loading" class="spinner-border spinner-border-sm ms-2"></span>
          </button>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import api from "@/services/axios";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";

const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
  services: { type: Array, default: () => [] },
  allPrices: { type: Array, default: () => [] },
});

const selectedService = ref(null);
const amount = ref(null);
const currentPrice = ref(null);
const countiesAffected = ref(0);
const errors = ref({});
const loading = ref(false);
const successMessage = ref("");

const onSelectService = (service) => {
  const records = props.allPrices.filter(p => p.service_id === service.id);
  currentPrice.value = records.length > 0 ? records[0].amount : null;
  countiesAffected.value = records.length;
  amount.value = currentPrice.value;
  successMessage.value = "";
  errors.value = {};
};

const formatCurrency = (value) => {
  if (!value && value !== 0) return "$0.00";
  return new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(value);
};

const close = () => {
  selectedService.value = null;
  amount.value = null;
  currentPrice.value = null;
  countiesAffected.value = 0;
  successMessage.value = "";
  errors.value = {};
  emit("close");
};

const submit = async () => {
  errors.value = {};
  successMessage.value = "";

  if (!selectedService.value) {
    errors.value.service_id = "Select a Jam";
    return;
  }
  if (amount.value === null || amount.value === "" || isNaN(amount.value) || amount.value <= 0) {
    errors.value.amount = "Enter a valid price greater than $0";
    return;
  }

  try {
    loading.value = true;
    const response = await api.put("/service-prices/bulk-update-base-price", {
      service_id: selectedService.value.id,
      amount: amount.value,
    });

    const updated = response.data?.data?.updated ?? 0;
    successMessage.value = `Done! Base price updated for ${updated} counties.`;
    emit("saved");
  } catch (error) {
    errors.value.general = error.response?.data?.message || "An error occurred";
  } finally {
    loading.value = false;
  }
};
</script>
