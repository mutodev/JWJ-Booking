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
    <div v-if="canCreate" class="col-md-2 pt-1">
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
        <template v-if="canUpdate" #item-actions="item">
          <button class="btn btn-sm btn-warning me-2" @click="editModal(item)">
            <i class="bi bi-pencil-square"></i> Edit
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
    :areas="areas"
    @close="modalCreateVisible = false"
    @saved="handle"
  />

</template>

<script setup>
import { inject, ref, onMounted, computed, watch } from "vue";
import api from "@/services/axios";
import CountiesEdit from "./CountiesEdit.vue";
import CountiesCreate from "./CountiesCreate.vue";
import { useMenuPermissions } from "@/composables/useMenuPermissions";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Counties", icon: "bi-pin-map" });

// Inyectar el helper global
const tableHelpers = inject("tableHelpers");

const data = ref([]);
const areas = ref([]);
const modalEditVisible = ref(false);
const modalCreateVisible = ref(false);
const selectedData = ref(null);
const searchValue = ref("");
const { canCreate, canUpdate } = useMenuPermissions("/admin/areas/counties");

// Headers dinámicos usando el helper global
const headers = computed(() => {
  return tableHelpers.generateTableHeaders(data.value, {
    addActionsColumn: canUpdate.value,
    customLabels: {
      name: "Name",
      metropolitan_area_name: "Metropolitan",
      is_active: "State",
    },
  });
});

const editModal = (item) => {
  selectedData.value = { ...item };
  modalEditVisible.value = true;
};

const createModal = () => {
  modalCreateVisible.value = true;
};

let searchTimeout = null;

const getData = async () => {
  try {
    const params = {};
    const search = searchValue.value.trim();

    if (search) {
      params.search = search;
    }

    const response = await api.get("counties/get-all-and-metropolitan", { params });
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

watch(searchValue, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    getData();
  }, 350);
});

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
