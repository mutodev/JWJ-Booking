<template>
  <div
    v-if="show"
    class="admin-modal modal fade show d-block"
    tabindex="-1"
    role="dialog"
  >
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            Preview: {{ previewSubject || "Loading..." }}
          </h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>

          <div v-else-if="previewBody">
            <div class="mb-3">
              <strong>Subject:</strong>
              <span class="ms-2">{{ previewSubject }}</span>
            </div>
            <hr />
            <div
              class="email-preview-container border rounded p-3"
              v-html="previewBody"
            ></div>
          </div>

          <div v-else class="text-center text-muted py-5">
            No preview available
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            <i class="bi bi-x-lg"></i> Close
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

const emit = defineEmits(["close"]);
const props = defineProps({
  show: Boolean,
  templateId: String,
});

const previewSubject = ref("");
const previewBody = ref("");
const loading = ref(false);

// Sample data for preview
const sampleVariables = {
  customer_name: "Jane Smith",
  reservation_id: "RES-SAMPLE-12345",
  service_name: "JamWithJamie Classic Party",
  event_date: "March 15, 2026",
  event_time: "2:00 PM",
  event_address: "123 Party Lane, Los Angeles, CA 90001",
  children_count: "12",
  birthday_child_name: "",
  total_amount: "350.00",
  description: "",
  confirmation_url: "#",
  password: "TempP@ss123",
};

watch(
  () => props.show,
  async (visible) => {
    if (visible && props.templateId) {
      await fetchPreview();
    }
  }
);

const fetchPreview = async () => {
  loading.value = true;
  try {
    const response = await api.post("/email-templates/preview", {
      id: props.templateId,
      variables: sampleVariables,
    });
    previewSubject.value = response.data.subject;
    previewBody.value = response.data.body;
  } catch (error) {
    console.error("Error fetching preview:", error);
  } finally {
    loading.value = false;
  }
};

const closeModal = () => {
  previewSubject.value = "";
  previewBody.value = "";
  emit("close");
};
</script>

<style scoped>
.email-preview-container {
  background-color: #f9fafb;
  max-height: 600px;
  overflow-y: auto;
}
</style>
