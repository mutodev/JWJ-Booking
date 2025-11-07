<template>
  <div
    v-if="show"
    class="admin-modal admin-form modal fade show d-block"
    tabindex="-1"
    role="dialog"
  >
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title">Edit Promo Code</h5>
          <button
            type="button"
            class="btn-close"
            @click="closeModal"
          ></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="save">
            <div class="row g-3">
              <!-- Code (read-only) -->
              <div class="col-md-6">
                <label class="form-label">Promo Code</label>
                <input
                  v-model="editData.code"
                  type="text"
                  class="form-control"
                  readonly
                  disabled
                />
                <small class="text-muted">Code cannot be modified</small>
              </div>

              <!-- Discount Percentage -->
              <div class="col-md-6">
                <label class="form-label">Discount % <span class="text-danger">*</span></label>
                <input
                  v-model.number="editData.discount_percentage"
                  type="number"
                  class="form-control"
                  min="1"
                  max="100"
                  step="0.01"
                  placeholder="Enter discount percentage"
                  required
                />
              </div>

              <!-- Valid From -->
              <div class="col-md-6">
                <label class="form-label">Valid From <span class="text-danger">*</span></label>
                <input
                  v-model="editData.valid_from"
                  type="date"
                  class="form-control"
                  required
                />
              </div>

              <!-- Valid Until -->
              <div class="col-md-6">
                <label class="form-label">Valid Until <span class="text-danger">*</span></label>
                <input
                  v-model="editData.valid_until"
                  type="date"
                  class="form-control"
                  required
                />
              </div>

              <!-- Max Uses -->
              <div class="col-md-6">
                <label class="form-label">Max Uses</label>
                <input
                  v-model.number="editData.max_uses"
                  type="number"
                  class="form-control"
                  min="0"
                  placeholder="Leave empty for unlimited"
                />
                <small class="text-muted">Leave blank for unlimited uses</small>
              </div>

              <!-- Usage Count (read-only) -->
              <div class="col-md-6">
                <label class="form-label">Times Used</label>
                <input
                  :value="editData.usage_count || 0"
                  type="number"
                  class="form-control"
                  readonly
                  disabled
                />
              </div>

              <!-- Is Active -->
              <div class="col-md-6">
                <label class="form-label d-block">Status</label>
                <div class="form-check form-switch mt-2">
                  <input
                    v-model="editData.is_active"
                    class="form-check-input"
                    type="checkbox"
                    id="isActiveEdit"
                  />
                  <label class="form-check-label" for="isActiveEdit">
                    {{ editData.is_active ? 'Active' : 'Inactive' }}
                  </label>
                </div>
              </div>

              <!-- Description -->
              <div class="col-12">
                <label class="form-label">Description</label>
                <textarea
                  v-model="editData.description"
                  class="form-control"
                  rows="3"
                  placeholder="Optional description or notes about this promo code"
                ></textarea>
              </div>
            </div>
          </form>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            <i class="bi bi-x-circle"></i> Cancel
          </button>
          <button type="button" class="btn btn-warning" @click="save" :disabled="saving">
            <i class="bi bi-check-circle"></i>
            {{ saving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import api from "@/services/axios";

const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
  data: {
    type: Object,
    default: () => ({}),
  },
});

const editData = ref({});
const saving = ref(false);

watch(
  () => props.data,
  (newData) => {
    editData.value = { ...newData };
    // Format dates if needed
    if (editData.value.valid_from) {
      editData.value.valid_from = formatDateForInput(editData.value.valid_from);
    }
    if (editData.value.valid_until) {
      editData.value.valid_until = formatDateForInput(editData.value.valid_until);
    }
  },
  { deep: true, immediate: true }
);

const closeModal = () => {
  emit("close");
};

const save = async () => {
  saving.value = true;
  try {
    await api.put(`/promo-codes/${editData.value.id}`, editData.value);
    emit("saved");
    emit("close");
  } catch (error) {
    console.error("Error updating promo code:", error);
  } finally {
    saving.value = false;
  }
};

const formatDateForInput = (dateString) => {
  if (!dateString) return "";
  const date = new Date(dateString);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};
</script>
