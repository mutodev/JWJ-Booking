<template>
    <h1>Reservaciones</h1>
</template>
<script setup>
import { inject } from "vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Reservaciones", icon: "bi bi-calendar-check" });
</script>