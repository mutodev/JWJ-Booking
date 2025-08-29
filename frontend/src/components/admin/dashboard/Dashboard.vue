<template>
    <h1>Dashboard</h1>
</template>
<script setup>
import { inject } from 'vue';


const updateHeaderData = inject('updateHeaderData')
updateHeaderData({ title: "Dashboard", icon: "bi-speedometer2" });
</script>