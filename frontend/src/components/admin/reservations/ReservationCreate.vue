<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-scrollable reservation-create-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-calendar-plus"></i> Create Reservation</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <div class="reservation-create-intro">
            <div class="reservation-create-intro__icon"><i class="bi bi-receipt-cutoff"></i></div>
            <div>
              <strong>Build the reservation</strong>
              <p>Select the customer, location and package. Service, add-on and custom service prices can be adjusted for this reservation only.</p>
            </div>
          </div>
          <ReservationClient :customers="customers" @setData="setData" />
          <ReservationAreas
            v-if="dataForm?.customer"
            :areas="areas"
            @setData="setData"
          />
          <ReservationServices
            v-if="dataForm?.areas?.zipcode"
            :services="services"
            :county="dataForm?.areas?.county ?? {}"
            :zipcode="dataForm?.areas?.zipcode ?? {}"
            @setData="setData"
          />
          <ReservationAddons
            v-if="dataForm?.price"
            :addons="addons"
            @setData="setData"
          />
          <ReservationCustomServices
            v-if="dataForm?.price"
            :catalog="customServices"
            @setData="setData"
          />
          <ReservationForm
            v-if="dataForm?.price"
            :addons="addons"
            @setData="setData"
          />

          <!-- Promo Code — visible as soon as a service is selected -->
          <div v-if="dataForm?.price" class="row justify-content-center mt-3">
            <div class="col-10">
              <label class="form-label small fw-semibold text-muted">
                <i class="bi bi-tag me-1"></i>Promo Code (Optional)
              </label>
              <div class="input-group input-group-sm">
                <input
                  v-model="promoCode"
                  type="text"
                  class="form-control"
                  :class="{ 'is-valid': promoValid, 'is-invalid': promoInvalid }"
                  placeholder="Enter promo code..."
                  @input="resetPromo"
                />
                <button
                  class="btn btn-outline-primary"
                  type="button"
                  @click="validatePromo"
                  :disabled="!promoCode || promoValidating"
                >
                  {{ promoValidating ? "Validating..." : "Apply" }}
                </button>
                <button
                  v-if="promoValid"
                  class="btn btn-outline-secondary"
                  type="button"
                  @click="clearPromo"
                >
                  Clear
                </button>
              </div>
              <div v-if="promoValid" class="valid-feedback d-block small">
                Promo applied — {{ promoDescription }} (excl. travel fee, expedite fee, Custom Song)
              </div>
              <div v-if="promoInvalid" class="invalid-feedback d-block small">{{ promoError }}</div>
            </div>
          </div>

          <ReservationTotal v-if="dataForm?.form" :data="dataForm" @setData="setData" />
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            <i class="bi bi-arrow-90deg-down"></i> Back
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="saveReservation"
            :disabled="!dataForm?.form || !pricingValid || saving"
          >
            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="bi bi-save"></i>
            {{ saving ? "Saving..." : "Save" }}
          </button>
        </div>
      </div>
    </div>

    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import api from "@/services/axios";
import ReservationClient from "./create/ReservationClient.vue";
import ReservationAreas from "./create/ReservationAreas.vue";
import ReservationServices from "./create/ReservationServices.vue";
import ReservationAddons from "./create/ReservationAddons.vue";
import ReservationCustomServices from "./create/ReservationCustomServices.vue";
import ReservationForm from "./create/ReservationForm.vue";
import ReservationTotal from "./create/ReservationTotal.vue";

const emit = defineEmits(["close", "saved"]);
const dataForm = ref({});
const saving = ref(false);

const props = defineProps({
  show: Boolean,
  customers: { type: Array, default: () => [] },
  areas: { type: Array, default: () => [] },
  services: { type: Array, default: () => [] },
  addons: { type: Array, default: () => [] },
  customServices: { type: Array, default: () => [] },
});

const customers = ref([]);
watch(() => props.customers, (val) => (customers.value = [...val]), { immediate: true });

const areas = ref([]);
watch(() => props.areas, (val) => (areas.value = [...val]), { immediate: true });

const services = ref([]);
watch(() => props.services, (val) => (services.value = [...val]), { immediate: true });

const addons = ref([]);
watch(() => props.addons, (val) => (addons.value = [...val]), { immediate: true });

// Promo code state
const promoCode = ref("");
const appliedCode = ref("");
const promoValid = ref(false);
const promoInvalid = ref(false);
const promoValidating = ref(false);
const promoError = ref("");
const promoCodeData = ref(null);

const setData = (data) => {
  for (const key in data) {
    if (data[key] === null) {
      delete dataForm.value[key];
    } else {
      dataForm.value[key] = data[key];
    }
  }
};

function resetPromo() {
  promoValid.value = false;
  promoInvalid.value = false;
  promoError.value = "";
  promoCodeData.value = null;
  appliedCode.value = "";
  delete dataForm.value.promoCode;
}

function clearPromo() {
  promoCode.value = "";
  resetPromo();
}

const calculatePromoDiscount = () => {
  if (!promoValid.value || !promoCodeData.value) return 0;

  const baseAmount = parseFloat(dataForm.value.price?.amount || 0);
  const addonsDiscountEligible = (dataForm.value.addons || []).reduce((sum, addon) => {
    if (addon.name === "Custom Song") return sum;
    return sum + (parseFloat(addon.base_price) || 0) * (parseInt(addon.quantity || 1, 10) || 1);
  }, 0);
  const extraChildrenQty = parseInt(dataForm.value.form?.extraChildren || 0, 10);
  const extraChildFee = parseFloat(dataForm.value.price?.extra_child_fee || 0);
  const discountBase = baseAmount + addonsDiscountEligible + (extraChildrenQty * extraChildFee);
  const discountType = promoCodeData.value.discount_type || "percentage";
  const discountValue = parseFloat(promoCodeData.value.discount_value ?? promoCodeData.value.discount_percentage ?? 0);

  return discountType === "fixed_amount"
    ? Math.min(discountValue, discountBase)
    : (discountBase * discountValue) / 100;
};

const syncAppliedPromo = () => {
  if (!promoValid.value || !appliedCode.value) return;
  dataForm.value.promoCode = {
    code: appliedCode.value,
    discount_amount: calculatePromoDiscount(),
  };
};

const promoDescription = computed(() => {
  if (promoCodeData.value?.discount_type === "fixed_amount") {
    return `${new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(Number(promoCodeData.value.discount_value || 0))} off`;
  }
  return `${promoCodeData.value?.discount_percentage || 0}% off`;
});

const pricingValid = computed(() => {
  const serviceAmount = dataForm.value.price?.amount;
  if (serviceAmount === null || serviceAmount === "" || !Number.isFinite(Number(serviceAmount)) || Number(serviceAmount) < 0) {
    return false;
  }

  const validMoney = (value) => (
    value !== null && value !== "" && Number.isFinite(Number(value)) && Number(value) >= 0
  );

  return (dataForm.value.addons || []).every((addon) => validMoney(addon.base_price))
    && (dataForm.value.customServices || []).every((item) => validMoney(item.price));
});

watch(
  () => ({
    servicePrice: dataForm.value.price?.amount,
    addons: dataForm.value.addons,
    extraChildren: dataForm.value.form?.extraChildren,
  }),
  syncAppliedPromo,
  { deep: true }
);

async function validatePromo() {
  if (!promoCode.value.trim()) return;
  promoValidating.value = true;
  promoInvalid.value = false;
  promoError.value = "";

  try {
    const response = await api.get(`/home/promo-codes/validate/${promoCode.value.trim()}`);

    if (response.data?.is_valid) {
      promoCodeData.value = response.data;
      promoValid.value = true;
      appliedCode.value = promoCode.value.trim();

      syncAppliedPromo();
    } else {
      promoCodeData.value = null;
      promoValid.value = false;
      promoInvalid.value = true;
      promoError.value = response.data?.message || "Invalid promo code";
      delete dataForm.value.promoCode;
    }
  } catch (error) {
    promoCodeData.value = null;
    promoValid.value = false;
    promoInvalid.value = true;
    promoError.value = error.response?.data?.message || "Invalid or expired promo code";
    delete dataForm.value.promoCode;
  } finally {
    promoValidating.value = false;
  }
}

const saveReservation = async () => {
  if (!dataForm.value.form || !pricingValid.value || saving.value) return;
  saving.value = true;
  try {
    await api.post('/reservations', dataForm.value);
    emit("saved");
  } catch (error) {
    // Error handled by axios interceptor
  } finally {
    saving.value = false;
  }
};

const closeModal = () => {
  emit("close");
};
</script>

<style scoped>
.modal-content,
.modal-body {
  min-height: 500px;
}

.reservation-create-dialog {
  width: min(96vw, 1480px);
  max-width: 1480px;
}

.modal-header,
.modal-footer {
  background: #fff;
  z-index: 2;
}

.modal-header {
  border-bottom-color: #e5e7eb;
}

.modal-footer {
  border-top-color: #e5e7eb;
}

.reservation-create-intro {
  display: flex;
  align-items: center;
  gap: 12px;
  max-width: 980px;
  margin: 0 auto 18px;
  padding: 14px 16px;
  border: 1px solid #dbeafe;
  border-radius: 10px;
  background: #f8fbff;
}

.reservation-create-intro__icon {
  display: grid;
  width: 38px;
  height: 38px;
  flex: 0 0 38px;
  place-items: center;
  border-radius: 9px;
  background: #e7f1ff;
  color: #0d6efd;
}

.reservation-create-intro p {
  margin: 2px 0 0;
  color: #6b7280;
  font-size: 0.82rem;
}

@media (max-width: 768px) {
  .reservation-create-dialog {
    width: auto;
    margin: 0.5rem;
  }
}
</style>
