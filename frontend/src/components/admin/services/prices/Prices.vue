<template>
  <!-- 🔍 Buscador + botón crear -->
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
        New Price
      </button>
    </div>
  </div>

  <!-- 📋 Tabla -->
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
        <!-- Estado disponible -->
        <template #item-is_available="{ is_available }">
          <span v-if="is_available" class="badge bg-success">Active</span>
          <span v-else class="badge bg-danger">Inactive</span>
        </template>

        <!-- 💵 Mostrar currency en amount -->
        <template #item-amount="{ amount }">
          {{ formatCurrency(amount) }}
        </template>

        <!-- 💵 Mostrar currency en extra_child_fee -->
        <template #item-extra_child_fee="{ extra_child_fee }">
          {{ formatCurrency(extra_child_fee) }}
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

  <!-- 🟢 Modales -->
  <PricesCreate
    :show="modalCreateVisible"
    :services="services"
    :counties="counties"
    @close="modalCreateVisible = false"
    @saved="handle"
  />
  <PricesEdit
    :show="modalEditVisible"
    :data="selectedData"
    :services="services"
    :counties="counties"
    @close="modalEditVisible = false"
    @saved="handle"
  />
</template>

<script setup>
import { inject, ref, onMounted, computed } from "vue";
import api from "@/services/axios";

// 🟢 Importar modal de creación
import PricesCreate from "./PricesCreate.vue";
import PricesEdit from "./PricesEdit.vue";

// 🏷️ Header dinámico
const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Prices", icon: "bi bi-currency-dollar" });

// 📊 Helpers de tabla
const tableHelpers = inject("tableHelpers");
const data = ref([]);
const services = ref([]); // 👈 lista de servicios activos
const counties = ref([]); // 👈 lista de counties activos
const searchValue = ref("");

// 🔄 Estados de modales
const modalEditVisible = ref(false);
const modalCreateVisible = ref(false);
const modalDeleteVisible = ref(false);
const selectedData = ref(null);

// ✏️ Editar
const editModal = (item) => {
  selectedData.value = { ...item };
  modalEditVisible.value = true;
};

// ➕ Crear
const createModal = () => {
  modalCreateVisible.value = true;
};

// 🗑️ Eliminar
const deleteModal = (item) => {
  selectedData.value = { ...item };
  modalDeleteVisible.value = true;
};

// 📝 Cabeceras de tabla - solo campos importantes
const headers = ref([
  { text: "Service", value: "service_name" },
  { text: "County", value: "county_name" },
  { text: "Performers", value: "performers_count" },
  { text: "Base Price", value: "amount" },
  { text: "Extra Child Fee", value: "extra_child_fee" },
  { text: "Age Range", value: "range_age" },
  { text: "Status", value: "is_available" },
  { text: "Actions", value: "actions" },
]);

// 🔍 Campos de búsqueda
const searchField = computed(() => {
  return tableHelpers.generateSearchFields(headers.value);
});

// 📡 Obtener precios
const getData = async () => {
  try {
    const response = await api.get("/service-prices");
    data.value = response.data;
  } catch (error) {
    console.log(error);
  }
};

// 📡 Obtener servicios activos
const getServices = async () => {
  try {
    const response = await api.get("/services/get-all-active");
    services.value = response.data;
  } catch (error) {
    console.log(error);
  }
};

// 📡 Obtener counties activos
const getCounties = async () => {
  try {
    const response = await api.get("/counties/get-all-active");
    counties.value = response.data;
  } catch (error) {
    console.log(error);
  }
};

// 💵 Formatear currency USD
const formatCurrency = (value) => {
  if (!value) return "$0.00";
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(value);
};

// 🔄 Recargar tabla tras guardar/editar/eliminar
const handle = () => {
  modalCreateVisible.value = false;
  modalEditVisible.value = false;
  modalDeleteVisible.value = false;
  getData();
  getServices();
  getCounties();
};

onMounted(() => {
  getData();
  getServices();
  getCounties();
});
</script>
