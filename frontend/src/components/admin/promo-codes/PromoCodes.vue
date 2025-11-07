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
          placeholder="Search promo codes..."
        />
      </div>
    </div>

    <!-- Botón nuevo -->
    <div class="col-md-2 pt-1">
      <button class="btn btn-sm btn-primary" @click="createModal()">
        <i class="bi bi-plus-lg"></i>
        New Promo Code
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
        <!-- Code (con badge) -->
        <template #item-code="{ code }">
          <span class="badge bg-primary">{{ code }}</span>
        </template>

        <!-- Discount -->
        <template #item-discount_percentage="{ discount_percentage }">
          <span class="text-success fw-bold">{{ discount_percentage }}%</span>
        </template>

        <!-- Is Active -->
        <template #item-is_active="{ is_active }">
          <span v-if="is_active" class="badge bg-success">
            <i class="bi bi-check-circle"></i> Active
          </span>
          <span v-else class="badge bg-secondary">
            <i class="bi bi-x-circle"></i> Inactive
          </span>
        </template>

        <!-- Valid dates -->
        <template #item-valid_from="{ valid_from }">
          {{ formatDate(valid_from) }}
        </template>

        <template #item-valid_until="{ valid_until }">
          {{ formatDate(valid_until) }}
        </template>

        <!-- Acciones -->
        <template #item-actions="item">
          <button class="btn btn-sm btn-warning" @click="editModal(item)">
            <i class="bi bi-pencil-square"></i> Edit
          </button>
        </template>
      </EasyDataTable>
    </div>
  </div>

  <!-- Modales -->
  <PromoCodeEdit
    :show="modalEditVisible"
    :data="selectedData"
    @close="modalEditVisible = false"
    @saved="handle"
  />

  <PromoCodeCreate
    :show="modalCreateVisible"
    @close="modalCreateVisible = false"
    @saved="handle"
  />
</template>

<script setup>
import { inject, ref, onMounted, computed } from "vue";
import api from "@/services/axios";
import PromoCodeEdit from "./PromoCodeEdit.vue";
import PromoCodeCreate from "./PromoCodeCreate.vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Promo Codes", icon: "bi-ticket-perforated" });

const tableHelpers = inject("tableHelpers");
const data = ref([]);
const searchValue = ref("");

// Modales
const modalEditVisible = ref(false);
const modalCreateVisible = ref(false);
const selectedData = ref(null);

const editModal = (item) => {
  selectedData.value = { ...item };
  modalEditVisible.value = true;
};

const createModal = () => {
  modalCreateVisible.value = true;
};

// Headers dinámicos
const headers = computed(() => {
  return tableHelpers.generateTableHeaders(data.value, {
    customLabels: {
      code: "Code",
      discount_percentage: "Discount %",
      is_active: "Status",
      valid_from: "Valid From",
      valid_until: "Valid Until",
      usage_count: "Times Used",
      max_uses: "Max Uses",
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
    const response = await api.get("/promo-codes");
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

// Format date helper
const formatDate = (date) => {
  if (!date) return "N/A";
  const d = new Date(date);
  return d.toLocaleDateString("en-US", {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

onMounted(() => {
  getData();
});
</script>
