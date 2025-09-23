<template>
  <div>
    <div class="row justify-content-end mb-3">
      <div class="col-10">
        <div class="input-group">
          <span class="input-group-text">
            <i class="bi bi-search"></i>
          </span>
          <input
            v-model="searchValue"
            type="text"
            class="form-control"
            placeholder="Search reservations..."
          />
        </div>
      </div>
      <div class="col-md-2 pt-1">
        <button class="btn btn-sm btn-primary" @click="createModal()">
          <i class="bi bi-plus-lg"></i> New Reservation
        </button>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <EasyDataTable
          :headers="headers"
          :items="dataProcessed"
          :search-field="searchField"
          :search-value="searchValue"
          table-class-name="table table-hover"
          header-text-direction="center"
          body-text-direction="center"
          :rows-per-page="10"
          :rows-per-page-options="[5, 10, 25, 50]"
          show-index
        >
          <template #item-status="{ status }">
            <span v-if="status === 'pending'" class="badge bg-warning text-dark"
              >Pending</span
            >
            <span v-else-if="status === 'confirmed'" class="badge bg-success"
              >Confirmed</span
            >
            <span v-else-if="status === 'cancelled'" class="badge bg-danger"
              >Cancelled</span
            >
            <span v-else class="badge bg-secondary">{{ status }}</span>
          </template>

          <template #item-is_paid="{ is_paid }">
            <span v-if="is_paid" class="badge bg-success">Paid</span>
            <span v-else class="badge bg-danger">Unpaid</span>
          </template>

          <template #item-total_amount="{ total_amount }">
            {{
              total_amount.toLocaleString("en-US", {
                style: "currency",
                currency: "USD",
              })
            }}
          </template>

          <template #item-event_date="{ event_date }">
            {{ new Date(event_date).toLocaleDateString("en-GB") }}
          </template>

          <template #item-actions="item">
            <button
              class="btn btn-sm btn-warning me-2"
              @click="editModal(item)"
            >
              <i class="bi bi-pencil-square"></i> Edit
            </button>
            <button
              class="btn btn-sm btn-secondary me-2"
              @click="viewModal(item)"
            >
              <i class="bi bi-eye"></i> View
            </button>
          </template>
        </EasyDataTable>
      </div>
    </div>

    <ReservationCreate
      :show="modalCreateVisible"
      :customers="customers"
      :areas="areas"
      :services="services"
      :addons="addons"
      @close="modalCreateVisible = false"
      @saved="handle"
    />

    <ReservationEdit
      :show="modalEditVisible"
      :data="selectedData"
      @close="modalEditVisible = false"
      @saved="handle"
    />

    <ReservationView
      :show="modalViewVisible"
      :data="selectedData"
      @close="modalViewVisible = false"
    />
  </div>
</template>

<script setup>
import { inject, ref, onMounted, computed } from "vue";
import api from "@/services/axios";
import ReservationCreate from "./ReservationCreate.vue";
import ReservationEdit from "./ReservationEdit.vue";
import ReservationView from "./ReservationView.vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Reservations", icon: "bi-calendar-event" });

const data = ref([]);
const searchValue = ref("");

const customers = ref([]);
const areas = ref([]);
const services = ref([]);
const addons = ref([]);

const modalEditVisible = ref(false);
const modalCreateVisible = ref(false);
const modalViewVisible = ref(false);
const selectedData = ref(null);

const editModal = (item) => {
  selectedData.value = { ...item };
  modalEditVisible.value = true;
};
const createModal = () => {
  modalCreateVisible.value = true;
};
const viewModal = (item) => {
  selectedData.value = { ...item };
  modalViewVisible.value = true;
};

// Encabezados principales
const headers = computed(() => [
  { text: "Customer", value: "customer_name" },
  { text: "Service", value: "service_name" },
  { text: "Location", value: "location" },
  { text: "Date", value: "event_date" },
  { text: "Time", value: "event_time" },
  { text: "Total", value: "total_amount" },
  { text: "Status", value: "status" },
  { text: "Paid", value: "is_paid" },
  { text: "Actions", value: "actions" },
]);

const searchField = computed(() => [
  "customer_name",
  "service_name",
  "location",
  "event_address",
]);

// Procesar datos: event_date como Date, total_amount como número
const dataProcessed = computed(() =>
  data.value.map((item) => ({
    ...item,
    customer_name: item.full_name || 'N/A',
    service_name: item.service_name || 'N/A',
    location: `${item.city_name || ''}, ${item.county_name || ''}`.replace(', ', '') ? `${item.city_name || ''}, ${item.county_name || ''}` : item.zipcode || 'N/A',
    event_address: item.event_address ?? "",
    event_date: item.event_date?.date ? new Date(item.event_date.date) : (item.event_date ? new Date(item.event_date) : null),
    event_time: item.event_time ?? "",
    total_amount: parseFloat(item.total_amount) || 0,
    status: item.status ?? "",
    is_paid: Boolean(item.is_paid),
  }))
);

const getData = async () => {
  try {
    const response = await api.get("/reservations");
    data.value = response.data;
  } catch (error) {
    console.error(error);
  }
};

const getSelectData = async () => {
  try {
    const [resCustomers, resAreas, resServices, resAddons] = await Promise.all([
      api.get("/customers"),
      api.get("/metropolitan-areas/list-active"),
      api.get("/services/get-all-active"),
      api.get("/addons/active"),
    ]);
    customers.value = resCustomers.data;
    areas.value = resAreas.data;
    services.value = resServices.data;
    addons.value = resAddons.data;
  } catch (error) {
    console.error(error);
  }
};

const handle = () => {
  modalCreateVisible.value = false;
  modalEditVisible.value = false;
  getData();
};

onMounted(() => {
  getData();
  getSelectData();
});
</script>
