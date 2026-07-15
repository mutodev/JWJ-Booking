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
  <div class="reservation-options row justify-content-center g-3" v-if="servicePriceList.length">
    <div
      v-for="price in servicePriceList"
      :key="price.id"
      class="col-12 col-md-6 col-lg-4"
    >
      <div
        class="card price-card h-100"
        :class="{
          'selected-card': selectedPrice?.id === price.id,
        }"
        @click="selectPrice(price)"
        @keydown.enter.prevent="selectPrice(price)"
        @keydown.space.prevent="selectPrice(price)"
        role="button"
        tabindex="0"
      >
        <div class="card-header price-card__header">
          <span>{{ getOptionTitle(price) }}</span>
          <i v-if="selectedPrice?.id === price.id" class="bi bi-check-circle-fill"></i>
        </div>

        <div class="card-body">
          <div class="price-card__price">
            <i class="bi bi-cash-coin" aria-hidden="true"></i>
            <span>{{ formatCurrency(price.amount) }}</span>
          </div>

          <div class="price-card__extra">
            <i class="bi bi-coin" aria-hidden="true"></i>
            {{ formatCurrency(price.extra_child_fee) }} per additional child
          </div>

          <hr class="my-2" />

          <ul class="price-card__meta">
            <li>
              <i class="bi bi-clock" aria-hidden="true"></i>
              {{ formatDuration(getPriceDurationHours(price)) }}
            </li>
            <li>
              <i class="bi bi-people-fill" aria-hidden="true"></i>
              {{ price.performers_count ?? "-" }} performer<span v-if="Number(price.performers_count || 0) !== 1">s</span>
            </li>
            <li v-if="getTravelFee(price) > 0">
              <i class="bi bi-car-front-fill" aria-hidden="true"></i>
              Travel Fee: {{ formatCurrency(getTravelFee(price)) }}
            </li>
            <li v-if="price.max_children !== undefined && price.max_children !== null">
              <i class="bi bi-people" aria-hidden="true"></i>
              Up to {{ price.max_children }} children
            </li>
          </ul>

          <div v-if="price.notes" class="price-card__notes" :title="price.notes">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <span>{{ truncateText(price.notes, 36) }}</span>
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
  county: Object,
  zipcode: Object,
});

const emit = defineEmits(["setData"]);

const service = ref(null);
const serviceList = ref([]);
const servicePriceList = ref([]);
const selectedPrice = ref(null);
const county = ref({});

watch(
  () => props.services,
  (newData) => {
    serviceList.value = [...newData];
  },
  { immediate: true }
);
watch(
  () => props.county,
  (newData) => {
    county.value = newData;
  },
  { immediate: true }
);

const onSelectService = async (selected) => {
  servicePriceList.value = [];
  selectedPrice.value = null;

  try {
    // Resolve prices through the zipcode so override_county_id is honored.
    // This mirrors the customer booking flow and avoids using the visible
    // county when a zipcode is configured to use another county's pricing.
    const response = await api.get(`/home/services/${props.zipcode.id}`);
    const prices = Array.isArray(response) ? response : (response?.data || []);
    servicePriceList.value = prices.filter(
      (price) => String(price.service_id) === String(selected.id)
    );
    emit("setData", {service: service.value, price: null});
  } catch (err) {
    console.error(err);
    servicePriceList.value = [];
  }
};

const selectPrice = (price) => {
  selectedPrice.value = price;
  emit("setData", {service: service.value, price: price});
};

const getOptionTitle = (price) => {
  if (price.price_type && price.price_type !== "standard") {
    return price.price_type.replace(/_/g, " ");
  }

  return service.value?.name || "Service option";
};

const getPriceDurationHours = (price) => {
  const baseDuration = parseFloat(
    service.value?.duration_hours ?? price.duration_hours ?? price.min_duration_hours ?? 0
  ) || 0;

  return props.zipcode?.zone_type === "minimum_2h"
    ? Math.max(baseDuration, 2)
    : baseDuration;
};

const formatDuration = (hours) => {
  const totalMinutes = Math.round((parseFloat(hours) || 0) * 60);
  if (totalMinutes <= 0) return "0 min";

  const wholeHours = Math.floor(totalMinutes / 60);
  const minutes = totalMinutes % 60;

  if (wholeHours === 0) return `${minutes} min`;
  if (minutes === 0) return `${wholeHours} hr${wholeHours === 1 ? "" : "s"}`;
  return `${wholeHours} hr ${minutes} min`;
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

// Calcular travel fee según zipcode y performers
const getTravelFee = (price) => {
  const performers = parseInt(price.performers_count || 1);
  if (props.zipcode) {
    if (performers >= 2 && props.zipcode.travel_fee_2_performers) {
      return parseFloat(props.zipcode.travel_fee_2_performers);
    }
    if (props.zipcode.travel_fee_1_performer) {
      return parseFloat(props.zipcode.travel_fee_1_performer);
    }
  }
  return parseFloat(price.travel_fee || 0);
};

// Truncar texto con máximo de caracteres
const truncateText = (text, maxLength) => {
  if (!text) return "";
  return text.length > maxLength ? text.slice(0, maxLength) + "..." : text;
};
</script>

<style scoped>
.reservation-options {
  max-width: 900px;
  margin: 16px auto 0;
}

.price-card {
  border: 1px solid var(--bs-border-color);
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
  box-shadow: 0 0.125rem 0.375rem rgba(0, 0, 0, 0.05);
}

.price-card:hover,
.price-card:focus-visible {
  border-color: var(--bs-primary);
  box-shadow: 0 0.35rem 0.875rem rgba(0, 0, 0, 0.09);
  outline: none;
  transform: translateY(-1px);
}

.selected-card {
  border-color: #ff74b7;
  box-shadow: 0 0 0 0.18rem rgba(255, 116, 183, 0.34), 0 0.65rem 1.35rem rgba(255, 116, 183, 0.3);
  transform: translateY(-2px);
}

.price-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 44px;
  border-bottom: 1px solid var(--bs-border-color);
  background: var(--bs-light);
  color: var(--bs-body-color);
  font-size: 0.9rem;
  font-weight: 800;
  line-height: 1.25;
  text-transform: capitalize;
}

.price-card__header span {
  color: var(--bs-body-color);
}

.price-card__header i {
  color: #ff74b7;
}

.price-card__price {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: var(--bs-body-color);
  font-size: 1.25rem;
  font-weight: 800;
}

.price-card__extra {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  margin-top: 8px;
  color: #dc3545;
  font-size: 0.8rem;
}

.price-card__price i {
  color: #20c997;
}

.price-card__extra i {
  color: #ff74b7;
}

.price-card__meta li:nth-child(1) i {
  color: #f59f00;
}

.price-card__meta li:nth-child(2) i {
  color: #12b886;
}

.price-card__meta li:nth-child(3) i {
  color: #339af0;
}

.price-card__meta li:nth-child(4) i {
  color: #845ef7;
}

.price-card__notes i {
  color: #868e96;
}

.price-card__meta {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin: 0;
  padding: 0;
  color: var(--bs-secondary-color);
  font-size: 0.82rem;
  list-style: none;
}

.price-card__meta li,
.price-card__notes {
  display: flex;
  align-items: center;
  gap: 8px;
}

.price-card__notes {
  margin-top: 10px;
  color: var(--bs-secondary-color);
  font-size: 0.78rem;
  line-height: 1.3;
}

</style>
