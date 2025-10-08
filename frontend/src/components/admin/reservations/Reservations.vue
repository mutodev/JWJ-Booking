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
            <button
              class="btn btn-sm btn-success"
              @click="paymentUrlModal(item)"
              :disabled="item.is_paid"
            >
              <i class="bi bi-credit-card"></i> Payment URL
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

    <!-- Payment URL Modal -->
    <div v-if="modalPaymentUrlVisible" class="modal fade show d-block" tabindex="-1" role="dialog" style="z-index: 1055;">
      <div class="modal-dialog modal-md" role="document" style="z-index: 1056;">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Send Payment URL</h5>
            <button type="button" class="btn-close" @click="modalPaymentUrlVisible = false"></button>
          </div>
          <div class="modal-body">
            <p><strong>Customer:</strong> {{ selectedData?.customer_name || 'N/A' }}</p>
            <p><strong>Total Amount:</strong> {{ formatCurrency(selectedData?.total_amount) }}</p>

            <div class="mb-3">
              <label for="paymentUrl" class="form-label">Payment URL *</label>
              <input
                v-model="paymentUrl"
                type="url"
                class="form-control"
                id="paymentUrl"
                placeholder="https://..."
                required
              >
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="modalPaymentUrlVisible = false">Cancel</button>
            <button
              type="button"
              class="btn btn-primary"
              @click="sendPaymentEmail"
              :disabled="!paymentUrl || sendingEmail"
            >
              <span v-if="sendingEmail" class="spinner-border spinner-border-sm me-2" role="status"></span>
              {{ sendingEmail ? 'Sending...' : 'Send Email' }}
            </button>
          </div>
        </div>
      </div>
      <div class="modal-backdrop fade show" style="z-index: 1054;" @click="modalPaymentUrlVisible = false"></div>
    </div>
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
const modalPaymentUrlVisible = ref(false);
const selectedData = ref(null);
const paymentUrl = ref('');
const sendingEmail = ref(false);

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

const paymentUrlModal = (item) => {
  selectedData.value = { ...item };
  paymentUrl.value = '';
  modalPaymentUrlVisible.value = true;
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

const formatCurrency = (amount) => {
  if (amount == null || amount === 0) return "$0.00";
  return amount.toLocaleString("en-US", { style: "currency", currency: "USD" });
};

const sendPaymentEmail = async () => {
  try {
    sendingEmail.value = true;

    const requestData = {
      reservationId: selectedData.value.id,
      paymentUrl: paymentUrl.value
    };

    await api.post('/reservations/send-payment-email', requestData);

    modalPaymentUrlVisible.value = false;
    paymentUrl.value = '';
  } catch (error) {
    console.error('Error sending payment email:', error);
  } finally {
    sendingEmail.value = false;
  }
};

onMounted(() => {
  getData();
  getSelectData();
});
</script>
