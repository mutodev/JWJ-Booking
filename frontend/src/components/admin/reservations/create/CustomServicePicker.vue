<template>
  <Multiselect
    class="custom-service-picker"
    :model-value="null"
    :options="available"
    :custom-label="searchLabel"
    track-by="id"
    :searchable="true"
    :reset-after="true"
    :close-on-select="true"
    :show-labels="false"
    :disabled="disabled"
    :placeholder="placeholder"
    open-direction="bottom"
    :max-height="280"
    @select="(option) => emit('select', option)"
  >
    <template #option="{ option }">
      <div class="cs-option">
        <div class="cs-option__text">
          <span class="cs-option__name">{{ option.name }}</span>
          <small v-if="option.detail" class="cs-option__detail">{{ option.detail }}</small>
        </div>
        <span class="cs-option__price">{{ formatCurrency(option.price) }}</span>
        <i class="bi bi-plus-circle cs-option__add" aria-hidden="true"></i>
      </div>
    </template>

    <template #noResult>
      <span class="cs-empty">No matches for your search.</span>
    </template>

    <template #noOptions>
      <span class="cs-empty">
        {{ catalog.length ? "All items are already added." : emptyText }}
      </span>
    </template>
  </Multiselect>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  catalog: { type: Array, default: () => [] },
  excludeIds: { type: Array, default: () => [] },
  disabled: { type: Boolean, default: false },
  placeholder: { type: String, default: "Search by name or detail…" },
  emptyText: { type: String, default: "No active custom services available." },
});

const emit = defineEmits(["select"]);

const available = computed(() =>
  props.catalog.filter((item) => !props.excludeIds.includes(item.id))
);

// Multiselect filtra por este texto: permite buscar por nombre y por detalle.
const searchLabel = (option) => [option.name, option.detail].filter(Boolean).join(" — ");

const formatCurrency = (value) =>
  new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(Number(value || 0));
</script>

<style scoped>
.cs-option {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.cs-option__text {
  display: flex;
  flex: 1;
  min-width: 0;
  flex-direction: column;
}

.cs-option__name {
  font-weight: 700;
  font-size: 0.85rem;
  white-space: normal;
}

.cs-option__detail {
  overflow: hidden;
  opacity: 0.75;
  font-size: 0.75rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.cs-option__price {
  font-weight: 700;
  font-size: 0.85rem;
  white-space: nowrap;
}

.cs-option__add {
  font-size: 1rem;
  opacity: 0.7;
}

.cs-empty {
  font-size: 0.82rem;
  color: #6b7280;
}
</style>
