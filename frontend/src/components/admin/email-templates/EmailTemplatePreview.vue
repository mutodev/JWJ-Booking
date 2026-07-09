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
  event_time: "1:45 PM",
  event_address: "123 Party Lane, Los Angeles, CA 90001",
  children_count: "12",
  birthday_child_name: "",
  total_amount: "350.00",
  description: "",
  entertainment_start_time: "1:45 PM",
  performers_count: "2",
  performers_names: "Jamie and Alex",
  event_contact_name: "Jamie Team",
  event_contact_phone: "(555) 123-4567",
  performer_venmo_handles: "@jamie-music, @alex-music",
  entertainment_start_time_row: '<tr><td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; background-color: #f9fafb; width: 40%; border-bottom: 1px solid #e5e7eb;">Entertainment Start Time</td><td style="padding: 12px 16px; font-size: 14px; color: #1F2937; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">1:45 PM</td></tr>',
  performers_row: '<tr><td style="padding: 12px 16px; font-size: 14px; font-weight: 600; color: #6b7280; width: 40%; border-bottom: 1px solid #e5e7eb;">Performer(s)</td><td style="padding: 12px 16px; font-size: 14px; color: #1F2937; border-bottom: 1px solid #e5e7eb;">2</td></tr>',
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
    const response = await api.post("/email-templates/compose-preview", {
      id: props.templateId,
      variables: sampleVariables,
    });
    const rendered = response.data?.data ?? response.data ?? response;
    previewSubject.value = rendered.subject;
    previewBody.value = rendered.is_full_html
      ? rendered.body
      : wrapContent(rendered.body);
  } catch (error) {
    console.error("Error fetching preview:", error);
  } finally {
    loading.value = false;
  }
};

const wrapContent = (content) => {
  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f9fafb;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;-webkit-font-smoothing:antialiased;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9fafb;padding:40px 20px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.06);">
          <tr>
            <td style="background-color:#ffffff;padding:32px 40px;text-align:center;border-bottom:3px solid #FF74B7;">
              <img src="/img/logos/JWJ_logo-05.png" alt="Jam with Jamie" width="220" style="display:inline-block;max-width:220px;height:auto;">
            </td>
          </tr>
          <tr>
            <td style="padding:40px 40px 32px;font-size:15px;line-height:1.7;color:#374151;">
              ${content}
            </td>
          </tr>
          <tr>
            <td style="background-color:#f9fafb;padding:24px 40px;border-top:1px solid #e5e7eb;text-align:center;">
              <p style="margin:0 0 4px;font-size:14px;font-weight:600;color:#FF74B7;">The Jam with Jamie Team</p>
              <p style="margin:0;font-size:12px;color:#9ca3af;">&copy; ${new Date().getFullYear()} Jam with Jamie LLC. All rights reserved.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>`;
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
