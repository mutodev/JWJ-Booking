<template>
  <div
    v-if="show"
    class="admin-modal modal fade show d-block"
    tabindex="-1"
    role="dialog"
  >
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Delete Promo Code</h5>
          <button
            type="button"
            class="btn-close btn-close-white"
            @click="closeModal"
          ></button>
        </div>

        <div class="modal-body">
          <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Warning!</strong> This action cannot be undone.
          </div>

          <p>Are you sure you want to delete this promo code?</p>

          <div class="card">
            <div class="card-body">
              <p class="mb-1"><strong>Code:</strong> <span class="badge bg-primary">{{ data.code }}</span></p>
              <p class="mb-1"><strong>Discount:</strong> {{ data.discount_percentage }}%</p>
              <p class="mb-1"><strong>Times Used:</strong> {{ data.usage_count || 0 }}</p>
              <p class="mb-0"><strong>Status:</strong>
                <span v-if="data.is_active" class="badge bg-success">Active</span>
                <span v-else class="badge bg-secondary">Inactive</span>
              </p>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            <i class="bi bi-x-circle"></i> Cancel
          </button>
          <button type="button" class="btn btn-danger" @click="deleteItem" :disabled="deleting">
            <i class="bi bi-trash"></i>
            {{ deleting ? 'Deleting...' : 'Delete Promo Code' }}
          </button>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import api from "@/services/axios";

const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
  data: {
    type: Object,
    default: () => ({}),
  },
});

const deleting = ref(false);

const closeModal = () => {
  emit("close");
};

const deleteItem = async () => {
  deleting.value = true;
  try {
    await api.delete(`/promo-codes/${props.data.id}`);
    emit("saved");
    emit("close");
  } catch (error) {
    console.error("Error deleting promo code:", error);
  } finally {
    deleting.value = false;
  }
};
</script>
