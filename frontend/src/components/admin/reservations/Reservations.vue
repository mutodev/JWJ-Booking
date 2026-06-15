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
      <div class="col-md-2 pt-1 d-flex gap-2">
        <button class="btn btn-sm btn-success" @click="exportModalVisible = true" title="Export to CSV">
          <i class="bi bi-download"></i> Export
        </button>
        <button class="btn btn-sm btn-primary" @click="createModal()">
          <i class="bi bi-plus-lg"></i> New
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
              class="btn btn-sm btn-success me-2"
              @click="paymentUrlModal(item)"
              :disabled="item.is_paid"
            >
              <i class="bi bi-credit-card"></i> Send Payment
            </button>
            <button
              class="btn btn-sm btn-primary"
              @click="openComposeModal(item)"
              title="Send email to this customer"
            >
              <i class="bi bi-envelope"></i>
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

    <ComposeEmailModal
      :show="composeEmailVisible"
      :locked-recipient="composeLockedRecipient"
      @close="composeEmailVisible = false"
    />

    <!-- Send Payment Modal -->
    <div v-if="modalPaymentUrlVisible" class="modal fade show d-block" tabindex="-1" role="dialog" style="z-index: 1055;">
      <div class="modal-dialog modal-md" role="document" style="z-index: 1056;">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Send Payment</h5>
            <button type="button" class="btn-close" @click="modalPaymentUrlVisible = false"></button>
          </div>
          <div class="modal-body">
            <p><strong>Customer:</strong> {{ selectedData?.customer_name || 'N/A' }}</p>
            <p><strong>Email:</strong> {{ selectedData?.email || 'N/A' }}</p>
            <p><strong>Total Amount:</strong> {{ formatCurrency(selectedData?.total_amount) }}</p>
            <div class="mb-3">
              <label class="form-label fw-bold">Description</label>
              <textarea
                v-model="paymentDescription"
                class="form-control"
                rows="3"
                placeholder="Add a description for this payment (optional)"
              ></textarea>
            </div>
            <p class="text-muted small">A Stripe Checkout link will be generated automatically and sent to the customer's email.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="modalPaymentUrlVisible = false">Cancel</button>
            <button
              type="button"
              class="btn btn-primary"
              @click="sendPaymentEmail"
              :disabled="sendingEmail"
            >
              <span v-if="sendingEmail" class="spinner-border spinner-border-sm me-2" role="status"></span>
              {{ sendingEmail ? 'Sending...' : 'Send Payment' }}
            </button>
          </div>
        </div>
      </div>
      <div class="modal-backdrop fade show" style="z-index: 1054;" @click="modalPaymentUrlVisible = false"></div>
    </div>

    <!-- Export CSV Modal -->
    <div v-if="exportModalVisible" class="modal fade show d-block" tabindex="-1" role="dialog" style="z-index: 1055;">
      <div class="modal-dialog modal-sm" role="document" style="z-index: 1056;">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi bi-download me-2 text-success"></i>Export Reservations
            </h5>
            <button type="button" class="btn-close" @click="closeExportModal"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted small mb-3">Select a date range to export. Both fields are required.</p>
            <div class="mb-3">
              <label class="form-label fw-semibold">From <span class="text-danger">*</span></label>
              <input v-model="exportDateFrom" type="date" class="form-control" />
            </div>
            <div class="mb-2">
              <label class="form-label fw-semibold">To <span class="text-danger">*</span></label>
              <input v-model="exportDateTo" type="date" class="form-control" />
            </div>
            <div v-if="exportError" class="mt-2 text-danger small">
              <i class="bi bi-exclamation-circle me-1"></i>{{ exportError }}
            </div>
            <div v-if="exportPreviewCount !== null" class="mt-2 text-success small">
              <i class="bi bi-check-circle me-1"></i>{{ exportPreviewCount }} reservation(s) found in range.
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" @click="closeExportModal">Cancel</button>
            <button type="button" class="btn btn-success btn-sm" @click="exportCSV">
              <i class="bi bi-file-earmark-spreadsheet me-1"></i>Download CSV
            </button>
          </div>
        </div>
      </div>
      <div class="modal-backdrop fade show" style="z-index: 1054;" @click="closeExportModal"></div>
    </div>
  </div>
</template>

<script setup>
import { inject, ref, watch, onMounted, computed } from "vue";
import api from "@/services/axios";
import ReservationCreate from "./ReservationCreate.vue";
import ReservationEdit from "./ReservationEdit.vue";
import ReservationView from "./ReservationView.vue";
import ComposeEmailModal from "@/components/admin/email-templates/ComposeEmailModal.vue";

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
const composeEmailVisible = ref(false);
const composeLockedRecipient = ref(null);
const exportModalVisible = ref(false);
const exportDateFrom = ref('');
const exportDateTo = ref('');
const exportError = ref('');
const exportPreviewCount = ref(null);
const selectedData = ref(null);
const sendingEmail = ref(false);
const paymentDescription = ref("");

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
  paymentDescription.value = item.description || "";
  modalPaymentUrlVisible.value = true;
};

const openComposeModal = (item) => {
  composeLockedRecipient.value = {
    id: item.customer_id,
    full_name: item.full_name || item.customer_name || 'Customer',
    email: item.email,
  };
  composeEmailVisible.value = true;
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
    addons.value = (resAddons.data || []).flatMap(group => group.addons || []);
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
      description: paymentDescription.value,
    };

    await api.post('/reservations/send-payment-email', requestData);

    modalPaymentUrlVisible.value = false;
    getData();
  } catch (error) {
    console.error('Error sending payment email:', error);
  } finally {
    sendingEmail.value = false;
  }
};

// PHP devuelve fechas como objeto { date: "2026-06-15 12:00:00.000000", ... } o como string
const parseDate = (val) => {
  if (!val) return null;
  const str = val?.date ?? val;
  const d = new Date(str);
  return isNaN(d.getTime()) ? null : d;
};
const fmtDate = (val) => parseDate(val)?.toLocaleDateString('en-US') ?? '';

const CSV_COLUMNS = [
  { label: 'Customer',           key: (r) => r.full_name || '' },
  { label: 'Email',              key: (r) => r.email || '' },
  { label: 'Phone',              key: (r) => r.phone || '' },
  { label: 'Service',            key: (r) => r.service_name || '' },
  { label: 'Event Date',         key: (r) => fmtDate(r.event_date) },
  { label: 'Event Time',         key: (r) => r.event_time || '' },
  { label: 'Address',            key: (r) => r.event_address || '' },
  { label: 'City',               key: (r) => r.city_name || '' },
  { label: 'County',             key: (r) => r.county_name || '' },
  { label: 'Zipcode',            key: (r) => r.zipcode || '' },
  { label: 'Children Count',     key: (r) => r.children_count ?? '' },
  { label: 'Children Range',     key: (r) => r.children_age_range || '' },
  { label: 'Duration (hrs)',     key: (r) => r.duration_hours ?? '' },
  { label: 'Performers',         key: (r) => r.performers_count ?? '' },
  { label: 'Status',             key: (r) => r.status || '' },
  { label: 'Paid',               key: (r) => r.is_paid ? 'Yes' : 'No' },
  { label: 'Base Price',         key: (r) => parseFloat(r.base_price || 0).toFixed(2) },
  { label: 'Addons Total',       key: (r) => parseFloat(r.addons_total || 0).toFixed(2) },
  { label: 'Expedition Fee',     key: (r) => parseFloat(r.expedition_fee || 0).toFixed(2) },
  { label: 'Extra Children Fee', key: (r) => parseFloat(r.extra_children_fee || 0).toFixed(2) },
  { label: 'Promo Code',         key: (r) => r.promo_code || '' },
  { label: 'Discount',           key: (r) => parseFloat(r.discount_amount || 0).toFixed(2) },
  { label: 'Total Amount',       key: (r) => parseFloat(r.total_amount || 0).toFixed(2) },
  { label: 'Birthday Child',     key: (r) => r.birthday_child_name || '' },
  { label: 'Internal Notes',     key: (r) => r.internal_notes || '' },
  { label: 'Created At',         key: (r) => fmtDate(r.created_at) },
];

const getFilteredForExport = () => {
  const from = exportDateFrom.value ? new Date(exportDateFrom.value) : null;
  const to   = exportDateTo.value   ? new Date(exportDateTo.value)   : null;
  if (to) to.setHours(23, 59, 59, 999);
  return data.value.filter((r) => {
    const d = parseDate(r.event_date);
    if (!d) return false;
    if (from && d < from) return false;
    if (to   && d > to)   return false;
    return true;
  });
};

watch([exportDateFrom, exportDateTo], () => {
  exportError.value = '';
  if (exportDateFrom.value && exportDateTo.value) {
    exportPreviewCount.value = getFilteredForExport().length;
  } else {
    exportPreviewCount.value = null;
  }
});

const closeExportModal = () => {
  exportModalVisible.value = false;
  exportDateFrom.value = '';
  exportDateTo.value = '';
  exportError.value = '';
  exportPreviewCount.value = null;
};

const exportCSV = () => {
  exportError.value = '';
  if (!exportDateFrom.value || !exportDateTo.value) {
    exportError.value = 'Both dates are required.';
    return;
  }
  if (exportDateFrom.value > exportDateTo.value) {
    exportError.value = '"From" date must be before "To" date.';
    return;
  }

  const filtered = getFilteredForExport();
  if (filtered.length === 0) {
    exportError.value = 'No reservations found in this date range.';
    return;
  }

  const escape = (val) => `"${String(val).replace(/"/g, '""')}"`;
  const header = CSV_COLUMNS.map((c) => escape(c.label)).join(';');
  const rows   = filtered.map((r) => CSV_COLUMNS.map((c) => escape(c.key(r))).join(';'));

  const csv  = [header, ...rows].join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href     = url;
  link.download = `reservations_${exportDateFrom.value}_${exportDateTo.value}.csv`;
  link.click();
  URL.revokeObjectURL(url);
  closeExportModal();
};

onMounted(() => {
  getData();
  getSelectData();
});
</script>
