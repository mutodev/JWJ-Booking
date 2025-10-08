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
        New Postal Code
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
        <template #item-is_active="{ id, is_active }">
          <div class="d-flex justify-content-center">
            <div class="form-check form-switch">
              <input
                class="form-check-input switch-lg"
                type="checkbox"
                role="switch"
                :checked="is_active"
                :class="
                  is_active
                    ? 'bg-success border-success'
                    : 'bg-danger border-danger'
                "
                @change="toggleActive(id)"
              />
            </div>
          </div>
        </template>

      </EasyDataTable>
    </div>
  </div>

  <PostalCodeCreate
    :show="modalCreateVisible"
    :cities="cities"
    @close="modalCreateVisible = false"
    @saved="handle"
  />

</template>

<script setup>
import { inject, ref, onMounted, computed } from "vue";
import api from "@/services/axios";
import PostalCodeCreate from "./PostalCodeCreate.vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Postal codes", icon: "bi-pin" });

const tableHelpers = inject("tableHelpers");
const data = ref([]);
const searchValue = ref("");
const cities = ref([]);

const modalEditVisible = ref(false);
const modalCreateVisible = ref(false);
const selectedData = ref(null);

const headers = computed(() => {
  return tableHelpers.generateTableHeaders(data.value, {
    customLabels: {
      name: "Name",
      is_active: "State",
    },
  });
});

const searchField = computed(() => {
  return tableHelpers.generateSearchFields(headers.value);
});

const createModal = () => {
  modalCreateVisible.value = true;
};


const getData = async () => {
  try {
    const response = await api.get("/zipcodes/get-all-and-city");
    data.value = response.data;
  } catch (error) {
    console.error(error);
  }
};

const getCities = async () => {
  try {
    const response = await api.get("cities/get-all-active");
    cities.value = response.data;
  } catch (error) {
    console.error(error);
  }
};

const handle = () => {
  modalCreateVisible.value = false;
  modalEditVisible.value = false;
  getData();
  getCities();
};

// 🔹 Activar/Desactivar estado
const toggleActive = async (id) => {
  try {
    const index = data.value.findIndex((item) => item.id === id);
    data.value[index].is_active = !data.value[index].is_active;
    await api.put(`/zipcodes/${data.value[index].id}`, data.value[index]);
    getData();
  } catch (error) {
    // Error handled by axios interceptor
  }
};

onMounted(() => {
  getData();
  getCities();
});
</script>

<style scoped>
.switch-lg {
  width: 2rem !important;
  height: 0.8rem !important;
  transform: scale(1.3); /* aumenta el tamaño */
  cursor: pointer;
}
</style>
