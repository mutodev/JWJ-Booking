<template>
  <div
    v-if="show"
    class="admin-modal modal fade show d-block"
    tabindex="-1"
    role="dialog"
  >
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header bg-danger">
          <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Delete Promo Code</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <div class="modal-delete-warning">
            <i class="bi bi-shield-exclamation"></i>
            <p>This action <strong>cannot be undone</strong>. The following promo code will be permanently deleted.</p>
          </div>
          <div class="card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted" style="font-size:0.78rem">Code</span>
                <span class="badge bg-primary">{{ data.code }}</span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted" style="font-size:0.78rem">Discount</span>
                <strong>{{ data.discount_percentage }}%</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted" style="font-size:0.78rem">Times Used</span>
                <strong>{{ data.usage_count || 0 }}</strong>
              </div>
              <div class="d-flex justify-content-between">
                <span class="text-muted" style="font-size:0.78rem">Status</span>
                <span v-if="data.is_active" class="badge bg-success">Active</span>
                <span v-else class="badge bg-secondary">Inactive</span>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            <i class="bi bi-x-circle"></i> Cancel
          </button>
          <button type="button" class="btn btn-danger" @click="deleteItem" :disabled="deleting">
            <i class="bi bi-trash"></i> {{ deleting ? 'Deleting...' : 'Delete Promo Code' }}
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
