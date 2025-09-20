<template>
  <div class="d-flex">
    <!-- Sidebar fijo -->
    <SidebarAdmin />

    <!-- Contenido principal -->
    <div class="flex-grow-1 d-flex flex-column min-vh-100 bg-light">
      <NavbarAdmin />
      <main class="p-4">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import SidebarAdmin from "./template/SidebarAdmin.vue";
import NavbarAdmin from "./template/NavbarAdmin.vue";
import { provide, ref } from "vue";

import '@/assets/styles/style-admin.css';

const headerData = ref({
  title: "Dashboard",
  icon: "bi-speedometer2",
});

const updateHeaderData = (newData) => {
  headerData.value = { ...headerData.value, ...newData }
}

provide('headerData', headerData)
provide('updateHeaderData', updateHeaderData)
</script>

<style scoped>
main {
  margin-left: 250px;
  transition: margin-left 0.3s ease;
}

/* Mobile: sidebar angosto */
@media (max-width: 991px) {
  main {
    margin-left: 70px;
  }
}
</style>
