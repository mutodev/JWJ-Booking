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
      <button class="btn btn-sm btn-primary" @click="createModal()">
        <i class="bi bi-plus-lg"></i>
        New County
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
        <!-- Slot para el estado -->
        <template #item-is_active="{ is_active }">
          <span v-if="is_active" class="badge bg-success">Active</span>
          <span v-else class="badge bg-danger">Inactive</span>
        </template>

        <!-- Slot para las acciones -->
        <template #item-actions="item">
          <button class="btn btn-sm btn-warning me-2" @click="editModal(item)">
            <i class="bi bi-pencil-square"></i> Edit
          </button>
          <button class="btn btn-sm btn-danger" @click="deleteModal(item)">
            <i class="bi bi-trash"></i> Delete
          </button>
        </template>
      </EasyDataTable>
    </div>
  </div>

  <CountiesEdit
    :show="modalEditVisible"
    :data="selectedData"
    :areas="areas"
    @close="modalEditVisible = false"
    @saved="handle"
  />

  <CountiesCreate
    :show="modalCreateVisible"
    :roles="dataRol"
    :areas="areas"
    @close="modalCreateVisible = false"
    @saved="handle"
  />

  <CountiesDelete
    :show="modalDeleteVisible"
    :data="selectedData"
    @close="modalDeleteVisible = false"
    @saved="handle"
  />
</template>

<script setup>
import { inject, ref, onMounted, computed } from "vue";
import api from "@/services/axios";
import CountiesEdit from "./CountiesEdit.vue";
import CountiesCreate from "./CountiesCreate.vue";
import CountiesDelete from "./CountiesDelete.vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Counties", icon: "bi-pin-map" });

// Inyectar el helper global
const tableHelpers = inject("tableHelpers");

const data = ref([]);
const areas = ref([]);
const modalEditVisible = ref(false);
const modalCreateVisible = ref(false);
const modalDeleteVisible = ref(false);
const selectedData = ref(null);
const searchValue = ref("");

// Headers dinámicos usando el helper global
const headers = computed(() => {
  return tableHelpers.generateTableHeaders(data.value, {
    customLabels: {
      name: "Name",
      metropolitan_area_name: "Metropolitan",
      is_active: "State",
    },
  });
});

// Campos de búsqueda usando el helper global
const searchField = computed(() => {
  return tableHelpers.generateSearchFields(headers.value);
});

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

const getData = async () => {
  try {
    const response = await api.get("counties/get-all-and-metropolitan");
    data.value = response.data;
  } catch (error) {
    console.error(error);
  }
};

const getAreas = async () => {
  try {
    const response = await api.get("metropolitan-areas/list-active");
    areas.value = response.data;
  } catch (error) {
    console.error(error);
  }
};

const handle = () => {
  modalCreateVisible.value = false;
  modalEditVisible.value = false;
  getData();
  getAreas();
};

onMounted(() => {
  getData();
  getAreas();
});
</script>

<style scoped>
:deep(.vue3-easy-data-table__main) {
  border-radius: 0.375rem;
  overflow: hidden;
}

:deep(.vue3-easy-data-table__header) {
  background-color: var(--bs-light);
}

:deep(.vue3-easy-data-table__body tr:hover) {
  background-color: var(--bs-light);
}
</style>
