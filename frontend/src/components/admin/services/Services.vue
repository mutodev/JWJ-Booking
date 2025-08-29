<template>
    <h1>Servicios</h1>
</template>
<script setup>
import { inject } from "vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Servicios", icon: "bi bi-list" });
</script>