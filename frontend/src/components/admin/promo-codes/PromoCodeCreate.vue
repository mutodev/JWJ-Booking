<template>
  <div
    v-if="show"
    class="admin-modal admin-form modal fade show d-block"
    tabindex="-1"
    role="dialog"
  >
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Create Promo Code</h5>
          <button
            type="button"
            class="btn-close btn-close-white"
            @click="closeModal"
          ></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="save">
            <div class="row g-3">
              <!-- Code -->
              <div class="col-md-6">
                <label class="form-label">Promo Code <span class="text-danger">*</span></label>
                <input
                  v-model="formData.code"
                  type="text"
                  class="form-control"
                  placeholder="Enter code (e.g., SUMMER2024)"
                  required
                  @input="formData.code = formData.code.toUpperCase()"
                />
                <small class="text-muted">Code will be converted to uppercase</small>
              </div>

              <!-- Discount Percentage -->
              <div class="col-md-6">
                <label class="form-label">Discount % <span class="text-danger">*</span></label>
                <input
                  v-model.number="formData.discount_percentage"
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
                  v-model="formData.valid_from"
                  type="date"
                  class="form-control"
                  required
                />
              </div>

              <!-- Valid Until -->
              <div class="col-md-6">
                <label class="form-label">Valid Until <span class="text-danger">*</span></label>
                <input
                  v-model="formData.valid_until"
                  type="date"
                  class="form-control"
                  required
                />
              </div>

              <!-- Max Uses -->
              <div class="col-md-6">
                <label class="form-label">Max Uses</label>
                <input
                  v-model.number="formData.max_uses"
                  type="number"
                  class="form-control"
                  min="0"
                  placeholder="Leave empty for unlimited"
                />
                <small class="text-muted">Leave blank for unlimited uses</small>
              </div>

              <!-- Is Active -->
              <div class="col-md-6">
                <label class="form-label d-block">Status</label>
                <div class="form-check form-switch mt-2">
                  <input
                    v-model="formData.is_active"
                    class="form-check-input"
                    type="checkbox"
                    id="isActiveCreate"
                  />
                  <label class="form-check-label" for="isActiveCreate">
                    {{ formData.is_active ? 'Active' : 'Inactive' }}
                  </label>
                </div>
              </div>

              <!-- Description -->
              <div class="col-12">
                <label class="form-label">Description</label>
                <textarea
                  v-model="formData.description"
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
          <button type="button" class="btn btn-primary" @click="save" :disabled="saving">
            <i class="bi bi-check-circle"></i>
            {{ saving ? 'Creating...' : 'Create Promo Code' }}
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
});

const formData = ref({
  code: "",
  discount_percentage: "",
  valid_from: "",
  valid_until: "",
  max_uses: null,
  is_active: true,
  description: "",
});

const saving = ref(false);

const closeModal = () => {
  emit("close");
};

const save = async () => {
  saving.value = true;
  try {
    await api.post("/promo-codes", formData.value);
    emit("saved");
    emit("close");
    resetForm();
  } catch (error) {
    console.error("Error creating promo code:", error);
  } finally {
    saving.value = false;
  }
};

const resetForm = () => {
  formData.value = {
    code: "",
    discount_percentage: "",
    valid_from: "",
    valid_until: "",
    max_uses: null,
    is_active: true,
    description: "",
  };
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    resetForm();
  }
});
</script>
