<template>
  <div class="row justify-content-end">
    <!-- Barra de búsqueda -->
    <div class="col">
      <div class="input-group">
        <span class="input-group-text">
          <i class="bi bi-search"></i>
        </span>
        <input
          v-model="searchValue"
          type="text"
          class="form-control"
          placeholder="Search by email, phone..."
        />
      </div>
    </div>

    <!-- Botón de refresh y analytics -->
    <div class="col-md-auto pt-1 d-flex gap-2 justify-content-end">
      <button class="btn btn-sm btn-success" @click="exportModalVisible = true" title="Export CSV">
        <i class="bi bi-download"></i> Export
      </button>
      <button class="btn btn-sm btn-info" @click="showAnalytics()">
        <i class="bi bi-graph-up"></i>
        Analytics
      </button>
      <button class="btn btn-sm btn-primary" @click="getData()">
        <i class="bi bi-arrow-clockwise"></i>
        Refresh
      </button>
    </div>
  </div>

  <!-- Filtros -->
  <div class="row mt-3">
    <div class="col-md-3">
      <label class="form-label small">Filter by Status</label>
      <select v-model="filterStatus" @change="applyFilters" class="form-select form-select-sm">
        <option value="">All</option>
        <option value="0">Abandoned</option>
        <option value="1">Completed</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small">Filter by Step</label>
      <select v-model="filterStep" @change="applyFilters" class="form-select form-select-sm">
        <option value="">All Steps</option>
        <option value="1">Step 1 - Contact Info</option>
        <option value="2">Step 2 - Choose Service</option>
        <option value="3">Step 3 - Select Add-ons</option>
        <option value="4">Step 4 - Review Subtotal</option>
        <option value="5">Step 5 - Event Details</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small">Date From</label>
      <input
        v-model="filterDateFrom"
        @change="applyFilters"
        type="date"
        class="form-control form-control-sm"
      />
    </div>
    <div class="col-md-3">
      <label class="form-label small">Date To</label>
      <input
        v-model="filterDateTo"
        @change="applyFilters"
        type="date"
        class="form-control form-control-sm"
      />
    </div>
  </div>

  <!-- Tabla -->
  <div class="row mt-3">
    <div class="col-md-12">
      <EasyDataTable
        :headers="headers"
        :items="filteredData"
        :search-field="searchField"
        :search-value="searchValue"
        table-class-name="table table-hover table-sm"
        header-text-direction="center"
        body-text-direction="center"
        :rows-per-page="25"
        :rows-per-page-options="[10, 25, 50, 100]"
        show-index
        index-column-text="#"
      >
        <!-- Email -->
        <template #item-email="{ email }">
          <span v-if="email">{{ email }}</span>
          <span v-else class="text-muted">N/A</span>
        </template>

        <!-- Phone -->
        <template #item-phone="{ phone }">
          <span v-if="phone">{{ phone }}</span>
          <span v-else class="text-muted">N/A</span>
        </template>

        <!-- Current Step -->
        <template #item-current_step="{ current_step }">
          <span class="badge bg-primary">Step {{ current_step }}</span>
        </template>

        <!-- Completed Status -->
        <template #item-completed="{ completed }">
          <span v-if="completed" class="badge bg-success">
            <i class="bi bi-check-circle"></i> Completed
          </span>
          <span v-else class="badge bg-danger">
            <i class="bi bi-cart-x"></i> Abandoned
          </span>
        </template>

        <!-- Last Activity -->
        <template #item-last_activity_at="{ last_activity_at }">
          {{ formatDateTime(last_activity_at) }}
          <br>
          <small class="text-muted">{{ getTimeAgo(last_activity_at) }}</small>
        </template>

        <!-- Actions -->
        <template #item-actions="item">
          <button
            class="btn btn-sm btn-secondary"
            @click="viewDetails(item)"
          >
            <i class="bi bi-eye"></i> View
          </button>
          <button
            v-if="!item.completed && item.email"
            class="btn btn-sm btn-action-icon btn-warning ms-1"
            :disabled="sendingId === item.id"
            :title="'Send follow-up to ' + item.email"
            @click="sendFollowUp(item)"
          >
            <i v-if="sendingId === item.id" class="bi bi-hourglass-split"></i>
            <i v-else class="bi bi-envelope-arrow-up"></i>
          </button>
        </template>
      </EasyDataTable>
    </div>
  </div>

  <!-- Export CSV Modal -->
  <div v-if="exportModalVisible" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-backdrop fade show" @click="closeExportModal"></div>
    <div class="modal-dialog" role="document" style="position: relative; z-index: 1;">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-download me-2 text-success"></i>Export Abandoned Carts
          </h5>
          <button type="button" class="btn-close" @click="closeExportModal"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small mb-3">Filter by last activity date range. Both fields are required.</p>
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label small">From</label>
              <input v-model="exportDateFrom" type="date" class="form-control form-control-sm" />
            </div>
            <div class="col-6">
              <label class="form-label small">To</label>
              <input v-model="exportDateTo" type="date" class="form-control form-control-sm" />
            </div>
          </div>
          <div v-if="exportError" class="mt-2 text-danger small">
            <i class="bi bi-exclamation-circle me-1"></i>{{ exportError }}
          </div>
          <div v-if="exportPreviewCount !== null" class="mt-2 text-success small">
            <i class="bi bi-check-circle me-1"></i>{{ exportPreviewCount }} cart(s) found in range.
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
  </div>

  <!-- Modales -->
  <DraftDetails
    :show="modalDetailsVisible"
    :data="selectedData"
    @close="modalDetailsVisible = false"
  />

  <AnalyticsModal
    :show="modalAnalyticsVisible"
    :stats="stats"
    @close="modalAnalyticsVisible = false"
  />
</template>

<script setup>
import { inject, ref, onMounted, computed, watch } from "vue";
import api from "@/services/axios";
import DraftDetails from "./DraftDetails.vue";
import AnalyticsModal from "./AnalyticsModal.vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Abandoned Carts", icon: "bi-cart-x" });

const tableHelpers = inject("tableHelpers");
const data = ref([]);
const stats = ref({});
const searchValue = ref("");

// Filtros
const filterStatus = ref("");
const filterStep = ref("");
const filterDateFrom = ref("");
const filterDateTo = ref("");

// Modales
const modalDetailsVisible = ref(false);
const modalAnalyticsVisible = ref(false);
const selectedData = ref(null);
const sendingId = ref(null);

const viewDetails = (item) => {
  selectedData.value = { ...item };
  modalDetailsVisible.value = true;
};

const showAnalytics = () => {
  modalAnalyticsVisible.value = true;
};

const sendFollowUp = async (item) => {
  sendingId.value = item.id;
  try {
    await api.post(`/reservation-drafts/${item.id}/follow-up`);
    alert(`Follow-up email sent to ${item.email}`);
  } catch (error) {
    alert('Failed to send follow-up email. Please try again.');
    console.error(error);
  } finally {
    sendingId.value = null;
  }
};

// Headers manuales
const headers = [
  { text: "Email", value: "email", sortable: true },
  { text: "Phone", value: "phone", sortable: true },
  { text: "Last Step", value: "current_step", sortable: true },
  { text: "Status", value: "completed", sortable: true },
  { text: "Last Activity", value: "last_activity_at", sortable: true },
  { text: "Actions", value: "actions", sortable: false },
];

// Campos de búsqueda
const searchField = ["email", "phone"];

// Datos filtrados
const filteredData = computed(() => {
  let filtered = [...data.value];

  if (filterStatus.value !== "") {
    filtered = filtered.filter(draft => draft.completed == filterStatus.value);
  }

  if (filterStep.value) {
    filtered = filtered.filter(draft => draft.current_step == filterStep.value);
  }

  if (filterDateFrom.value) {
    const fromDate = new Date(filterDateFrom.value);
    filtered = filtered.filter(draft => {
      const draftDate = new Date(draft.last_activity_at);
      return draftDate >= fromDate;
    });
  }

  if (filterDateTo.value) {
    const toDate = new Date(filterDateTo.value);
    toDate.setHours(23, 59, 59, 999);
    filtered = filtered.filter(draft => {
      const draftDate = new Date(draft.last_activity_at);
      return draftDate <= toDate;
    });
  }

  return filtered;
});

// Cargar data
const getData = async () => {
  try {
    const response = await api.get("/reservation-drafts");
    data.value = response.data || [];
    calculateStats();
  } catch (error) {
    console.error(error);
  }
};

const calculateStats = () => {
  const total = data.value.length;
  const completed = data.value.filter(d => d.completed).length;
  const abandoned = total - completed;

  const now = new Date();
  const yesterday = new Date(now.getTime() - 24 * 60 * 60 * 1000);
  const last24h = data.value.filter(d => {
    const draftDate = new Date(d.last_activity_at);
    return !d.completed && draftDate >= yesterday;
  }).length;

  stats.value = {
    total_drafts: total,
    completed: completed,
    abandoned: abandoned,
    completion_rate: total > 0 ? ((completed / total) * 100).toFixed(1) : 0,
    abandon_rate: total > 0 ? ((abandoned / total) * 100).toFixed(1) : 0,
    last_24h: last24h
  };
};

const applyFilters = () => {
  // Filters are applied reactively via computed property
};

// Format datetime helper
const formatDateTime = (datetime) => {
  if (!datetime) return "N/A";
  const d = new Date(datetime);
  return d.toLocaleString("en-US", {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Time ago helper
const getTimeAgo = (datetime) => {
  if (!datetime) return "";
  const now = new Date();
  const past = new Date(datetime);
  const diffMs = now - past;
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
  const diffDays = Math.floor(diffHours / 24);

  if (diffDays > 0) {
    return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
  } else if (diffHours > 0) {
    return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
  } else {
    const diffMins = Math.floor(diffMs / (1000 * 60));
    return `${diffMins} min${diffMins > 1 ? 's' : ''} ago`;
  }
};

// ─── Export CSV ────────────────────────────────────────────────────────────
const exportModalVisible  = ref(false);
const exportDateFrom      = ref('');
const exportDateTo        = ref('');
const exportError         = ref('');
const exportPreviewCount  = ref(null);

const parseFormData = (raw) => {
  if (!raw) return {};
  try {
    return typeof raw === 'string' ? JSON.parse(raw) : raw;
  } catch {
    return {};
  }
};

const fmtDateTime = (val) => {
  if (!val) return '';
  const d = new Date(val);
  return isNaN(d.getTime()) ? String(val) : d.toLocaleString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
};

const STEP_NAMES = {
  1: 'Contact Info',
  2: 'Choose Service',
  3: 'Select Add-ons',
  4: 'Review Subtotal',
  5: 'Event Details',
};

// r._fd = form_data pre-parsed once per row in exportCSV
const CSV_COLUMNS = [
  { label: 'Email',               key: (r) => r.email || '' },
  { label: 'Phone',               key: (r) => r.phone || '' },
  { label: 'Status',              key: (r) => r.completed ? 'Completed' : 'Abandoned' },
  { label: 'Last Step',           key: (r) => r.current_step ?? '' },
  { label: 'Step Name',           key: (r) => STEP_NAMES[r.current_step] || '' },
  { label: 'Reservation ID',      key: (r) => r.reservation_id || '' },
  { label: 'Zip Code',            key: (r) => r._fd.zipcode || '' },
  { label: 'Children Count',      key: (r) => r._fd.children_count ?? '' },
  { label: 'Children Age Range',  key: (r) => r._fd.children_age_range || '' },
  { label: 'Performers',          key: (r) => r._fd.performers_count ?? '' },
  { label: 'Duration (hrs)',      key: (r) => r._fd.duration_hours ?? '' },
  { label: 'Event Date',          key: (r) => r._fd.event_date || '' },
  { label: 'Event Time',          key: (r) => r._fd.event_time || '' },
  { label: 'Event Address',       key: (r) => r._fd.event_address || '' },
  { label: 'Birthday Child',      key: (r) => r._fd.birthday_child_name || '' },
  { label: 'Birthday Age',        key: (r) => r._fd.birthday_child_age || '' },
  { label: 'Promo Code',          key: (r) => r._fd.promo_code || '' },
  { label: 'Subtotal',            key: (r) => r._fd.subtotal ?? '' },
  { label: 'Last Activity',       key: (r) => fmtDateTime(r.last_activity_at) },
  { label: 'Created At',          key: (r) => fmtDateTime(r.created_at) },
  { label: 'IP Address',          key: (r) => r.ip_address || '' },
];

const getFilteredForExport = () => {
  const from = exportDateFrom.value ? new Date(exportDateFrom.value) : null;
  const to   = exportDateTo.value   ? new Date(exportDateTo.value)   : null;
  if (to) to.setHours(23, 59, 59, 999);
  return data.value.filter((r) => {
    const d = new Date(r.last_activity_at);
    if (isNaN(d.getTime())) return false;
    if (from && d < from) return false;
    if (to   && d > to)   return false;
    return true;
  });
};

watch([exportDateFrom, exportDateTo], () => {
  exportError.value = '';
  exportPreviewCount.value = (exportDateFrom.value && exportDateTo.value)
    ? getFilteredForExport().length
    : null;
});

const closeExportModal = () => {
  exportModalVisible.value = false;
  exportDateFrom.value     = '';
  exportDateTo.value       = '';
  exportError.value        = '';
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
    exportError.value = 'No carts found in this date range.';
    return;
  }

  const escape = (val) => `"${String(val ?? '').replace(/"/g, '""')}"`;
  const header = CSV_COLUMNS.map((c) => escape(c.label)).join(';');
  const rows   = filtered.map((r) => {
    const enriched = { ...r, _fd: parseFormData(r.form_data) };
    return CSV_COLUMNS.map((c) => {
      try { return escape(c.key(enriched)); } catch { return '""'; }
    }).join(';');
  });

  const csv  = [header, ...rows].join('\n');
  const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href     = url;
  link.download = `abandoned_carts_${exportDateFrom.value}_${exportDateTo.value}.csv`;
  link.click();
  URL.revokeObjectURL(url);
  closeExportModal();
};
// ───────────────────────────────────────────────────────────────────────────

onMounted(() => {
  getData();
});
</script>

<style scoped>
</style>
