<template>
  <div class="row mt-3">
    <div class="col-md-12">
      <!-- Buscador en la parte superior -->
      <div class="col-3 mb-3">
        <div class="input-group">
          <input
            v-model="searchValue"
            type="text"
            class="form-control"
            placeholder="Search..."
          />
        </div>
      </div>

      <!-- EasyDataTable -->
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
          <button class="btn btn-sm btn-warning" @click="editRoleModal(item)">
            <i class="bi bi-pencil-square"></i> Edit
          </button>
        </template>
      </EasyDataTable>
    </div>
  </div>

  <RoleEdit
    :show="modalVisible"
    :data="selectedRole"
    @close="modalVisible = false"
    @saved="handleRoleSaved"
  />
</template>

<script setup>
import { inject, ref, onMounted, computed } from "vue";
import api from "@/services/axios";
import RoleEdit from "./RoleEdit.vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Roles", icon: "bi bi-shield-lock" });

// Inyectar el helper global
const tableHelpers = inject("tableHelpers");

const data = ref([]);
const modalVisible = ref(false);
const selectedRole = ref(null);
const searchValue = ref("");

// Headers dinámicos usando el helper global
const headers = computed(() => {
  return tableHelpers.generateTableHeaders(data.value, {
    customLabels: {
      name: "Role Name",
      is_active: "State",
    },
  });
});

// Campos de búsqueda usando el helper global
const searchField = computed(() => {
  return tableHelpers.generateSearchFields(headers.value);
});

const getData = async () => {
  try {
    const response = await api.get("/roles");
    data.value = response.data;
  } catch (error) {
    console.error("Error fetching roles:", error);
  }
};

const editRoleModal = (item) => {
  selectedRole.value = { ...item };
  modalVisible.value = true;
};

const handleRoleSaved = () => {
  modalVisible.value = false;
  getData();
};

onMounted(() => {
  getData();
});
</script>

<style scoped>
/* Mantener los estilos existentes y agregar algunos para EasyDataTable */
.input-group-text {
  background-color: var(--bs-light);
  border-color: var(--bs-border-color);
}

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
