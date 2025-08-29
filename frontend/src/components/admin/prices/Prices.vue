<template>
    <h1>Precios</h1>
</template>
<script setup>
import { inject } from "vue";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Precios", icon: "bi bi-tag" });
</script>