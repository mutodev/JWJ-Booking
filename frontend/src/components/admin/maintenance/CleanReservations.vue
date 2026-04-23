<template>
  <div class="container-fluid">
    <div class="row justify-content-center mt-5">
      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="bi bi-trash3 me-2"></i>Clean Old Reservations</h5>
          </div>
          <div class="card-body">
            <div v-if="!result" class="text-center">
              <p class="text-muted mb-4">
                This will delete all reservations with an event date on or before
                <strong>last Sunday</strong>. This action uses soft delete.
              </p>
              <button
                class="btn btn-danger btn-lg"
                @click="confirmDelete"
                :disabled="loading"
              >
                <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                {{ loading ? 'Deleting...' : 'Delete Old Reservations' }}
              </button>
            </div>

            <div v-else class="text-center">
              <div class="mb-3">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
              </div>
              <h5>Done!</h5>
              <p class="mb-1">Cutoff date: <strong>{{ result.cutoff_date }}</strong></p>
              <p class="mb-4">Reservations deleted: <strong>{{ result.deleted_count }}</strong></p>
              <button class="btn btn-outline-secondary" @click="result = null">Run Again</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, inject, onMounted } from "vue";
import api from "@/services/axios";

const updateHeaderData = inject("updateHeaderData");
const loading = ref(false);
const result = ref(null);

onMounted(() => {
  updateHeaderData({ title: "Clean Reservations", icon: "bi-trash3" });
});

const confirmDelete = async () => {
  if (!window.confirm("Are you sure you want to delete all old reservations?")) return;

  try {
    loading.value = true;
    const response = await api.delete("/reservations/old");
    result.value = response.data;
  } catch (error) {
    console.error("Error deleting old reservations:", error);
  } finally {
    loading.value = false;
  }
};
</script>
