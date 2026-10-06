<template>
  <div class="addon-section">
    <div class="addon-heading">
      <i class="bi bi-plus-circle"></i>
      <span>Add-ons</span>
    </div>
  </div>

  <div class="row justify-content-center g-3 addon-grid">
    <div v-for="addon in listAddons" :key="addon.id" class="col-12 col-sm-6 col-md-4 col-xl-3">
      <div
        class="card addon-card h-100"
        :class="{
          'selected-card': selectedAddons.some((a) => a.id === addon.id),
        }"
        @click="toggleSelect(addon)"
        @keydown.enter.prevent="toggleSelect(addon)"
        @keydown.space.prevent="toggleSelect(addon)"
        role="button"
        tabindex="0"
      >
        <div
          v-if="selectedAddons.some((a) => a.id === addon.id)"
          class="addon-card__check"
        >
          <i class="bi bi-check-lg"></i>
        </div>

        <div class="addon-card__title">
          {{ addon.name }}
        </div>

        <p v-if="addon.description" class="addon-card__description">
          {{ addon.description }}
        </p>

        <div class="addon-card__meta">
          <span>
            <i class="bi bi-cash-coin"></i>
            {{ formatCurrency(addon.base_price) }}
          </span>
          <span>
            <i class="bi bi-clock"></i>
            {{ formatMinutes(addon.estimated_duration_minutes) }}
          </span>
        </div>
      </div>
    </div>
  </div>

  <div v-if="selectedAddons.length" class="selected-addon-pricing">
    <div class="selected-addon-pricing__header">
      <div>
        <strong>Prices for this reservation</strong>
        <small>These amounts only apply to this reservation.</small>
      </div>
      <span class="badge rounded-pill text-bg-light">{{ selectedAddons.length }} selected</span>
    </div>

    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead>
          <tr>
            <th>Add-on</th>
            <th class="text-end">Configured</th>
            <th style="width: 190px">Reservation price</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="addon in selectedAddons" :key="`price-${addon.id}`">
            <td class="fw-semibold">{{ addon.name }}</td>
            <td class="text-end text-muted">{{ formatCurrency(addon.catalog_base_price) }}</td>
            <td>
              <div class="input-group input-group-sm">
                <span class="input-group-text">$</span>
                <input
                  :value="addon.base_price"
                  type="number"
                  min="0"
                  step="0.01"
                  inputmode="decimal"
                  class="form-control"
                  :class="{ 'is-invalid': addon.price_invalid }"
                  :aria-label="`${addon.name} price for this reservation`"
                  @input="updateAddonPrice(addon.id, $event.target.value)"
                  @blur="normalizeAddonPrice(addon.id)"
                />
              </div>
              <small v-if="addon.price_invalid" class="text-danger">Enter $0.00 or more.</small>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";

const props = defineProps({
  addons: { type: Array, default: () => [] },
  multiple: { type: Boolean, default: true },
});

const emit = defineEmits(["setData"]);

const listAddons = ref([]);
const selectedAddons = ref([]);

watch(
  () => props.addons,
  (val) => (listAddons.value = [...val]),
  { immediate: true }
);

const toggleSelect = (addon) => {
  if (props.multiple) {
    if (selectedAddons.value.some((a) => a.id === addon.id)) {
      selectedAddons.value = selectedAddons.value.filter(
        (a) => a.id !== addon.id
      );
    } else {
      selectedAddons.value.push(createSelectedAddon(addon));
    }
  } else {
    selectedAddons.value = selectedAddons.value.some((a) => a.id === addon.id)
      ? []
      : [createSelectedAddon(addon)];
  }

  emitSelection();
};

const normalizeMoney = (value) => Math.round((Number(value) || 0) * 100) / 100;

const createSelectedAddon = (addon) => {
  const catalogPrice = normalizeMoney(addon.base_price);
  return {
    ...addon,
    base_price: catalogPrice,
    catalog_base_price: catalogPrice,
    quantity: 1,
    is_custom_price: false,
    price_invalid: false,
  };
};

const emitSelection = () => {
  emit("setData", {
    addons: selectedAddons.value.map(({ price_invalid, ...addon }) => ({ ...addon })),
  });
};

const updateAddonPrice = (addonId, rawValue) => {
  const addon = selectedAddons.value.find((item) => item.id === addonId);
  if (!addon) return;

  if (rawValue === "" || !Number.isFinite(Number(rawValue)) || Number(rawValue) < 0) {
    addon.base_price = rawValue === "" ? null : rawValue;
    addon.price_invalid = true;
    emitSelection();
    return;
  }

  addon.base_price = Number(rawValue);
  addon.is_custom_price = normalizeMoney(addon.base_price) !== normalizeMoney(addon.catalog_base_price);
  addon.price_invalid = false;
  emitSelection();
};

const normalizeAddonPrice = (addonId) => {
  const addon = selectedAddons.value.find((item) => item.id === addonId);
  if (!addon || addon.price_invalid) return;
  addon.base_price = normalizeMoney(addon.base_price);
  addon.is_custom_price = addon.base_price !== normalizeMoney(addon.catalog_base_price);
  emitSelection();
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    maximumFractionDigits: 2,
  }).format(Number(value || 0));
};

const formatMinutes = (value) => {
  const minutes = Number(value || 0);
  return `${minutes} min`;
};
</script>

<style scoped>
.addon-section {
  max-width: 1200px;
  margin: 24px auto 0;
}

.addon-heading {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--bs-body-color);
  font-size: 0.95rem;
  font-weight: 800;
}

.addon-heading i {
  color: var(--bs-primary);
}

.addon-grid {
  max-width: 1200px;
  margin: 14px auto 0;
}

.selected-addon-pricing {
  max-width: 980px;
  margin: 18px auto 0;
  overflow: hidden;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #fff;
}

.selected-addon-pricing__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 12px 16px;
  border-bottom: 1px solid #e5e7eb;
  background: #f8f9fa;
}

.selected-addon-pricing__header strong,
.selected-addon-pricing__header small {
  display: block;
}

.selected-addon-pricing__header small {
  margin-top: 2px;
  color: #6b7280;
  font-size: 0.75rem;
}

.selected-addon-pricing .table > :not(caption) > * > * {
  padding: 10px 16px;
}

.addon-card {
  position: relative;
  display: flex;
  min-height: 118px;
  flex-direction: column;
  justify-content: space-between;
  padding: 14px 16px;
  border: 1px solid var(--bs-border-color);
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
  box-shadow: 0 0.125rem 0.375rem rgba(0, 0, 0, 0.05);
}

.addon-card:hover,
.addon-card:focus-visible {
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

.addon-card__check {
  position: absolute;
  top: 10px;
  right: 10px;
  display: grid;
  width: 20px;
  height: 20px;
  place-items: center;
  border-radius: 50%;
  background: #ff74b7;
  color: #fff;
  font-size: 0.72rem;
}

.addon-card__title {
  padding-right: 24px;
  color: var(--bs-body-color);
  font-size: 0.78rem;
  font-weight: 800;
  line-height: 1.3;
}

.addon-card__description {
  display: -webkit-box;
  margin: 6px 0 0;
  overflow: hidden;
  color: var(--bs-secondary-color);
  font-size: 0.68rem;
  line-height: 1.3;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.addon-card__meta {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 5px;
  margin-top: 12px;
  color: var(--bs-secondary-color);
  font-size: 0.7rem;
}

.addon-card__meta span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  white-space: nowrap;
}

.addon-card__meta span:nth-child(1) i {
  color: #20c997;
}

.addon-card__meta span:nth-child(2) i {
  color: #f59f00;
}

</style>
