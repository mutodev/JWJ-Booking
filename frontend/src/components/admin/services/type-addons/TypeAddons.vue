<template>
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
          placeholder="Search..."
        />
      </div>
    </div>
    <div class="col-md-2 pt-1">
      <button class="btn btn-sm btn-primary" @click="openCreateModal">
        <i class="bi bi-plus-lg"></i>
        New Type
      </button>
    </div>
  </div>

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
        <!-- Estado -->
        <template #item-is_active="{ is_active }">
          <span v-if="is_active" class="badge bg-success">Active</span>
          <span v-else class="badge bg-danger">Inactive</span>
        </template>

        <!-- Descripción -->
        <template #item-description="{ description }">
          <span>
            {{
              description && description.length > 50
                ? description.substring(0, 50) + "..."
                : description || '-'
            }}
          </span>
        </template>

        <!-- Acciones -->
        <template #item-actions="item">
          <button class="btn btn-sm btn-warning me-2" @click="openEditModal(item)">
            <i class="bi bi-pencil-square"></i> Edit
          </button>
        </template>
      </EasyDataTable>
    </div>
  </div>

  <!-- Modales -->
  <TypeAddonsEdit
    :show="modalEditVisible"
    :data="selectedData"
    @close="closeEditModal"
    @saved="handleModalSaved"
  />

  <TypeAddonsCreate
    :show="modalCreateVisible"
    @close="closeCreateModal"
    @saved="handleModalSaved"
  />

</template>

<script setup>
import { inject, ref, onMounted, computed } from "vue";
import api from "@/services/axios";
import TypeAddonsEdit from "./TypeAddonsEdit.vue";
import TypeAddonsCreate from "./TypeAddonsCreate.vue";

// Page header configuration
const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Addon Types", icon: "bi-tags" });

// Table helpers for search functionality
const tableHelpers = inject("tableHelpers");

// Reactive data
const data = ref([]);
const searchValue = ref("");
const modalEditVisible = ref(false);
const modalCreateVisible = ref(false);
const selectedData = ref(null);

// Table headers configuration
const headers = ref([
  { text: "Name", value: "name", sortable: true },
  { text: "Description", value: "description", sortable: true },
  { text: "Status", value: "is_active", sortable: true },
  { text: "Actions", value: "actions", sortable: false },
]);

// Computed search fields for table filtering
const searchField = computed(() => {
  return tableHelpers.generateSearchFields(headers.value);
});

// Opens the edit modal with selected data
const openEditModal = (item) => {
  selectedData.value = { ...item };
  modalEditVisible.value = true;
};

// Opens the create modal
const openCreateModal = () => {
  modalCreateVisible.value = true;
};

// Closes the edit modal
const closeEditModal = () => {
  modalEditVisible.value = false;
  selectedData.value = null;
};

// Closes the create modal
const closeCreateModal = () => {
  modalCreateVisible.value = false;
};

// Fetches data from the backend API
const fetchData = async () => {
  try {
    const response = await api.get("/type-addons");
    data.value = response.data;
  } catch (error) {
    console.error('Error loading type addons:', error);
  }
};

// Handles modal save events and refreshes data
const handleModalSaved = () => {
  closeEditModal();
  closeCreateModal();
  fetchData();
};

// Initialize component
onMounted(() => {
  fetchData();
});
</script>
