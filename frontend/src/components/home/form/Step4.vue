<template>
  <div class="container py-5">
    <!-- Título -->
    <div class="d-flex align-items-center justify-content-center mb-5">
      <i class="bi bi-calculator fs-3 me-2 text-dark"></i>
      <h2 class="mb-0">Sub Total</h2>
    </div>

    <!-- Valor centrado -->
    <div class="text-center">
      <div class="subtotal-display">
        <span class="currency">$</span>
        <span class="amount">{{ subtotal.toFixed(2) }}</span>
      </div>

      <!-- Desglose opcional -->
      <div class="breakdown mt-4" v-if="showBreakdown">
        <div class="row justify-content-center">
          <div class="col-md-8 col-lg-6">
            <!-- Base rate -->
            <div class="breakdown-item d-flex justify-content-between">
              <span>Base Service Rate:</span>
              <span>${{ servicePrice.toFixed(2) }}</span>
            </div>

            <!-- Add-ons (listado detallado) -->
            <template v-if="props.addons && props.addons.length > 0">
              <div
                v-for="(addon, index) in props.addons"
                :key="index"
                class="breakdown-item d-flex justify-content-between"
                v-show="addon.is_referral_service !== '1' && addon.is_referral_service !== 1 && addon.is_referral_service !== true"
              >
                <span>{{ addon.name }} <span v-if="addon.quantity > 1">(x{{ addon.quantity }})</span>:</span>
                <span>${{ ((parseFloat(addon.selectedPrice || addon.base_price || 0)) * (parseInt(addon.quantity || 1))).toFixed(2) }}</span>
              </div>
            </template>

            <!-- Extra children -->
            <div class="breakdown-item d-flex justify-content-between" v-if="extraChildrenTotal > 0">
              <span>Extra Children ({{ getExtraChildrenCount() }} over {{ getMaxKidsIncluded() }}):</span>
              <span>${{ extraChildrenTotal.toFixed(2) }}</span>
            </div>

            <!-- Subtotal before discount -->
            <div class="breakdown-item d-flex justify-content-between border-top pt-2 mt-2 fw-semibold">
              <span>Subtotal (Service + Add-ons):</span>
              <span>${{ baseAmount.toFixed(2) }}</span>
            </div>

            <!-- Promo code input -->
            <div class="promo-code-section mt-3 mb-3">
              <label class="form-label small fw-semibold text-muted">Promo Code (Optional)</label>
              <div class="input-group">
                <input
                  v-model="promoCode"
                  type="text"
                  class="form-control"
                  :class="{ 'is-valid': promoValid, 'is-invalid': promoInvalid }"
                  placeholder="Enter promo code (auto-validates)"
                  @input="resetPromoValidation"
                  @keyup.enter="validatePromoCode"
                />
                <button
                  class="btn promo-apply-btn"
                  type="button"
                  @click="validatePromoCode"
                  :disabled="!promoCode || promoValidating"
                >
                  {{ promoValidating ? 'Validating...' : 'Apply' }}
                </button>
              </div>
              <div class="promo-disclaimer mt-2">
                <small>*Discounts only apply to the base rate and not to travel fees.</small>
              </div>
              <div v-if="promoValid" class="valid-feedback d-block">
                <i class="bi bi-check-circle-fill me-1"></i>
                Promo code applied successfully!
              </div>
              <div v-if="promoInvalid" class="invalid-feedback d-block">
                <i class="bi bi-x-circle-fill me-1"></i>
                {{ promoErrorMessage }}
              </div>
            </div>

            <!-- Discount -->
            <div class="breakdown-item d-flex justify-content-between text-success" v-if="discount > 0">
              <span>Discount ({{ promoCodeData?.discount_percentage }}%):</span>
              <span>-${{ discount.toFixed(2) }}</span>
            </div>

            <!-- Subtotal after discount (only show if discount applied) -->
            <div v-if="discount > 0" class="breakdown-item d-flex justify-content-between fw-semibold text-primary">
              <span>Subtotal (after discount):</span>
              <span>${{ (baseAmount - discount).toFixed(2) }}</span>
            </div>

            <!-- Travel fee -->
            <div class="breakdown-item d-flex justify-content-between mt-2" v-if="travelFee > 0">
              <span>Travel Fee ({{ getZoneName() }}):</span>
              <span>${{ travelFee.toFixed(2) }}</span>
            </div>

            <hr class="my-2">

            <!-- Total -->
            <div class="breakdown-total d-flex justify-content-between fw-bold">
              <span>Grand Total:</span>
              <span>${{ subtotal.toFixed(2) }}</span>
            </div>

            <!-- Discount note -->
            <div v-if="discount > 0 || promoCode" class="discount-note mt-2">
              <i class="bi bi-info-circle me-1"></i>
              <small>*Discount applies to service, add-ons, and extra children. Travel fees are not discounted.</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Botón de confirmación -->
      <div class="confirmation-section mt-5">
        <div class="d-flex align-items-center justify-content-center">
          <input
            type="checkbox"
            class="btn-check"
            id="confirm-purchase"
            v-model="isConfirmed"
          />
          <label
            class="btn confirmation-btn d-flex align-items-center"
            :class="isConfirmed ? 'btn-success' : 'btn-outline-success'"
            for="confirm-purchase"
          >
            <i
              class="bi me-2"
              :class="isConfirmed ? 'bi-check-circle-fill' : 'bi-check-circle'"
            ></i>
            <span>Confirm Purchase</span>
          </label>
        </div>
        <p class="text-muted text-center mt-2 small">
          Please confirm your purchase to continue
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import api from "@/services/axios";
import { useToast } from "vue-toastification";

const toast = useToast();

const props = defineProps({
  active: {
    type: Boolean,
    default: false,
  },
  service: {
    type: Object,
    default: null,
  },
  addons: {
    type: Array,
    default: () => [],
  },
  kids: {
    type: Object,
    default: null,
  },
  zipcode: {
    type: Object,
    default: null,
  },
  customer: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["setData"]);

const showBreakdown = ref(true);
const isConfirmed = ref(false);
const promoCode = ref("");
const promoValid = ref(false);
const promoInvalid = ref(false);
const promoValidating = ref(false);
const promoErrorMessage = ref("");
const promoCodeData = ref(null);
let promoValidationTimeout = null;

// Calcular precio del servicio
const servicePrice = computed(() => {
  if (!props.service) return 0;
  return parseFloat(props.service.amount || 0);
});

// Calcular total de addons
const addonsTotal = computed(() => {
  if (!props.addons || props.addons.length === 0) return 0;

  const total = props.addons.reduce((total, addon) => {
    // Filtrar solo add-ons que no sean referral services
    // Comparar explícitamente con "1", 1 o true para evitar problemas con strings
    const isReferral = addon.is_referral_service === "1" || addon.is_referral_service === 1 || addon.is_referral_service === true;

    if (isReferral) {
      return total;
    }

    // Usar selectedPrice si existe (para Jukebox Live), sino usar base_price
    const price = parseFloat(addon.selectedPrice || addon.base_price || 0);
    const quantity = parseInt(addon.quantity || 1);
    const addonTotal = price * quantity;

    return total + addonTotal;
  }, 0);

  return total;
});

// Calcular tarifa de viaje basada en la zona
const travelFee = computed(() => {
  if (!props.zipcode) return 0;

  // Obtener travel_fee del zipcode
  return parseFloat(props.zipcode.travel_fee || 0);
});

// Obtener el límite de niños incluidos del servicio
function getMaxKidsIncluded() {
  if (!props.service) return 40;
  return parseInt(props.service.max_kids_included || 40);
}

// Calcular niños extra y su costo
const extraChildrenTotal = computed(() => {
  if (!props.customer) return 0;

  // Obtener cantidad de niños del customer data
  let selectedKids = 0;

  // Si seleccionaron "25+ kids", usar exactChildrenCount
  if (props.customer.childrenRange === "25+ kids" && props.customer.exactChildrenCount) {
    selectedKids = parseInt(props.customer.exactChildrenCount);
  } else if (props.customer.childrenRange === "11-24 kids") {
    // Para 11-24, usar el punto medio (17) o no cobrar extra si está dentro del límite
    selectedKids = 17;
  } else if (props.customer.childrenRange === "1-10 kids") {
    selectedKids = 5; // Punto medio
  }

  // Límite de niños incluidos en el servicio
  const maxKidsIncluded = getMaxKidsIncluded();

  // Calcular niños extra solo después del límite
  const extraKids = Math.max(0, selectedKids - maxKidsIncluded);

  // Calcular costo de niños extra
  const extraChildFee = parseFloat(props.service?.extra_child_fee || 0);

  return extraKids * extraChildFee;
});

// Calcular base amount (antes del descuento)
const baseAmount = computed(() => {
  return servicePrice.value + addonsTotal.value + extraChildrenTotal.value;
});

// Calcular descuento
const discount = computed(() => {
  if (!promoCodeData.value || !promoValid.value) return 0;

  const discountPercentage = parseFloat(promoCodeData.value.discount_percentage || 0);

  // El descuento solo aplica al base amount (no incluye travel fee)
  return (baseAmount.value * discountPercentage) / 100;
});

// Calcular subtotal
const subtotal = computed(() => {
  return baseAmount.value - discount.value + travelFee.value;
});

// Función para obtener la cantidad de niños extra
function getExtraChildrenCount() {
  if (!props.customer) return 0;

  let selectedKids = 0;

  if (props.customer.childrenRange === "25+ kids" && props.customer.exactChildrenCount) {
    selectedKids = parseInt(props.customer.exactChildrenCount);
  } else if (props.customer.childrenRange === "11-24 kids") {
    selectedKids = 17;
  } else if (props.customer.childrenRange === "1-10 kids") {
    selectedKids = 5;
  }

  return Math.max(0, selectedKids - getMaxKidsIncluded());
}

// Función para obtener el nombre de la zona
function getZoneName() {
  if (!props.zipcode) return '';

  const zoneType = props.zipcode.zone_type;
  const zoneNames = {
    'A': 'Zone A',
    'B': 'Zone B',
    'C': 'Zone C',
    'D': 'Zone D'
  };

  return zoneNames[zoneType] || 'Unknown Zone';
}

// Función para resetear validación de promo code
function resetPromoValidation() {
  promoValid.value = false;
  promoInvalid.value = false;
  promoErrorMessage.value = "";
  promoCodeData.value = null;

  // Limpiar timeout anterior
  if (promoValidationTimeout) {
    clearTimeout(promoValidationTimeout);
    promoValidationTimeout = null;
  }
}

// Función para validar promo code
async function validatePromoCode() {
  if (!promoCode.value.trim()) return;

  promoValidating.value = true;
  // No resetear aquí, solo limpiar errores previos
  promoInvalid.value = false;
  promoErrorMessage.value = "";

  try {
    const response = await api.get(`/home/promo-codes/validate/${promoCode.value.trim()}`);

    if (response.data && response.data.is_valid) {
      promoCodeData.value = response.data;
      promoValid.value = true;

      toast.success(
        `Promo code applied! You save ${response.data.discount_percentage}%`,
        { timeout: 3000 }
      );

      emitSubtotalData();
    } else {
      // Si es inválido, limpiar el promo code data
      promoCodeData.value = null;
      promoValid.value = false;
      promoInvalid.value = true;
      promoErrorMessage.value = response.data?.message || "Invalid promo code";
      emitSubtotalData();
    }
  } catch (error) {
    // Si hay error, limpiar el promo code data
    promoCodeData.value = null;
    promoValid.value = false;
    promoInvalid.value = true;
    promoErrorMessage.value = error.response?.data?.message || "Invalid or expired promo code";
    emitSubtotalData();
  } finally {
    promoValidating.value = false;
  }
}

// Emitir datos cuando cambie el subtotal
function emitSubtotalData() {
  const subtotalData = {
    subtotal: subtotal.value,
    servicePrice: servicePrice.value,
    addonsTotal: addonsTotal.value,
    extraChildrenTotal: extraChildrenTotal.value,
    travelFee: travelFee.value,
    discount: discount.value,
    promoCode: promoValid.value ? promoCode.value : null,
    promoCodeData: promoCodeData.value,
    isConfirmed: isConfirmed.value,
    isValid: isConfirmed.value && subtotal.value > 0
  };

  emit("setData", { subtotal: subtotalData });
}

// Watch para emitir datos cuando sea activo o cambien los valores
watch(
  () => [props.active, subtotal.value, isConfirmed.value],
  ([active]) => {
    if (active) {
      emitSubtotalData();
    }
  },
  { immediate: true }
);

// Watch específico para la confirmación
watch(isConfirmed, () => {
  emitSubtotalData();
});

// Watch específico para promoCodeData y discount
watch([promoCodeData, discount], () => {
  if (props.active) {
    emitSubtotalData();
  }
});
</script>

<style scoped>
.subtotal-display {
  font-size: 4rem;
  font-weight: 700;
  color: #FF74B7;
  margin: 2rem 0;
}

.currency {
  font-size: 0.8em;
  vertical-align: top;
  margin-right: 0.2em;
}

.amount {
  font-feature-settings: 'tnum';
  letter-spacing: -0.02em;
}

.breakdown {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid #e9ecef;
}

.breakdown-item {
  padding: 0.5rem 0;
  color: #6c757d;
}

.breakdown-total {
  font-size: 1.1rem;
  color: #FF74B7;
}

/* Promo code section */
.promo-code-section {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.promo-code-section .input-group {
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.promo-code-section .form-control {
  border-right: none;
  font-weight: 500;
}

.promo-code-section .form-control:focus {
  border-color: #FF74B7;
  box-shadow: 0 0 0 0.25rem rgba(255, 116, 183, 0.1);
}

/* Promo disclaimer */
.promo-disclaimer {
  color: #6b7280;
  font-size: 0.85rem;
  font-style: italic;
  text-align: left;
}

/* Apply button - Matching Next button design */
.promo-apply-btn {
  border-radius: 8px !important;
  font-weight: 600 !important;
  padding: 12px 24px !important;
  height: auto !important;
  transition: all 0.2s ease !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
  border: 2px solid #FF74B7 !important;
  background: #FF74B7 !important;
  color: black !important;
}

.promo-apply-btn:hover:not(:disabled) {
  border-color: #FF74B7 !important;
  background: #FF74B7 !important;
  color: black !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 6px rgba(255, 116, 183, 0.3) !important;
}

.promo-apply-btn:active:not(:disabled) {
  transform: translateY(0) !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}

.promo-apply-btn:disabled {
  opacity: 0.5 !important;
  cursor: not-allowed;
}

/* Discount note */
.discount-note {
  color: #6b7280;
  font-style: italic;
  text-align: center;
  padding: 0.5rem;
  background: #fffbeb;
  border-radius: 6px;
  border: 1px solid #fde68a;
}

h2 {
  font-size: 2rem;
  font-weight: 600;
  color: #333;
}

.confirmation-section {
  margin-top: 3rem;
}

.confirmation-btn {
  border-radius: 8px !important;
  font-weight: 600 !important;
  padding: 12px 24px !important;
  height: auto !important;
  transition: all 0.2s ease !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
  min-width: 200px;
  font-size: 1rem;
}

.confirmation-btn:hover {
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 6px rgba(255, 116, 183, 0.3) !important;
}

.confirmation-btn:active {
  transform: translateY(0) !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}

.confirmation-btn.btn-success {
  border: 2px solid #FF74B7 !important;
  background: #FF74B7 !important;
  color: black !important;
}

.confirmation-btn.btn-outline-success {
  border: 2px solid #d1d5db !important;
  background: white !important;
  color: #6b7280 !important;
}

.confirmation-btn.btn-outline-success:hover {
  border-color: #9ca3af !important;
  background: #f9fafb !important;
  color: #4b5563 !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
}

@media (max-width: 768px) {
  .subtotal-display {
    font-size: 3rem;
  }

  .breakdown {
    margin: 0 1rem;
  }

  .confirmation-btn {
    font-size: 1rem;
    padding: 0.65rem 1.5rem;
  }
}
</style>