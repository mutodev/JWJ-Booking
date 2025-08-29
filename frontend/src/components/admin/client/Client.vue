<template>
  <h1>Clientes</h1>
</template>
<script setup>
import { inject } from "vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Clientes", icon: "bi bi-people" });
</script>
