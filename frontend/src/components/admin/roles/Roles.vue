<template>
  <div class="row justify-content-end">
    <div class="col-md-1">
      
    </div>
  </div>

  <div class="row mt-3">
    <div class="col-md-12">
      <table class="table table-hover">
        <thead>
          <tr>
            <th class="text-center" scope="col">#</th>
            <th class="text-center" scope="col">Role Name</th>
            <th class="text-center" scope="col">State</th>
            <th class="text-center" scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in data" :key="item.id || index">
            <th class="text-center" scope="row">{{ index + 1 }}</th>
            <td class="text-center">{{ item.name }}</td>
            <td class="text-center">
              <span v-if="item.is_active" class="badge bg-success">Active</span>
              <span v-else class="badge bg-danger">Inactive</span>
            </td>
            <td class="text-center">
              <button
                class="btn btn-sm btn-warning me-2"
                @click="editRoleModal(item)"
              >
                <i class="bi bi-pencil-square"></i> Edit
              </button>
            </td>
          </tr>
        </tbody>
      </table>
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
import { inject, ref, onMounted } from "vue";
import api from "@/services/axios";
import RoleEdit from "./RoleEdit.vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Roles", icon: "bi bi-shield-lock" });

const data = ref([]);
const modalVisible = ref(false);
const selectedRole = ref(null);

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
