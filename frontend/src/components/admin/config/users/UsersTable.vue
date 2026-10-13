<template>
  <EasyDataTable
    :headers="headers"
    :items="dataUsers"
    :search-field="searchField"
    :search-value="searchValue"
    table-class-name="table table-hover"
    header-text-direction="center"
    body-text-direction="center"
    :rows-per-page="10"
    :rows-per-page-options="[5, 10, 25, 50]"
    show-index
  >
    <template #item-is_active="{ is_active }">
      <span v-if="is_active" class="badge bg-success">Active</span>
      <span v-else class="badge bg-danger">Inactive</span>
    </template>

    <template #item-mfa_enabled="{ mfa_enabled }">
      <span v-if="mfa_enabled" class="badge bg-success">Enabled</span>
      <span v-else class="badge bg-secondary">Pending</span>
    </template>

    <template #item-actions="{ id, mfa_enabled }">
      <button
        class="btn btn-sm btn-warning me-2"
        @click="editUserModal(id)"
        title="Edit User"
      >
        <i class="bi bi-pencil-square"></i>
      </button>
      <button
        v-if="mfa_enabled"
        class="btn btn-sm btn-outline-danger"
        @click="resetMfa(id)"
        title="Reset authenticator"
      >
        <i class="bi bi-shield-x"></i>
      </button>
    </template>
  </EasyDataTable>

  <UserEdit
    :show="modalVisible"
    :data="selectedData"
    :roles="dataRoles"
    @close="modalVisible = false"
    @saved="handleRoleSaved"
  />
</template>
<script setup>
import { ref, watch, defineEmits, inject } from "vue";
import UserEdit from "./UserEdit.vue";
import api from "@/services/axios";

const emit = defineEmits(['data-updated']);
const tableHelpers = inject('tableHelpers');

const props = defineProps({
  data: {
    type: Array,
    default: () => []
  },
  roles: {
    type: Array,
    default: () => []
  },
  searchValue: {
    type: String,
    default: ''
  }
});

// EasyDataTable configuration
const headers = ref([
  { text: "First Name", value: "first_name", sortable: true },
  { text: "Last Name", value: "last_name", sortable: true },
  { text: "Email", value: "email", sortable: true },
  { text: "State", value: "is_active", sortable: true },
  { text: "MFA", value: "mfa_enabled", sortable: true },
  { text: "Actions", value: "actions", sortable: false }
]);

const searchField = ref([
  "first_name",
  "last_name",
  "email"
]);

const dataUsers = ref([]);
const dataRoles = ref([]);
const modalVisible = ref(false);
const selectedData = ref(null);

watch(
  () => props.data,
  (newData) => {
    dataUsers.value = [...newData];
  },
  { deep: true, immediate: true }
);

watch(
  () => props.roles,
  (newRoles) => {
    dataRoles.value = [...newRoles];
  },
  { deep: true, immediate: true }
);

const editUserModal = (userId) => {
  // Buscar el usuario por ID
  const user = dataUsers.value.find(user => user.id === userId);
  if (user) {
    selectedData.value = { ...user };
    modalVisible.value = true;
  }
};

const handleRoleSaved = () => {
  emit('data-updated', true);
};

const resetMfa = async (userId) => {
  if (!window.confirm("Reset this user's authenticator? They will need to scan a new QR code on their next sign-in.")) {
    return;
  }

  await api.post(`/users/${userId}/mfa/reset`);
  emit('data-updated', true);
};
</script>
