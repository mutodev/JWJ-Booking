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
        <!-- Slot para zone type -->
        <template #item-zone_type="{ zone_type }">
          <span
            class="badge"
            :class="{
              'bg-success': zone_type === 'standard',
              'bg-primary': zone_type === 'travel_fee',
              'bg-warning text-dark': zone_type === 'minimum_2h',
              'bg-danger': zone_type === 'not_available'
            }"
          >
            {{ zone_type === 'travel_fee' ? 'Travel Fee' :
               zone_type === 'minimum_2h' ? 'Min 2h' :
               zone_type === 'not_available' ? 'Not Available' :
               'Standard' }}
          </span>
        </template>

        <!-- Slot para travel fees -->
        <template #item-travel_fee_1_performer="{ travel_fee_1_performer }">
          <span v-if="travel_fee_1_performer">{{ `$${parseFloat(travel_fee_1_performer).toFixed(2)}` }}</span>
          <span v-else class="text-muted">-</span>
        </template>

        <template #item-travel_fee_2_performers="{ travel_fee_2_performers }">
          <span v-if="travel_fee_2_performers">{{ `$${parseFloat(travel_fee_2_performers).toFixed(2)}` }}</span>
          <span v-else class="text-muted">-</span>
        </template>

        <!-- Slot para override county -->
        <template #item-override_county_id="{ override_county_id }">
          <span v-if="override_county_id" class="badge bg-info text-dark">
            {{ counties.find(c => c.id === override_county_id)?.name ?? override_county_id }}
          </span>
          <span v-else class="text-muted">—</span>
        </template>

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
                :disabled="!canUpdate"
                @change="toggleActive(id)"
              />
            </div>
          </div>
        </template>

        <!-- Slot para acciones -->
        <template v-if="canUpdate" #item-actions="item">
          <div class="d-flex gap-1 justify-content-center">
            <button class="btn btn-sm btn-warning" @click="editModal(item)">
              <i class="bi bi-pencil-square"></i>
            </button>
          </div>
        </template>

      </EasyDataTable>
    </div>
  </div>

  <PostalCodeCreate
    :show="modalCreateVisible"
    :cities="cities"
    :counties="counties"
    @close="modalCreateVisible = false"
    @saved="handle"
  />

  <PostalCodeEdit
    :show="modalEditVisible"
    :zipcode="selectedData"
    :cities="cities"
    :counties="counties"
    @close="modalEditVisible = false"
    @saved="handle"
  />

</template>

<script setup>
import { inject, ref, onMounted, computed } from "vue";
import api from "@/services/axios";
import PostalCodeCreate from "./PostalCodeCreate.vue";
import PostalCodeEdit from "./PostalCodeEdit.vue";
import { useMenuPermissions } from "@/composables/useMenuPermissions";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Postal codes", icon: "bi-pin" });

const tableHelpers = inject("tableHelpers");
const data = ref([]);
const searchValue = ref("");
const cities = ref([]);
const counties = ref([]);

const modalEditVisible = ref(false);
const modalCreateVisible = ref(false);
const selectedData = ref(null);
const { canCreate, canUpdate } = useMenuPermissions("/admin/areas/postal-codes");

const headers = computed(() => {
  return tableHelpers.generateTableHeaders(data.value, {
    addActionsColumn: canUpdate.value,
    excludeColumns: ["city_id", "id", "deleted_at", "created_at", "updated_at"],
    customLabels: {
      zipcode: "Zipcode",
      zone_type: "Zone Type",
      override_county_id: "Price Override",
      travel_fee_1_performer: "Travel Fee (1P)",
      travel_fee_2_performers: "Travel Fee (2P)",
      is_active: "State",
      actions: "Actions",
    },
  });
});

const searchField = computed(() => {
  return tableHelpers.generateSearchFields(headers.value);
});

const createModal = () => {
  modalCreateVisible.value = true;
};

const editModal = (item) => {
  selectedData.value = item;
  modalEditVisible.value = true;
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

const getCounties = async () => {
  try {
    const response = await api.get("counties");
    counties.value = response.data;
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
  getCounties();
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
