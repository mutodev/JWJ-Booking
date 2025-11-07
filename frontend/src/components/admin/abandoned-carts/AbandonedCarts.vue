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
          placeholder="Search by email, phone..."
        />
      </div>
    </div>

    <!-- Botón de refresh y analytics -->
    <div class="col-md-2 pt-1 d-flex gap-2">
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
        </template>
      </EasyDataTable>
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
import { inject, ref, onMounted, computed } from "vue";
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

const viewDetails = (item) => {
  selectedData.value = { ...item };
  modalDetailsVisible.value = true;
};

const showAnalytics = () => {
  modalAnalyticsVisible.value = true;
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

onMounted(() => {
  getData();
});
</script>

<style scoped>
</style>
