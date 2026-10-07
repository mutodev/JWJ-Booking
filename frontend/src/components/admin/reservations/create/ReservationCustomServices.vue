<template>
  <section class="cs-panel">
    <header class="cs-panel__header">
      <div class="cs-panel__icon"><i class="bi bi-stars"></i></div>
      <div class="cs-panel__title">
        <strong>
          Custom Services
          <span class="badge rounded-pill text-bg-light fw-normal ms-1">Optional</span>
        </strong>
        <small>Add special services to this reservation. You can adjust the price for this reservation only.</small>
      </div>
      <span v-if="selected.length" class="badge rounded-pill text-bg-primary">
        {{ selected.length }} added
      </span>
    </header>

    <div class="cs-panel__body">
      <label class="form-label small fw-semibold mb-1">Add a custom service</label>
      <CustomServicePicker
        :catalog="catalog"
        :exclude-ids="selected.map((s) => s.custom_service_id)"
        @select="add"
      />

      <div v-if="!catalog.length" class="cs-panel__notice">
        <i class="bi bi-info-circle"></i>
        <span>
          There are no <strong>active</strong> custom services. Create or activate them in
          <router-link to="/admin/services/custom-services" target="_blank">Services › Custom Services</router-link>.
        </span>
      </div>

      <div v-if="selected.length" class="table-responsive mt-3">
        <table class="table table-sm align-middle mb-0 cs-table">
          <thead>
            <tr>
              <th>Service</th>
              <th class="text-end" style="width: 120px">Catalog price</th>
              <th style="width: 180px">Price for this reservation</th>
              <th style="width: 44px"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in selected" :key="item.custom_service_id" :class="{ 'is-zero-price': !(parseFloat(item.price) > 0) }">
              <td>
                <span class="fw-semibold">{{ item.name }}</span>
                <span v-if="item.is_custom_price" class="badge text-bg-warning ms-1">Custom</span>
                <small v-if="item.detail" class="text-muted d-block">{{ item.detail }}</small>
              </td>
              <td class="text-end text-muted">{{ formatCurrency(item.catalog_price) }}</td>
              <td>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">$</span>
                  <input
                    :value="item.price"
                    type="number"
                    min="0"
                    step="0.01"
                    inputmode="decimal"
                    class="form-control"
                    :class="{ 'is-invalid': item.price_invalid }"
                    :aria-label="`${item.name} price for this reservation`"
                    @input="updatePrice(item.custom_service_id, $event.target.value)"
                    @blur="normalizePrice(item.custom_service_id)"
                  />
                </div>
                <small v-if="item.price_invalid" class="text-danger">Enter $0.00 or more.</small>
              </td>
              <td class="text-end">
                <button
                  type="button"
                  class="btn btn-sm btn-link text-danger p-0"
                  :title="`Remove ${item.name}`"
                  @click="remove(item.custom_service_id)"
                >
                  <i class="bi bi-x-circle-fill"></i>
                </button>
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="2" class="text-end small text-muted">Custom services subtotal</td>
              <td colspan="2" class="fw-bold">{{ formatCurrency(subtotal) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <p v-else-if="catalog.length" class="cs-panel__empty">
        No custom services added. Use the search above to add one.
      </p>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from "vue";
import CustomServicePicker from "./CustomServicePicker.vue";

defineProps({
  catalog: { type: Array, default: () => [] },
});

const emit = defineEmits(["setData"]);

const selected = ref([]);

const normalizeMoney = (value) => Math.round((Number(value) || 0) * 100) / 100;

const subtotal = computed(() =>
  selected.value.reduce((sum, item) => sum + (Number(item.price) || 0), 0)
);

const emitSelection = () => {
  emit("setData", {
    customServices: selected.value.map(({ price_invalid, ...item }) => ({ ...item })),
  });
};

const add = (service) => {
  if (!service || selected.value.some((s) => s.custom_service_id === service.id)) return;
  const catalogPrice = normalizeMoney(service.price);
  selected.value.push({
    custom_service_id: service.id,
    name: service.name,
    detail: service.detail,
    catalog_price: catalogPrice,
    price: catalogPrice,
    is_custom_price: false,
    price_invalid: false,
  });
  emitSelection();
};

const remove = (id) => {
  selected.value = selected.value.filter((s) => s.custom_service_id !== id);
  emitSelection();
};

const updatePrice = (id, rawValue) => {
  const item = selected.value.find((s) => s.custom_service_id === id);
  if (!item) return;

  if (rawValue === "" || !Number.isFinite(Number(rawValue)) || Number(rawValue) < 0) {
    item.price = rawValue === "" ? null : rawValue;
    item.price_invalid = true;
    emitSelection();
    return;
  }

  item.price = Number(rawValue);
  item.is_custom_price = normalizeMoney(item.price) !== item.catalog_price;
  item.price_invalid = false;
  emitSelection();
};

const normalizePrice = (id) => {
  const item = selected.value.find((s) => s.custom_service_id === id);
  if (!item || item.price_invalid) return;
  item.price = normalizeMoney(item.price);
  item.is_custom_price = item.price !== item.catalog_price;
  emitSelection();
};

const formatCurrency = (value) =>
  new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(Number(value || 0));
</script>

<style scoped>
.cs-panel {
  max-width: 980px;
  margin: 24px auto 0;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #fff;
}

.cs-panel__header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid #e5e7eb;
  border-radius: 10px 10px 0 0;
  background: #f8f9fa;
}

.cs-panel__icon {
  display: grid;
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  place-items: center;
  border-radius: 8px;
  background: #e7f1ff;
  color: #0d6efd;
}

.cs-panel__title {
  flex: 1;
  min-width: 0;
}

.cs-panel__title strong,
.cs-panel__title small {
  display: block;
}

.cs-panel__title small {
  margin-top: 2px;
  color: #6b7280;
  font-size: 0.75rem;
}

.cs-panel__body {
  padding: 14px 16px 16px;
}

.cs-panel__notice {
  display: flex;
  gap: 8px;
  margin-top: 10px;
  padding: 10px 12px;
  border: 1px solid #fde68a;
  border-radius: 8px;
  background: #fffbeb;
  color: #92400e;
  font-size: 0.8rem;
}

.cs-panel__empty {
  margin: 10px 0 0;
  color: #6b7280;
  font-size: 0.8rem;
}

.cs-table tfoot td {
  border-bottom: 0;
  padding-top: 10px;
}

@media (max-width: 768px) {
  .cs-panel__header {
    flex-wrap: wrap;
  }
}
</style>
