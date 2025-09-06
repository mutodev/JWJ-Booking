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
          placeholder="Search..."
        />
      </div>
    </div>

    <!-- Botón nuevo -->
    <div class="col-md-2 pt-1">
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
        <template #item-segment="{ segment }">
          <span v-if="segment == 'new'">
            <i :title="segment" class="bi bi-stars text-warning h6"></i>
          </span>
          <span v-if="segment == 'frequent'">
            <i :title="segment" class="bi bi-award text-danger h6"></i>
          </span>
          <span v-if="segment == 'vip'">
            <i :title="segment" class="bi bi-gem text-secondary h6"></i>
          </span>
        </template>

        <!-- Acciones -->
        <template #item-actions="item">
          <button class="btn btn-sm btn-warning me-2" @click="editModal(item)">
            <i class="bi bi-pencil-square"></i> Edit
          </button>
        </template>
      </EasyDataTable>
    </div>
  </div>

  <!-- Modales -->
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
</template>

<script setup>
import { inject, ref, onMounted, computed } from "vue";
import api from "@/services/axios";
import ClientEdit from "./ClientEdit.vue";
import ClientCreate from "./ClientCreate.vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Clients", icon: "bi-people-fill" });

const tableHelpers = inject("tableHelpers");
const data = ref([]);
const searchValue = ref("");

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

onMounted(() => {
  getData();
});
</script>
