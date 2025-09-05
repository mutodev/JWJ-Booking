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
        New Service
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
                : description
            }}
          </span>
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
  <!-- <ServicesEdit
    :show="modalEditVisible"
    :data="selectedData"
    @close="modalEditVisible = false"
    @saved="handle"
  />

  <ServicesCreate
    :show="modalCreateVisible"
    @close="modalCreateVisible = false"
    @saved="handle"
  />

  <ServicesDelete
    :show="modalDeleteVisible"
    :data="selectedData"
    @close="modalDeleteVisible = false"
    @saved="handle"
  /> -->
</template>

<script setup>
import { inject, ref, onMounted, computed } from "vue";
import api from "@/services/axios";
// import ServicesEdit from "./ServicesEdit.vue";
// import ServicesCreate from "./ServicesCreate.vue";
// import ServicesDelete from "./ServicesDelete.vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Services", icon: "bi-gear" });

const tableHelpers = inject("tableHelpers");
const data = ref([]);
const searchValue = ref("");

const modalEditVisible = ref(false);
const modalCreateVisible = ref(false);
const modalDeleteVisible = ref(false);
const selectedData = ref(null);

const headers = computed(() => {
  return tableHelpers.generateTableHeaders(data.value, {
    customLabels: {
      name: "Name",
      description: "Description",
      is_active: "State",
    },
  });
});

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
    const response = await api.get("/services");
    data.value = response.data;
  } catch (error) {
    console.error(error);
  }
};

const handle = () => {
  modalCreateVisible.value = false;
  modalEditVisible.value = false;
  modalDeleteVisible.value = false;
  getData();
};

onMounted(() => {
  getData();
});
</script>
