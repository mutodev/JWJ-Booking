<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create Reservation</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <ReservationClient :customers="customers" />
          <hr />
          <ReservationAreas :areas="areas" />
          <hr />
          <ReservationServices :services="services" />
          <hr />
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            <i class="bi bi-arrow-90deg-down"></i> Back
          </button>
        </div>
      </div>
    </div>

    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import ReservationClient from "./create/ReservationClient.vue";
import ReservationAreas from "./create/ReservationAreas.vue";
import ReservationServices from "./create/ReservationServices.vue";

const emit = defineEmits(["close", "saved"]);

const props = defineProps({
  show: Boolean,
  customers: { type: Array, default: () => [] },
  areas: { type: Array, default: () => [] },
  services: { type: Array, default: () => [] },
});

const customers = ref([]);
watch(
  () => props.customers,
  (val) => {
    customers.value = [...val];
  },
  { immediate: true }
);

const areas = ref([]);
watch(
  () => props.areas,
  (val) => {
    areas.value = [...val];
  },
  { immediate: true }
);

const services = ref([]);
watch(
  () => props.services,
  (val) => {
    services.value = [...val];
  },
  { immediate: true }
);

const closeModal = () => {
  emit("close");
};
</script>

<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
}
.modal-backdrop {
  z-index: 1040;
}
.modal-dialog {
  z-index: 1050;
}
</style>
