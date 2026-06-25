<template>
  <div class="row justify-content-end align-items-center">
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
          placeholder="Search..."
        />
      </div>
    </div>

    <!-- Botón bulk delete (solo cuando hay selección) -->
    <div class="col-auto" v-if="selectedItems.length > 0">
      <button class="btn btn-sm btn-danger" @click="bulkDeleteModal = true">
        <i class="bi bi-trash3"></i>
        Delete Selected ({{ selectedItems.length }})
      </button>
    </div>

    <!-- Botón nuevo -->
    <div class="col-auto">
      <button class="btn btn-sm btn-primary" @click="createModal()">
        <i class="bi bi-plus-lg"></i>
        New Customer
      </button>
    </div>
  </div>

  <!-- Tabla -->
  <div class="row mt-3">
    <div class="col-md-12">
      <EasyDataTable
        v-model:items-selected="selectedItems"
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
        <!-- Segmento -->
        <template #item-segment="{ segment }">
          <span v-if="segment == 'new'">
            <i :title="segment" class="bi bi-stars text-warning h6"></i>
          </span>
          <span v-if="segment == 'frequent'">
            <i :title="segment" class="bi bi-award text-danger small h6"></i>
          </span>
          <span v-if="segment == 'vip'">
            <i :title="segment" class="bi bi-gem text-secondary h6"></i>
          </span>
        </template>

        <!-- Acciones -->
        <template #item-actions="item">
          <button class="btn btn-sm btn-action-icon btn-warning me-1" @click="editModal(item)" title="Edit">
            <i class="bi bi-pencil-square"></i>
          </button>
          <button class="btn btn-sm btn-action-icon btn-danger" @click="deleteModal(item)" title="Delete">
            <i class="bi bi-trash3"></i>
          </button>
        </template>
      </EasyDataTable>
    </div>
  </div>

  <!-- Modales CRUD -->
  <ClientEdit
    :show="modalEditVisible"
    :data="selectedData"
    @close="modalEditVisible = false"
    @saved="handle"
  />

  <ClientCreate
    :show="modalCreateVisible"
    @close="modalCreateVisible = false"
    @saved="handle"
  />

  <ClientDelete
    :show="modalDeleteVisible"
    :data="selectedData"
    @close="modalDeleteVisible = false"
    @deleted="handle"
  />

  <!-- Modal bulk delete -->
  <ConfirmModal
    :show="bulkDeleteModal"
    title="Delete Customers"
    :message="`You are about to delete <strong>${selectedItems.length} customer(s)</strong>. This action cannot be undone.`"
    confirmLabel="Delete Selected"
    @confirm="submitBulkDelete"
    @cancel="bulkDeleteModal = false"
  />
</template>

<script setup>
import { inject, ref, onMounted, computed } from "vue";
import api from "@/services/axios";
import ClientEdit from "./ClientEdit.vue";
import ClientCreate from "./ClientCreate.vue";
import ClientDelete from "./ClientDelete.vue";
import ConfirmModal from "@/components/admin/shared/ConfirmModal.vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Clients", icon: "bi-people-fill" });

const tableHelpers = inject("tableHelpers");
const data = ref([]);
const searchValue = ref("");
const selectedItems = ref([]);
const bulkDeleteModal = ref(false);

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

// Headers dinámicos
const headers = computed(() => {
  return tableHelpers.generateTableHeaders(data.value, {
    customLabels: {
      full_name: "Full Name",
      email: "Email",
      phone: "Phone",
      billing_address: "Billing Address",
      segment: "Segment",
    },
  });
});

// Campos de búsqueda
const searchField = computed(() => {
  return tableHelpers.generateSearchFields(headers.value);
});

// Cargar data
const getData = async () => {
  try {
    const response = await api.get("/customers");
    data.value = response.data;
  } catch (error) {
    console.error(error);
  }
};

// Refrescar tabla tras CRUD
const handle = () => {
  modalCreateVisible.value = false;
  modalEditVisible.value = false;
  modalDeleteVisible.value = false;
  getData();
};

// Bulk delete
const submitBulkDelete = async () => {
  try {
    const ids = selectedItems.value.map((item) => item.id);
    await api.post("/customers/bulk-delete", { ids });
    bulkDeleteModal.value = false;
    selectedItems.value = [];
    getData();
  } catch (error) {
    console.error("Error bulk deleting customers:", error);
  }
};

onMounted(() => {
  getData();
});
</script>
