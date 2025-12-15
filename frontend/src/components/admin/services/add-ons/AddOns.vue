<template>
  <!-- Search bar and create button -->
  <div class="row justify-content-end">
    <div class="col-10">
      <div class="input-group">
        <span class="input-group-text">
          <i class="bi bi-search"></i>
        </span>
        <input
          v-model="searchValue"
          type="text"
          class="form-control"
          placeholder="Search addons..."
        />
      </div>
    </div>
    <div class="col-md-2 pt-1">
      <button class="btn btn-sm btn-primary" @click="openCreateModal">
        <i class="bi bi-plus-lg"></i>
        New Addon
      </button>
    </div>
  </div>

  <!-- Addons table -->
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
        <!-- Type addon image -->
        <template #item-type_addon_image="{ type_addon_image }">
          <img
            v-if="type_addon_image"
            :src="type_addon_image"
            alt="Addon type image"
            class="img-thumbnail"
            style="width: 50px; height: 50px; object-fit: cover;"
          />
          <span v-else class="text-muted">-</span>
        </template>

        <!-- Status badge -->
        <template #item-is_active="{ is_active }">
          <span v-if="is_active" class="badge bg-success">Active</span>
          <span v-else class="badge bg-danger">Inactive</span>
        </template>

        <!-- Type addon name -->
        <template #item-type_addon_name="{ type_addon_name }">
          <span>{{ type_addon_name || '-' }}</span>
        </template>

        <!-- Formatted base price -->
        <template #item-base_price="{ base_price }">
          {{ formatCurrency(base_price) }}
        </template>

        <!-- Is referral service -->
        <template #item-is_referral_service="{ is_referral_service }">
          <span v-if="is_referral_service" class="badge bg-info">Referral</span>
          <span v-else class="badge bg-secondary">Standard</span>
        </template>

        <!-- Estimated duration -->
        <template #item-estimated_duration_minutes="{ estimated_duration_minutes }">
          <span v-if="estimated_duration_minutes">
            {{ estimated_duration_minutes }} min
          </span>
          <span v-else class="text-muted">-</span>
        </template>

        <!-- Actions -->
        <template #item-actions="item">
          <button class="btn btn-sm btn-warning me-2" @click="openEditModal(item)">
            <i class="bi bi-pencil-square"></i> Edit
          </button>
        </template>
      </EasyDataTable>
    </div>
  </div>

  <!-- Modals -->
  <AddonsEdit
    :show="modalEditVisible"
    :data="selectedData"
    @close="closeEditModal"
    @saved="handleModalSaved"
  />

  <AddonsCreate
    :show="modalCreateVisible"
    @close="closeCreateModal"
    @saved="handleModalSaved"
  />
</template>

<script setup>
/**
 * Addons List Component
 *
 * Displays a searchable table of addons with CRUD functionality.
 * Manages create and edit modals for addon operations.
 *
 * Features:
 * - Searchable data table with pagination
 * - Create new addon functionality
 * - Edit existing addon functionality
 * - Currency formatting for prices
 * - Badge styling for status and type
 * - Duration display with units
 */

import { inject, ref, onMounted, computed } from "vue";
import api from "@/services/axios";
import AddonsEdit from "./AddonsEdit.vue";
import AddonsCreate from "./AddonsCreate.vue";

// Page header configuration
const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Addons", icon: "bi-plus-circle-dotted" });

// Table helpers for search functionality
const tableHelpers = inject("tableHelpers");

// Reactive data
const data = ref([]);
const searchValue = ref("");
const modalEditVisible = ref(false);
const modalCreateVisible = ref(false);
const selectedData = ref(null);

/**
 * Table headers configuration
 * Defines the columns displayed in the addons table
 */
const headers = ref([
  { text: "Image", value: "type_addon_image", sortable: false },
  { text: "Type", value: "type_addon_name", sortable: true },
  { text: "Name", value: "name", sortable: true },
  { text: "Base Price", value: "base_price", sortable: true },
  { text: "Type", value: "is_referral_service", sortable: true },
  { text: "Duration", value: "estimated_duration_minutes", sortable: true },
  { text: "Status", value: "is_active", sortable: true },
  { text: "Actions", value: "actions", sortable: false },
]);

/**
 * Computed search fields for table filtering
 */
const searchField = computed(() => {
  return tableHelpers.generateSearchFields(headers.value);
});

/**
 * Opens the edit modal with selected addon data
 * @param {Object} item - Addon data to edit
 */
const openEditModal = (item) => {
  selectedData.value = { ...item };
  modalEditVisible.value = true;
};

/**
 * Opens the create modal for new addon
 */
const openCreateModal = () => {
  modalCreateVisible.value = true;
};

/**
 * Closes the edit modal
 */
const closeEditModal = () => {
  modalEditVisible.value = false;
  selectedData.value = null;
};

/**
 * Closes the create modal
 */
const closeCreateModal = () => {
  modalCreateVisible.value = false;
};

/**
 * Fetches addons list from the backend API
 */
const fetchAddons = async () => {
  try {
    const response = await api.get("/addons");
    data.value = response.data;
  } catch (error) {
    console.error('Error loading addons:', error);
  }
};

/**
 * Handles modal save events and refreshes data
 */
const handleModalSaved = () => {
  closeEditModal();
  closeCreateModal();
  fetchAddons();
};

/**
 * Formats price value as USD currency
 * @param {number} value - Price value to format
 * @returns {string} Formatted currency string
 */
const formatCurrency = (value) => {
  if (!value) return "$0.00";
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(value);
};

// Initialize component
onMounted(() => {
  fetchAddons();
});
</script>
