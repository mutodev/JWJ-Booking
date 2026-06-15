<template>
  <div class="admin-layout" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
    <SidebarAdmin />
    <div class="admin-main">
      <NavbarAdmin />
      <main class="admin-content p-4">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import SidebarAdmin from "./template/SidebarAdmin.vue";
import NavbarAdmin from "./template/NavbarAdmin.vue";
import { provide, ref } from "vue";

import '@/assets/styles/admin-consolidated.css';

const headerData = ref({ title: "Dashboard", icon: "bi-speedometer2" });
const updateHeaderData = (newData) => { headerData.value = { ...headerData.value, ...newData }; };

const sidebarCollapsed = ref(false);

provide('headerData', headerData);
provide('updateHeaderData', updateHeaderData);
provide('sidebarCollapsed', sidebarCollapsed);
</script>
