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
            <div class="breakdown-item d-flex justify-content-between" v-if="extraChildrenTotal > 0">
              <span>Extra Children ({{ getExtraChildrenCount() }} over 40):</span>
              <span>${{ extraChildrenTotal.toFixed(2) }}</span>
            </div>
            <hr class="my-2">
            <div class="breakdown-total d-flex justify-content-between fw-bold">
              <span>Sub Total:</span>
              <span>${{ subtotal.toFixed(2) }}</span>
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
  kids: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["setData"]);

const showBreakdown = ref(true);
const isConfirmed = ref(false);

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

// Calcular niños extra y su costo
const extraChildrenTotal = computed(() => {
  if (!props.service || !props.kids) return 0;

  // Obtener cantidad de niños seleccionados
  let selectedKids = 0;
  if (props.kids.count) {
    // Para opción custom como "+40 kids" con cantidad específica
    selectedKids = parseInt(props.kids.count);
  } else if (props.kids.selectedKids) {
    // Para opción directa con selectedKids
    selectedKids = parseInt(props.kids.selectedKids);
  }

  // Límite fijo de 40 niños sin cargos extra
  const maxKidsIncluded = 40;

  // Calcular niños extra solo después de 40
  const extraKids = Math.max(0, selectedKids - maxKidsIncluded);

  // Calcular costo de niños extra
  const extraChildFee = parseFloat(props.service.extra_child_fee || 0);

  return extraKids * extraChildFee;
});

// Calcular subtotal
const subtotal = computed(() => {
  return servicePrice.value + addonsTotal.value + extraChildrenTotal.value;
});

// Función para obtener la cantidad de niños extra
function getExtraChildrenCount() {
  if (!props.kids) return 0;

  let selectedKids = 0;
  if (props.kids.count) {
    selectedKids = parseInt(props.kids.count);
  } else if (props.kids.selectedKids) {
    selectedKids = parseInt(props.kids.selectedKids);
  }

  return Math.max(0, selectedKids - 40);
}

// Emitir datos cuando cambie el subtotal
function emitSubtotalData() {
  const subtotalData = {
    subtotal: subtotal.value,
    servicePrice: servicePrice.value,
    addonsTotal: addonsTotal.value,
    extraChildrenTotal: extraChildrenTotal.value,
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

.confirmation-section {
  margin-top: 3rem;
}

.confirmation-btn {
  font-size: 1.1rem;
  font-weight: 600;
  padding: 0.75rem 2rem;
  border-radius: 50px;
  transition: all 0.3s ease;
  border-width: 2px;
}

.confirmation-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
}

.confirmation-btn.btn-success {
  background-color: #198754;
  border-color: #198754;
  color: white;
}

.confirmation-btn.btn-outline-success {
  background-color: transparent;
  border-color: #198754;
  color: #198754;
}

.confirmation-btn.btn-outline-success:hover {
  background-color: #198754;
  border-color: #198754;
  color: white;
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