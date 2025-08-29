<template>
  <header>
    <NavbarAdmin />
  </header>
  <main class="container-fluid d-flex">
    <div>
      <SidebarAdmin />
    </div>
    <router-view />
  </main>
</template>
<script setup>
import NavbarAdmin from './template/NavbarAdmin.vue'
import SidebarAdmin from './template/SidebarAdmin.vue'
</script>
