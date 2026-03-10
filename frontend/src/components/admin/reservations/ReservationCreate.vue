<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create Reservation</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
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
            @setData="setData"
          />
          <ReservationAddons
            v-if="dataForm?.price"
            :addons="addons"
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
                Promo applied — {{ promoCodeData?.discount_percentage }}% off (excl. travel fee)
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
            :disabled="!dataForm?.form"
          >
            <i class="bi bi-save"></i>
            Save
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
import ReservationClient from "./create/ReservationClient.vue";
import ReservationAreas from "./create/ReservationAreas.vue";
import ReservationServices from "./create/ReservationServices.vue";
import ReservationAddons from "./create/ReservationAddons.vue";
import ReservationForm from "./create/ReservationForm.vue";
import ReservationTotal from "./create/ReservationTotal.vue";

const emit = defineEmits(["close", "saved"]);
const dataForm = ref({});

const props = defineProps({
  show: Boolean,
  customers: { type: Array, default: () => [] },
  areas: { type: Array, default: () => [] },
  services: { type: Array, default: () => [] },
  addons: { type: Array, default: () => [] },
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

      // Calcula el descuento sobre el precio base del servicio seleccionado
      const baseAmount = parseFloat(dataForm.value.price?.amount || 0);
      const pct = parseFloat(response.data.discount_percentage || 0);
      const discountAmount = (baseAmount * pct) / 100;

      dataForm.value.promoCode = {
        code: appliedCode.value,
        discount_amount: discountAmount,
      };
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
  try {
    await api.post('/reservations', dataForm.value);
    emit("saved");
  } catch (error) {
    // Error handled by axios interceptor
  }
};

const closeModal = () => {
  emit("close");
};
</script>
