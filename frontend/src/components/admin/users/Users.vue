<template>
    <h1>
        Users
    </h1>
</template>
<script setup>
import { inject } from "vue";


const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Usurios", icon: "bi bi-person-gear" });
</script>