<template>
  <div v-if="show" class="modal fade show d-block admin-modal" tabindex="-1" style="background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-danger">
          <h5 class="modal-title">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ title }}
          </h5>
          <button type="button" class="btn-close btn-close-white" @click="$emit('cancel')"></button>
        </div>
        <div class="modal-body">
          <div class="modal-delete-warning">
            <i class="bi bi-shield-exclamation"></i>
            <p v-html="message"></p>
          </div>
          <div class="form-check mt-3">
            <input
              class="form-check-input"
              type="checkbox"
              id="confirmCheck"
              v-model="confirmed"
            />
            <label class="form-check-label" for="confirmCheck">
              I understand this action <strong>cannot be undone</strong>
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="$emit('cancel')">Cancel</button>
          <button
            type="button"
            class="btn btn-danger"
            :disabled="!confirmed"
            @click="handleConfirm"
          >
            <i class="bi bi-trash3 me-1"></i>{{ confirmLabel }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";

const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: "Confirm Delete" },
  message: { type: String, default: "This action cannot be undone." },
  confirmLabel: { type: String, default: "Delete" },
});

const emit = defineEmits(["confirm", "cancel"]);

const confirmed = ref(false);

watch(() => props.show, (val) => {
  if (!val) confirmed.value = false;
});

function handleConfirm() {
  if (!confirmed.value) return;
  emit("confirm");
  confirmed.value = false;
}
</script>
