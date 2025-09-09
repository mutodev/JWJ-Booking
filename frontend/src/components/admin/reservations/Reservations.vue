<template>
  <div class="row justify-content-end">
    <!-- Barra de búsqueda -->
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

    <!-- Botón de creación -->
    <div class="col-md-2 pt-1">
      <button class="btn btn-sm btn-primary" @click="createModal()">
        <i class="bi bi-plus-lg"></i>
        New Reservation
      </button>
    </div>
  </div>

  <!-- Tabla -->
  <div class="row mt-3">
    <div class="col-md-12">
      <EasyDataTable
        :headers="headers"
        :items="data"
        :search-field="searchField"
        :search-value="searchValue"
        table-class-name="table table-hover"
        header-text-direction="center"
        body-text-direction="center"
        :rows-per-page="10"
        :rows-per-page-options="[5, 10, 25, 50]"
        show-index
        index-column-text="#"
      >
        <!-- Estado de la reserva -->
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

        <!-- Estado de pago -->
        <template #item-is_paid="{ is_paid }">
          <span v-if="is_paid" class="badge bg-success">Paid</span>
          <span v-else class="badge bg-danger">Unpaid</span>
        </template>

        <!-- Acciones -->
        <template #item-actions="item">
          <button class="btn btn-sm btn-warning me-2" @click="editModal(item)">
            <i class="bi bi-pencil-square"></i> Edit
          </button>
          <button class="btn btn-sm btn-danger me-2" @click="deleteModal(item)">
            <i class="bi bi-trash"></i> Delete
          </button>
        </template>
      </EasyDataTable>
    </div>
  </div>

  <!-- Modales -->
  <ReservationCreate
    :show="modalCreateVisible"
    :customers="customers"
    :areas="areas"
    @close="modalCreateVisible = false"
    @saved="handle"
  />

</template>

<script setup>
import { inject, ref, onMounted, computed } from "vue";
import api from "@/services/axios";
import ReservationCreate from "./ReservationCreate.vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Reservations", icon: "bi-calendar-event" });

const tableHelpers = inject("tableHelpers");

// Data principal
const data = ref([]);
const searchValue = ref("");

// Listas de selects
const customers = ref([]);
const areas = ref([]);

// Modales
const modalEditVisible = ref(false);
const modalCreateVisible = ref(false);
const modalDeleteVisible = ref(false);
const selectedData = ref(null);

const editModal = (item) => {
  selectedData.value = { ...item };
  modalEditVisible.value = true;
};

const createModal = () => {
  modalCreateVisible.value = true;
};

const deleteModal = (item) => {
  selectedData.value = { ...item };
  modalDeleteVisible.value = true;
};

// Encabezados de la tabla
const headers = computed(() => {
  return tableHelpers.generateTableHeaders(data.value, {
    customLabels: {
      customer_id: "Customer",
      service_id: "Service",
      county_id: "County",
      event_date: "Date",
      event_time: "Time",
      total_amount: "Total",
      status: "Status",
      is_paid: "Paid",
    },
  });
});

const searchField = computed(() => {
  return tableHelpers.generateSearchFields(headers.value);
});

// Cargar reservas
const getData = async () => {
  try {
    const response = await api.get("/reservations");
    data.value = response.data;
  } catch (error) {
    console.error(error);
  }
};

// Cargar customers, services, counties y zipcodes activos
const getSelectData = async () => {
  try {
    const [resCustomers, resAreas] =
      await Promise.all([
        api.get("/customers"),
        api.get("/metropolitan-areas/list-active"),
      ]);

    customers.value = resCustomers.data;
    areas.value = resAreas.data;
  } catch (error) {
    console.error(error);
  }
};

// Refrescar datos
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
