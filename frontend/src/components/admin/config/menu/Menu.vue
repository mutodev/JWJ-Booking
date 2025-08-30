<template>
  <h1>Menus</h1>
</template>
<script setup>
import { inject } from "vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Menús", icon: "bi bi-list-nested" });
</script>
