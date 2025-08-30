<template>
  <div class="row justify-content-end">
    <div class="col-md-1">
      <button class="btn btn-sm btn-primary">
        <i class="bi bi-plus-lg"></i>
        New User
      </button>
    </div>
  </div>

  <ul class="nav nav-tabs">
    <li class="nav-item" v-for="item in dataRol">
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
      :class="{ active: activeTab === item.id }"
    >
      <UsersTable
        :data="dataUsers"
        :roles="dataRol"
        @data-updated="handleDataUpdate"
      />
    </div>
  </div>
</template>

<script setup>
import { inject, onMounted, ref } from "vue";
import api from "@/services/axios";
import UsersTable from "./UsersTable.vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Usurios", icon: "bi bi-person-gear" });

const activeTab = ref("");
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

onMounted(() => {
  getDataRol();
});
</script>
