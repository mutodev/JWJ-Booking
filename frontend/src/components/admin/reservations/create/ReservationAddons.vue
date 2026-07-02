<template>
  <div class="addon-section">
    <div class="addon-heading">
      <i class="bi bi-plus-circle"></i>
      <span>Add-ons</span>
    </div>
  </div>

  <div class="row justify-content-center g-3 addon-grid">
    <div v-for="addon in listAddons" :key="addon.id" class="col-12 col-sm-6 col-md-4 col-lg-2">
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
      selectedAddons.value.push(addon);
    }
  } else {
    selectedAddons.value = selectedAddons.value.some((a) => a.id === addon.id)
      ? []
      : [addon];
  }

  emit("setData", { addons: [...selectedAddons.value] });
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
