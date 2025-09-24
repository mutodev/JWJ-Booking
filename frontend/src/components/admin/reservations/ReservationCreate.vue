<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create Reservation</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <ReservationClient :customers="customers" @setData="setData" />
          <ReservationAreas
            v-if="dataForm?.customer"
            :areas="areas"
            @setData="setData"
          />
          <ReservationServices
            v-if="dataForm?.areas?.zipcode"
            :services="services"
            :county="dataForm?.areas?.county ?? {}"
            @setData="setData"
          />
          <ReservationAddons
            v-if="dataForm?.price"
            :addons="addons"
            @setData="setData"
          />
          <ReservationForm
            v-if="dataForm?.price"
            :addons="addons"
            @setData="setData"
          />

          <ReservationTotal v-if="dataForm?.form" :data="dataForm" />
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            <i class="bi bi-arrow-90deg-down"></i> Back
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="saveReservation"
            :disabled="!dataForm?.form"
          >
            <i class="bi bi-save"></i>
            Save
          </button>
        </div>
      </div>
    </div>

    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import api from "@/services/axios";
import ReservationClient from "./create/ReservationClient.vue";
import ReservationAreas from "./create/ReservationAreas.vue";
import ReservationServices from "./create/ReservationServices.vue";
import ReservationAddons from "./create/ReservationAddons.vue";
import ReservationForm from "./create/ReservationForm.vue";
import ReservationTotal from "./create/ReservationTotal.vue";

const emit = defineEmits(["close", "saved"]);
const dataForm = ref({});

const props = defineProps({
  show: Boolean,
  customers: { type: Array, default: () => [] },
  areas: { type: Array, default: () => [] },
  services: { type: Array, default: () => [] },
  addons: { type: Array, default: () => [] },
});

const customers = ref([]);
watch(
  () => props.customers,
  (val) => (customers.value = [...val]),
  { immediate: true }
);

const areas = ref([]);
watch(
  () => props.areas,
  (val) => (areas.value = [...val]),
  { immediate: true }
);

const services = ref([]);
watch(
  () => props.services,
  (val) => (services.value = [...val]),
  { immediate: true }
);

const addons = ref([]);
watch(
  () => props.addons,
  (val) => (addons.value = [...val]),
  { immediate: true }
);

/**
 * arma datos del formulario
 */
const setData = (data) => {
  for (const key in data) {
    if (data[key] === null) {
      delete dataForm.value[key];
    } else {
      dataForm.value[key] = data[key];
    }
  }
};

const saveReservation = async() => {
  try {
      await api.post('/reservations', dataForm.value)
      emit("saved");
  } catch (error) {
    console.log(error);
  }
}

const closeModal = () => {
  emit("close");
};
</script>

