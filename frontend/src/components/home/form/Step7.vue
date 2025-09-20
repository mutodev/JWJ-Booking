<template>
  <div class="container py-5">
    <!-- Título -->
    <div class="d-flex align-items-center justify-content-center mb-5">
      <i class="bi bi-calculator fs-3 me-2 text-success"></i>
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
          <div class="col-md-6">
            <div class="breakdown-item d-flex justify-content-between">
              <span>Service:</span>
              <span>${{ servicePrice.toFixed(2) }}</span>
            </div>
            <div class="breakdown-item d-flex justify-content-between" v-if="addonsTotal > 0">
              <span>Add-ons:</span>
              <span>${{ addonsTotal.toFixed(2) }}</span>
            </div>
            <hr class="my-2">
            <div class="breakdown-total d-flex justify-content-between fw-bold">
              <span>Sub Total:</span>
              <span>${{ subtotal.toFixed(2) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";

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
  hours: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["setData"]);

const showBreakdown = ref(true);

// Calcular precio del servicio
const servicePrice = computed(() => {
  if (!props.service) return 0;
  return parseFloat(props.service.amount || 0);
});

// Calcular total de addons
const addonsTotal = computed(() => {
  if (!props.addons || props.addons.length === 0) return 0;
  return props.addons.reduce((total, addon) => {
    return total + parseFloat(addon.base_price || 0);
  }, 0);
});

// Calcular subtotal
const subtotal = computed(() => {
  return servicePrice.value + addonsTotal.value;
});

// Emitir datos cuando cambie el subtotal
function emitSubtotalData() {
  const subtotalData = {
    subtotal: subtotal.value,
    servicePrice: servicePrice.value,
    addonsTotal: addonsTotal.value,
    isValid: true
  };

  emit("setData", { subtotal: subtotalData });
}

// Watch para emitir datos cuando sea activo o cambien los valores
watch(
  () => [props.active, subtotal.value],
  ([active, newSubtotal]) => {
    if (active) {
      emitSubtotalData();
    }
  },
  { immediate: true }
);
</script>

<style scoped>
.subtotal-display {
  font-size: 4rem;
  font-weight: 700;
  color: #198754;
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
  color: #198754;
}

h2 {
  font-size: 2rem;
  font-weight: 600;
  color: #333;
}

@media (max-width: 768px) {
  .subtotal-display {
    font-size: 3rem;
  }

  .breakdown {
    margin: 0 1rem;
  }
}
</style>