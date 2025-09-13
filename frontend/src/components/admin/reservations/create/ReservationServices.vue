<template>
  <div class="row justify-content-center">
    <!-- Select de servicios -->
    <div class="col-5">
      <div class="form-group">
        <label for="service">Service</label>
        <Multiselect
          id="service"
          v-model="service"
          :options="serviceList"
          label="name"
          track-by="id"
          placeholder="Select a service"
          @select="onSelectService"
        />
      </div>
    </div>
  </div>

  <!-- Cards de precios -->
  <div class="row justify-content-center mt-3">
    <div class="d-flex flex-row gap-3 flex-wrap justify-content-center">
      <div
        v-for="price in servicePriceList"
        :key="price.id"
        class="card price-card"
        :class="{
          'selected-card': selectedPrice?.id === price.id,
          'standard-card': price.price_type === 'standard',
          'other-card': price.price_type !== 'standard',
        }"
        @click="selectPrice(price)"
        role="button"
        tabindex="0"
      >
        <!-- Header -->
        <div class="card-header py-1 fw-semibold text-white text-center">
          {{ price.price_type?.toUpperCase() || "N/A" }}
        </div>

        <!-- Body -->
        <div class="card-body py-2 d-flex flex-column">
          <!-- Precio -->
          <h5 class="fw-bold mb-2 text-center text-primary">
            <i class="bi bi-cash-coin me-1" aria-hidden="true"></i>
            {{ formatCurrency(price.amount) }}
          </h5>

          <!-- Extra child fee (si aplica) -->
          <div class="text-center small mb-2">
            <span class="text-danger d-inline-flex align-items-center gap-1">
              <i class="bi bi-coin" aria-hidden="true"></i>
              {{ formatCurrency(price.extra_child_fee) }} / child
            </span>
          </div>

          <hr class="my-1" />

          <!-- Info en lista -->
          <ul class="list-unstyled small mb-0">
            <li class="mb-1 d-flex align-items-center">
              <i class="bi bi-clock text-warning me-2" aria-hidden="true"></i>
              {{ Math.round((price.min_duration_hours || 0) * 60) }} min
            </li>
            <li class="mb-1 d-flex align-items-center">
              <i
                class="bi bi-people-fill text-success me-2"
                aria-hidden="true"
              ></i>
              {{ price.performers_count ?? "-" }} performers
            </li>

            <!-- Icono para "Up to X children" -->
            <li
              v-if="
                price.max_children !== undefined && price.max_children !== null
              "
              class="mb-1 d-flex align-items-center"
            >
              <i class="bi bi-people text-info me-2" aria-hidden="true"></i>
              Up to <span class="fw-semibold ms-1">{{ price.max_children }} </span> children
            </li>
          </ul>

          <!-- Notas -->
          <div
            v-if="price.notes"
            class="mt-2 d-flex align-items-start small text-muted fst-italic notes-block"
          >
            <i
              class="bi bi-info-circle text-secondary me-2 mt-1"
              aria-hidden="true"
            ></i>
            <span :title="price.notes" class="notes-text">
              {{ truncateText(price.notes, 30) }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import api from "@/services/axios";

const props = defineProps({
  services: { type: Array, default: () => [] },
});

const emit = defineEmits(["priceSelected"]);

const service = ref(null);
const serviceList = ref([]);
const servicePriceList = ref([]);
const selectedPrice = ref(null);

watch(
  () => props.services,
  (newData) => {
    serviceList.value = [...newData];
  },
  { immediate: true }
);

const onSelectService = async (selected) => {
  servicePriceList.value = [];
  selectedPrice.value = null;

  try {
    const response = await api.get(
      `/service-prices/get-by-service-and-county/${selected.id}/33c3d4e5-f6a7-48b9-0123-456789abcdef`
    );
    servicePriceList.value = response.data;
  } catch (err) {
    console.error(err);
    servicePriceList.value = [];
  }
};

const selectPrice = (price) => {
  selectedPrice.value = price;
  emit("priceSelected", price);
};

// Formato moneda
const formatCurrency = (value) => {
  if (value === null || value === undefined) return "$0.00";
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    maximumFractionDigits: 2,
  }).format(Number(value));
};

// Truncar texto con máximo de caracteres
const truncateText = (text, maxLength) => {
  if (!text) return "";
  return text.length > maxLength ? text.slice(0, maxLength) + "..." : text;
};
</script>

<style scoped>
.price-card {
  width: 200px;
  border-radius: 0.8rem;
  border: none;
  cursor: pointer;
  transition: all 0.18s ease-in-out;
  font-size: 0.85rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  margin-bottom: 8px;
}
.price-card:hover {
  transform: translateY(-3px);
}
.price-card .card-header {
  font-size: 0.75rem;
  border-radius: 0.8rem 0.8rem 0 0;
}
.standard-card .card-header {
  background: var(--bs-primary);
}
.other-card .card-header {
  background: var(--bs-success);
}
.selected-card {
  transform: translateY(-3px);
  box-shadow: 0 0 12px rgba(13, 110, 253, 0.35);
}
.notes-block {
  line-height: 1.2;
}
.notes-text {
  display: inline-block;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
