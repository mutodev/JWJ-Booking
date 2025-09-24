<template>
  <div>
    <div class="row justify-content-end mb-3">
      <div class="col-10">
        <div class="input-group">
          <span class="input-group-text">
            <i class="bi bi-search"></i>
          </span>
          <input
            v-model="searchValue"
            type="text"
            class="form-control"
            placeholder="Search users..."
          />
        </div>
      </div>
      <div class="col-md-2">
        <button class="btn btn-sm btn-primary" @click="createUserModal()">
          <i class="bi bi-plus-lg"></i>
          New User
        </button>
      </div>
    </div>

    <ul class="nav nav-tabs">
      <li class="nav-item" v-for="item in dataRol" :key="item.id">
        <a
          class="nav-link"
          :href="'#' + item.id"
          :class="{ active: activeTab === item.id }"
          @click.prevent="setActiveTab(item.id)"
        >
          {{ item.name }}
        </a>
      </li>
    </ul>

    <div class="tab-content">
      <div
        class="tab-pane"
        v-for="item in dataRol"
        :key="item.id"
        :class="{ active: activeTab === item.id }"
      >
        <UsersTable
          :data="dataUsers"
          :roles="dataRol"
          :search-value="searchValue"
          @data-updated="handleDataUpdate"
        />
      </div>
    </div>

    <UserCreate
      :show="modalVisible"
      :roles="dataRol"
      @close="modalVisible = false"
      @data-updated="handleDataUpdate"
    />
  </div>
</template>

<script setup>
import { inject, onMounted, ref } from "vue";
import api from "@/services/axios";
import UsersTable from "./UsersTable.vue";
import UserCreate from "./UserCreate.vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Users", icon: "bi bi-person-gear" });

const activeTab = ref("");
const modalVisible = ref(false);
const searchValue = ref("");
const setActiveTab = (roleId) => {
  activeTab.value = roleId;
  getDataByRole(roleId);
};

/**
 * Consulta de roles
 */
const dataRol = ref([]);
const getDataRol = async () => {
  const response = await api.get("/roles");
  dataRol.value = response.data;
  setActiveTab(response.data[0]?.id || "");
};

/**
 * Consulta de usuarios por rol
 */
const dataUsers = ref([]);
const getDataByRole = async (roleId) => {
  const response = await api.get(`/users/by-rol/${roleId}`);
  dataUsers.value = response.data;
};

/**
 * Actualización de datos por cierre de modal
 * @param updated
 */
const handleDataUpdate = (updated) => {
  if (updated) {
    getDataByRole(activeTab.value);
  }
};

/**
 * Modal de creación de usuario
 */
const createUserModal = () => {
  modalVisible.value = true;
};

onMounted(() => {
  getDataRol();
});
</script>
