<template>
  <div class="row justify-content-end">
    <div class="col-md-2">
      <button class="btn btn-sm btn-primary" @click="createModal()">
        <i class="bi bi-plus-lg"></i>
        New Metropolitan
      </button>
    </div>
  </div>

  <div class="row mt-3">
    <div class="col-md-12">
      <table class="table table-hover">
        <thead>
          <tr>
            <th class="text-center" scope="col">#</th>
            <th scope="col">Name</th>
            <th class="text-center" scope="col">State</th>
            <th class="text-center" scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in data" :key="item.id || index">
            <th class="text-center" scope="row">{{ index + 1 }}</th>
            <td>{{ item.name }}</td>
            <td class="text-center">
              <span v-if="item.is_active" class="badge bg-success">Active</span>
              <span v-else class="badge bg-danger">Inactive</span>
            </td>
            <td class="text-center">
              <button
                class="btn btn-sm btn-warning me-2"
                @click="editModal(item)"
              >
                <i class="bi bi-pencil-square"></i> Edit
              </button>
              <button
                class="btn btn-sm btn-danger me-2"
                @click="deleteModal(item)"
              >
                <i class="bi bi-trash"></i> Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <MetropolitanAreaEdit
    :show="modalEditVisible"
    :data="selectedData"
    @close="modalEditVisible = false"
    @saved="handle"
  />

  <MetropolitanAreaCreate
    :show="modalCreateVisible"
    :roles="dataRol"
    @close="modalCreateVisible = false"
    @saved="handle"
  />

  <MetropolitanAreaDelete
    :show="modalDeleteVisible"
    :data="selectedData"
    @close="modalDeleteVisible = false"
    @saved="handle"
  />



</template>
<script setup>
import { inject, ref, onMounted } from "vue";
import api from "@/services/axios";
import MetropolitanAreaEdit from "./MetropolitanAreaEdit.vue";
import MetropolitanAreaCreate from "./MetropolitanAreaCreate.vue";
import MetropolitanAreaDelete from "./MetropolitanAreaDelete.vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Metropolitan Areas", icon: "bi-building" });

const data = ref([]);
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

const getData = async () => {
  try {
    const response = await api.get("/metropolitan-areas");
    data.value = response.data;
  } catch (error) {
    console.error("Error fetching roles:", error);
  }
};

const handle = () => {
  modalCreateVisible.value = false;
  modalEditVisible.value = false;
  getData();
};

onMounted(() => {
  getData();
});
</script>
