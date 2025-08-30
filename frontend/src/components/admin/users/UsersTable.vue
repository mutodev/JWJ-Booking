<template>
  <table class="table table-hover">
    <thead>
      <tr>
        <th class="text-center" scope="col">#</th>
        <th scope="col">First Name</th>
        <th scope="col">Last Name</th>
        <th scope="col">Email</th>
        <th class="text-center" scope="col">State</th>
        <th class="text-center" scope="col">Actions</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="(item, index) in internalData" :key="item.id || index">
        <th class="text-center" scope="row">{{ index + 1 }}</th>
        <td>{{ item.first_name }}</td>
        <td>{{ item.last_name }}</td>
        <td>{{ item.email }}</td>
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
          <button class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-clockwise"></i>
            Reset Password
          </button>
        </td>
      </tr>
    </tbody>
  </table>

  <!-- <UserEdit
    :show="modalVisible"
    :data="selectedData"
    @close="modalVisible = false"
  /> -->
</template>
<script setup>
import { ref, watch } from "vue";
import UserEdit from "./UserEdit.vue";

const props = defineProps({
  data: Array,
});

const internalData = ref([]);
const modalVisible = ref(false);
const selectedData = ref(null);

watch(
  () => props.data,
  (newData) => {
    internalData.value = [...newData];
  },
  { deep: true, immediate: true }
);

const editRoleModal = (item) => {
  selectedData.value = { ...item };
  modalVisible.value = true;
};
</script>
