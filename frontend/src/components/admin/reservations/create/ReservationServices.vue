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
    <div class="d-flex flex-row gap-2 flex-wrap justify-content-center">
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
      >
        <!-- Header -->
        <div class="card-header py-1 fw-semibold text-white text-center">
          {{ price.price_type.toUpperCase() }}
        </div>

        <!-- Body -->
        <div class="card-body py-2">
          <!-- Precio centrado -->
          <h6 class="fw-bold mb-3 text-center">
            <span
              :class="
                price.price_type === 'standard' ? 'text-info' : 'text-success'
              "
            >
              <i class="bi bi-cash-coin"></i>
            </span>
            ${{ price.amount }}
          </h6>
          <hr />
          <!-- Duración -->
          <div class="d-flex align-items-center gap-1 small">
            <i class="bi bi-clock text-warning"></i>
            {{ price.min_duration_hours * 60 }} min
          </div>

          <!-- Performers -->
          <div class="d-flex align-items-center gap-1 small mt-1">
            <i class="bi bi-people-fill text-danger small"></i>
            {{ price.performers_count }}
          </div>

          <!-- Notas -->
          <div v-if="price.notes" class="mt-1 text-muted fst-italic small">
            <i class="bi bi-info-circle text-info"></i> {{ price.notes }}
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

  const response = await api.get(
    `/service-prices/get-by-service-and-county/${selected.id}/33c3d4e5-f6a7-48b9-0123-456789abcdef`
  );
  servicePriceList.value = response.data;
};

const selectPrice = (price) => {
  selectedPrice.value = price;
  emit("priceSelected", price);
};
</script>

<style scoped>
.price-card {
  width: 150px;
  border-radius: 0.8rem;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  font-size: 0.8rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}
.price-card:hover {
  transform: translateY(-2px);
}
.price-card .card-header {
  font-size: 0.75rem;
  border-radius: 0.8rem 0.8rem 0 0;
}
.standard-card .card-header {
  background: var(--bs-info); /* Azul sólido */
}
.other-card .card-header {
  background: var(--bs-success); /* Verde sólido */
}
.selected-card {
  transform: translateY(-2px);
  box-shadow: 0 0 10px rgba(5, 94, 226, 0.4);
}
</style>
