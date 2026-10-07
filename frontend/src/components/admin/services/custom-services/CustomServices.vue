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
        New Custom Service
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
        <!-- Detalle -->
        <template #item-detail="{ detail }">
          <span>
            {{
              detail && detail.length > 50
                ? detail.substring(0, 50) + "..."
                : detail
            }}
          </span>
        </template>

        <!-- Valor -->
        <template #item-price="{ price }">
          <span>{{ formatPrice(price) }}</span>
        </template>

        <!-- Estado -->
        <template #item-is_active="{ is_active }">
          <span v-if="is_active" class="badge bg-success">Active</span>
          <span v-else class="badge bg-danger">Inactive</span>
        </template>

        <!-- Acciones -->
        <template v-if="canUpdate || canDelete" #item-actions="item">
          <button
            v-if="canUpdate"
            class="btn btn-sm btn-warning me-2"
            @click="editModal(item)"
          >
            <i class="bi bi-pencil-square"></i> Edit
          </button>
          <button
            v-if="canDelete"
            class="btn btn-sm btn-danger"
            @click="deleteModal(item)"
          >
            <i class="bi bi-trash"></i> Delete
          </button>
        </template>
      </EasyDataTable>
    </div>
  </div>

  <!-- Modales -->
  <CustomServicesCreate
    :show="modalCreateVisible"
    @close="modalCreateVisible = false"
    @saved="handle"
  />

  <CustomServicesEdit
    :show="modalEditVisible"
    :data="selectedData"
    @close="modalEditVisible = false"
    @saved="handle"
  />

  <CustomServicesDelete
    :show="modalDeleteVisible"
    :data="selectedData"
    @close="modalDeleteVisible = false"
    @saved="handle"
  />
</template>

<script setup>
import { inject, ref, onMounted, computed } from "vue";
import api from "@/services/axios";
import CustomServicesCreate from "./CustomServicesCreate.vue";
import CustomServicesEdit from "./CustomServicesEdit.vue";
import CustomServicesDelete from "./CustomServicesDelete.vue";
import { useMenuPermissions } from "@/composables/useMenuPermissions";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Custom Services", icon: "bi-stars" });

const data = ref([]);
const searchValue = ref("");
const { canCreate, canUpdate, canDelete } = useMenuPermissions(
  "/admin/services/custom-services"
);

const modalCreateVisible = ref(false);
const modalEditVisible = ref(false);
const modalDeleteVisible = ref(false);
const selectedData = ref(null);

// Headers fijos para que la tabla se vea aunque no haya registros
const headers = computed(() => {
  const cols = [
    { text: "Name", value: "name", sortable: true },
    { text: "Detail", value: "detail", sortable: true },
    { text: "Price", value: "price", sortable: true },
    { text: "State", value: "is_active", sortable: true },
  ];
  if (canUpdate.value || canDelete.value) {
    cols.push({ text: "Actions", value: "actions" });
  }
  return cols;
});

const searchField = ["name", "detail", "price"];

const formatPrice = (value) =>
  `$${(parseFloat(value) || 0).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;

const createModal = () => {
  modalCreateVisible.value = true;
};

const editModal = (item) => {
  selectedData.value = { ...item };
  modalEditVisible.value = true;
};

const deleteModal = (item) => {
  selectedData.value = { ...item };
  modalDeleteVisible.value = true;
};

const getData = async () => {
  try {
    const response = await api.get("/custom-services");
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
