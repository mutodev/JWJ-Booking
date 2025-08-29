<template>
  <div class="d-flex">
    <!-- Sidebar fijo -->
    <SidebarAdmin />

    <!-- Contenido principal -->
    <div class="flex-grow-1 d-flex flex-column min-vh-100 bg-light">
      <NavbarAdmin />
      <main class="p-4">
        <h2 class="mb-3">Bienvenido al Panel</h2>
        <p>Este es un ejemplo de contenido para tu administración.</p>
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import SidebarAdmin from "./template/SidebarAdmin.vue";
import NavbarAdmin from "./template/NavbarAdmin.vue";
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
