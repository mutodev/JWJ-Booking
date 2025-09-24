<template>
  <div class="admin-layout d-flex">
    <!-- Sidebar fijo -->
    <SidebarAdmin />

    <!-- Contenido principal -->
    <div class="flex-grow-1 d-flex flex-column min-vh-100 bg-light">
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

<!-- Styles moved to admin-consolidated.css -->
