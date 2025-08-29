<template>
  <div class="row justify-content-end">
    <div class="col-md-1">
      <button class="btn btn-sm btn-primary">
        <i class="bi bi-plus-lg"></i> Add Role
      </button>
    </div>
  </div>

  <div class="row mt-3">
    <div class="col-md-12">
      <table class="table table-hover">
        <thead>
          <tr>
            <th class="text-center" scope="col">#</th>
            <th class="text-center" scope="col">Role Name</th>
            <th class="text-center" scope="col">State</th>
            <th class="text-center" scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in data" :key="index">
            <th class="text-center" scope="row">{{ index + 1 }}</th>
            <td>{{ item.name }}</td>
            <td>
              <span v-if="item.is_active" class="text-success">Active</span>
              <span v-if="!item.is_active" class="text-danger">Inactive</span>
            </td>
            <td class="text-center">
              <button class="btn btn-sm btn-warning me-2">
                <i class="bi bi-pencil-square"></i> Edit
              </button>
              <button class="btn btn-sm btn-danger">
                <i class="bi bi-trash"></i> Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
<script setup>
import { inject, ref } from "vue";
import api from "@/services/axios";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Roles", icon: "bi bi-shield-lock" });

// Variable reactiva
const data = ref([]);

const getData = async () => {
  try {
    const response = await api.get("/roles");
    data.value = response.data; // <--- actualizar .value
  } catch (error) {
    console.error("Error fetching roles:", error);
  }
};

getData();
</script>
