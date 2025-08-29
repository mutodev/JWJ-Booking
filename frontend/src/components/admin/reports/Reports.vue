<template>
    <h1>Reportes</h1>
</template>
<script setup>
import { inject } from "vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Reportes", icon: "bi bi-bar-chart" });
</script>