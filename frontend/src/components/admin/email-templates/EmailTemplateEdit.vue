<template>
  <div
    v-if="show"
    class="admin-modal modal fade show d-block"
    tabindex="-1"
    role="dialog"
  >
    <div class="modal-dialog modal-fullscreen-lg-down modal-xl" role="document">
      <div class="modal-content h-100">

        <!-- Header -->
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-0">
              <i class="bi bi-pencil-square me-2 text-primary"></i>
              {{ templateData?.name }}
            </h5>
            <p class="text-muted small mb-0 mt-1">
              Edit the text of this email. The design and layout are preserved automatically.
            </p>
          </div>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <!-- Body -->
        <div class="modal-body p-0" v-if="templateData">
          <div class="d-flex h-100" style="min-height: 70vh;">

            <!-- ── LEFT PANEL: Edit Fields ── -->
            <div class="edit-panel border-end overflow-auto p-4" style="width: 45%; min-width: 340px;">

              <!-- Subject -->
              <div
                class="field-card mb-3"
                :class="{ 'field-card--active': activeField === 'subject' }"
              >
                <div class="field-icon-label">
                  <span class="field-icon bg-primary-subtle text-primary">
                    <i class="bi bi-envelope-fill"></i>
                  </span>
                  <div>
                    <div class="field-label">Subject Line</div>
                    <div class="field-hint">What the recipient sees in their inbox before opening</div>
                  </div>
                </div>
                <input
                  type="text"
                  class="form-control mt-2"
                  v-model="subject"
                  ref="subjectInput"
                  placeholder="Enter subject..."
                  @focus="activeField = 'subject'"
                  @click="activeField = 'subject'"
                />
                <div v-if="activeField === 'subject' && availableVariables.length" class="var-chips mt-2">
                  <span class="var-chips-label">Insert:</span>
                  <button
                    v-for="v in availableVariables"
                    :key="v"
                    class="var-chip"
                    @click="insertIntoSubject(v)"
                    :title="'Insert ' + getVarLabel(v)"
                  >
                    {{ getVarLabel(v) }}
                  </button>
                </div>
              </div>

              <!-- Content Fields -->
              <div
                v-for="field in contentSchema"
                :key="field.key"
                class="field-card mb-3"
                :class="{ 'field-card--active': activeField === field.key }"
              >
                <div class="field-icon-label">
                  <span class="field-icon" :class="field.iconBg + ' ' + field.iconColor">
                    <i :class="field.icon"></i>
                  </span>
                  <div>
                    <div class="field-label">{{ field.label }}</div>
                    <div class="field-hint">{{ field.hint }}</div>
                  </div>
                </div>

                <!-- Rich text editor for multiline fields -->
                <QuillEditor
                  v-if="field.multiline"
                  :ref="el => { if (el) quillRefs[field.key] = el }"
                  v-model:content="content[field.key]"
                  contentType="html"
                  theme="snow"
                  :options="QUILL_OPTIONS"
                  @focus="activeField = field.key"
                  @selection-change="(range) => { if (range) quillLastSelection[field.key] = range }"
                  class="quill-editor-field mt-2"
                />

                <!-- Plain input for single-line fields -->
                <input
                  v-else
                  type="text"
                  class="form-control mt-2"
                  v-model="content[field.key]"
                  @focus="activeField = field.key"
                  @click="activeField = field.key"
                  :ref="el => { if (el) fieldRefs[field.key] = el }"
                  :placeholder="field.placeholder || ''"
                />

                <!-- Variable chips per field -->
                <div v-if="activeField === field.key && availableVariables.length" class="var-chips mt-2">
                  <span class="var-chips-label">Insert dynamic value:</span>
                  <button
                    v-for="v in availableVariables"
                    :key="v"
                    class="var-chip"
                    @mousedown.prevent="insertIntoField(field.key, v, field.multiline)"
                    :title="'Inserts the actual ' + getVarLabel(v) + ' when the email is sent'"
                  >
                    {{ getVarLabel(v) }}
                  </button>
                </div>
              </div>

              <!-- Active toggle -->
              <div class="field-card mb-3">
                <div class="d-flex align-items-center gap-3">
                  <div class="form-check form-switch mb-0">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="isActive"
                      v-model="isActive"
                      role="switch"
                    />
                    <label class="form-check-label fw-semibold" for="isActive">
                      Template enabled
                    </label>
                  </div>
                  <span class="text-muted small">Disable to stop sending this email type</span>
                </div>
              </div>
            </div>

            <!-- ── RIGHT PANEL: Live Preview ── -->
            <div class="preview-panel d-flex flex-column flex-grow-1 overflow-auto bg-light">
              <div class="preview-topbar px-4 py-2 bg-white border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-eye-fill text-success"></i>
                <span class="fw-semibold small">Live Preview</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">
                  Updates as you type
                </span>
                <div class="ms-auto text-muted small">
                  <i class="bi bi-info-circle me-1"></i>
                  Dynamic values like customer name will appear at send time
                </div>
              </div>

              <div class="preview-subject px-4 py-2 bg-white border-bottom">
                <span class="text-muted small me-2">Subject:</span>
                <strong class="small">{{ subject || '(no subject)' }}</strong>
              </div>

              <div class="flex-grow-1 p-3">
                <iframe
                  :srcdoc="previewBody"
                  class="w-100 h-100 border-0 rounded shadow-sm"
                  style="min-height: 500px; background: white;"
                ></iframe>
              </div>
            </div>

          </div>
        </div>

        <!-- Error message -->
        <div v-if="errorMsg" class="alert alert-danger mx-3 mb-0 py-2 small">
          <i class="bi bi-exclamation-circle me-1"></i>{{ errorMsg }}
        </div>

        <!-- Footer -->
        <div class="modal-footer border-top">
          <button type="button" class="btn btn-light px-4" @click="closeModal">
            Cancel
          </button>
          <button
            type="button"
            class="btn btn-primary px-4"
            @click="submitForm"
            :disabled="loading"
          >
            <span v-if="loading">
              <span class="spinner-border spinner-border-sm me-2"></span>Saving...
            </span>
            <span v-else>
              <i class="bi bi-check-lg me-1"></i> Save Changes
            </span>
          </button>
        </div>
      </div>
    </div>

    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from "vue";
import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.snow.css";
import api from "@/services/axios";

const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
  templateId: String,
});

// ── Quill configuration ────────────────────────────────────────────────────
const QUILL_OPTIONS = {
  modules: {
    toolbar: [
      ["bold", "italic", "underline"],
      [{ list: "ordered" }, { list: "bullet" }],
      ["link"],
      ["clean"],
    ],
  },
};

// ── Content schemas per template slug ──────────────────────────────────────
const CONTENT_SCHEMAS = {
  payment_notification: [
    {
      key: "greeting_title",
      label: "Opening Greeting",
      hint: "The main title shown at the top of the email",
      icon: "bi bi-chat-quote-fill",
      iconBg: "bg-primary-subtle",
      iconColor: "text-primary",
      multiline: false,
      placeholder: "e.g. Hi {{customer_name}}!",
    },
    {
      key: "intro",
      label: "Main Message",
      hint: "First paragraph the customer reads",
      icon: "bi bi-paragraph",
      iconBg: "bg-info-subtle",
      iconColor: "text-info",
      multiline: true,
      placeholder: "Write the opening message...",
    },
    {
      key: "button_text",
      label: "Button Text",
      hint: "Label on the call-to-action button",
      icon: "bi bi-hand-index-fill",
      iconBg: "bg-success-subtle",
      iconColor: "text-success",
      multiline: false,
      placeholder: "e.g. Continue to Pay",
    },
    {
      key: "steps_heading",
      label: "Steps Section Title",
      hint: "Heading above the numbered steps",
      icon: "bi bi-list-ol",
      iconBg: "bg-success-subtle",
      iconColor: "text-success",
      multiline: false,
      placeholder: "e.g. Next Steps",
    },
    {
      key: "step1",
      label: "Step 1",
      hint: "First step for the customer",
      icon: "bi bi-1-circle-fill",
      iconBg: "bg-success-subtle",
      iconColor: "text-success",
      multiline: false,
      placeholder: "e.g. Complete Event Details — Click the button above...",
    },
    {
      key: "step2",
      label: "Step 2",
      hint: "Second step for the customer",
      icon: "bi bi-2-circle-fill",
      iconBg: "bg-success-subtle",
      iconColor: "text-success",
      multiline: false,
      placeholder: "e.g. Proceed to Payment — secure payment page...",
    },
    {
      key: "important_note",
      label: "Important Note",
      hint: "Message shown in the highlighted yellow box",
      icon: "bi bi-exclamation-circle-fill",
      iconBg: "bg-warning-subtle",
      iconColor: "text-warning",
      multiline: true,
      placeholder: "Write a note for the customer...",
    },
  ],
  reservation_confirmation: [
    {
      key: "title",
      label: "Email Title",
      hint: "Main heading at the top of the email",
      icon: "bi bi-type-h1",
      iconBg: "bg-primary-subtle",
      iconColor: "text-primary",
      multiline: false,
      placeholder: "e.g. Reservation Received!",
    },
    {
      key: "intro",
      label: "Introduction",
      hint: "Opening paragraph welcoming the customer",
      icon: "bi bi-paragraph",
      iconBg: "bg-info-subtle",
      iconColor: "text-info",
      multiline: true,
      placeholder: "Write the opening message...",
    },
    {
      key: "steps_heading",
      label: "Steps Section Title",
      hint: "Heading above the numbered steps",
      icon: "bi bi-list-ol",
      iconBg: "bg-success-subtle",
      iconColor: "text-success",
      multiline: false,
      placeholder: "e.g. What's Next?",
    },
    {
      key: "step1",
      label: "Step 1",
      hint: "First step in the process",
      icon: "bi bi-1-circle-fill",
      iconBg: "bg-success-subtle",
      iconColor: "text-success",
      multiline: false,
      placeholder: "e.g. Our team reviews your reservation — We'll confirm availability",
    },
    {
      key: "step2",
      label: "Step 2",
      hint: "Second step in the process",
      icon: "bi bi-2-circle-fill",
      iconBg: "bg-success-subtle",
      iconColor: "text-success",
      multiline: false,
      placeholder: "e.g. You'll receive a payment link — via email",
    },
    {
      key: "step3",
      label: "Step 3",
      hint: "Third step in the process",
      icon: "bi bi-3-circle-fill",
      iconBg: "bg-success-subtle",
      iconColor: "text-success",
      multiline: false,
      placeholder: "e.g. Get ready to party! — your event is fully booked",
    },
    {
      key: "question_note",
      label: "Help Message",
      hint: "Message shown in the yellow box at the bottom",
      icon: "bi bi-question-circle-fill",
      iconBg: "bg-warning-subtle",
      iconColor: "text-warning",
      multiline: true,
      placeholder: "Write a helpful message...",
    },
  ],
  welcome: [
    {
      key: "title",
      label: "Email Title",
      hint: "Main heading at the top of the welcome email",
      icon: "bi bi-type-h1",
      iconBg: "bg-primary-subtle",
      iconColor: "text-primary",
      multiline: false,
      placeholder: "e.g. Welcome aboard!",
    },
    {
      key: "intro",
      label: "Welcome Message",
      hint: "Paragraph shown below the title",
      icon: "bi bi-paragraph",
      iconBg: "bg-info-subtle",
      iconColor: "text-info",
      multiline: true,
      placeholder: "Write a welcome message...",
    },
    {
      key: "security_reminder",
      label: "Security Reminder",
      hint: "Warning message reminding the user to change their password",
      icon: "bi bi-shield-fill-exclamation",
      iconBg: "bg-danger-subtle",
      iconColor: "text-danger",
      multiline: true,
      placeholder: "Write a security reminder...",
    },
  ],
  reset_password: [
    {
      key: "title",
      label: "Email Title",
      hint: "Main heading at the top of the email",
      icon: "bi bi-type-h1",
      iconBg: "bg-primary-subtle",
      iconColor: "text-primary",
      multiline: false,
      placeholder: "e.g. Password Reset",
    },
    {
      key: "intro",
      label: "Main Message",
      hint: "Explanation shown before the new password",
      icon: "bi bi-paragraph",
      iconBg: "bg-info-subtle",
      iconColor: "text-info",
      multiline: true,
      placeholder: "Write the main message...",
    },
    {
      key: "security_reminder",
      label: "Security Reminder",
      hint: "Warning message shown after the temporary password",
      icon: "bi bi-shield-fill-exclamation",
      iconBg: "bg-danger-subtle",
      iconColor: "text-danger",
      multiline: true,
      placeholder: "Write a security reminder...",
    },
  ],
  payment_confirmation: [
    {
      key: "title",
      label: "Email Title",
      hint: "Main heading shown at the top of the email",
      icon: "bi bi-type-h1",
      iconBg: "bg-primary-subtle",
      iconColor: "text-primary",
      multiline: false,
      placeholder: "e.g. Payment Confirmed!",
    },
    {
      key: "intro",
      label: "Introduction",
      hint: "Opening paragraph confirming payment and celebrating the booking",
      icon: "bi bi-paragraph",
      iconBg: "bg-info-subtle",
      iconColor: "text-info",
      multiline: true,
      placeholder: "e.g. Hi {{customer_name}}, your payment has been received...",
    },
    {
      key: "closing_note",
      label: "Closing Note",
      hint: "Message shown in the yellow box at the bottom",
      icon: "bi bi-chat-heart-fill",
      iconBg: "bg-warning-subtle",
      iconColor: "text-warning",
      multiline: true,
      placeholder: "e.g. If you have any questions before your event, feel free to reply...",
    },
  ],
};

// ── Friendly labels for dynamic variables ──────────────────────────────────
const VAR_LABELS = {
  customer_name:       "Customer Name",
  reservation_id:      "Reservation #",
  service_name:        "Service",
  event_date:          "Event Date",
  event_time:          "Event Time",
  event_address:       "Location",
  children_count:      "No. of Children",
  performers_names:    "Performer Names",
  event_contact_name:  "Event Contact",
  event_contact_phone: "Contact Phone",
  performer_venmo_handles: "Performer Venmo",
  birthday_child_name: "Birthday Child",
  total_amount:        "Total Amount",
  description:         "Description",
  confirmation_url:    "Payment Link",
  password:            "Password",
};

const getVarLabel = (key) => VAR_LABELS[key] || key.replace(/_/g, " ");

const RESERVATION_MESSAGE_TEMPLATE_SLUGS = new Set([
  "payment_needed_secure_event",
  "reservation_cancelled_no_payment",
  "thank_you_for_jamming",
  "availability_confirmed_next_steps",
  "not_available_for_event",
  "week_reminder",
]);

const MESSAGE_SCHEMA = [
  {
    key: "message",
    label: "Message",
    hint: "Edit the email content used when sending from reservations",
    icon: "bi bi-pencil",
    iconBg: "bg-primary-subtle",
    iconColor: "text-primary",
    multiline: true,
    rows: 8,
  },
];

// ── State ──────────────────────────────────────────────────────────────────
const templateData       = ref(null);
const subject            = ref("");
const body               = ref("");
const content            = ref({});
const isActive           = ref(true);
const availableVariables = ref([]);
const loading            = ref(false);

const subjectInput      = ref(null);
const fieldRefs         = ref({});
const quillRefs         = ref({});
const quillLastSelection = ref({});
const activeField       = ref("subject");
const successMsg        = ref("");
const errorMsg          = ref("");

// ── Computed ───────────────────────────────────────────────────────────────
const contentSchema = computed(() => {
  const slug = templateData.value?.slug;
  return getContentSchema(slug, content.value, body.value);
});

// Replace {{content_*}} placeholders with current values for live preview
const previewBody = computed(() => {
  if (isReservationMessageTemplate(templateData.value?.slug, content.value)) {
    return wrapReservationMessagePreview(content.value.message || "");
  }

  let html = body.value;
  for (const field of contentSchema.value) {
    const val = content.value[field.key] ?? "";
    // Multiline fields use Quill (HTML) — output as-is
    // Single-line fields are plain text — convert newlines for safety
    const displayed = field.multiline ? val : val.replace(/\n/g, "<br>");
    html = html.replaceAll(`{{content_${field.key}}}`, displayed);
  }
  return html;
});

// ── Watchers ───────────────────────────────────────────────────────────────
watch(
  [() => props.show, () => props.templateId],
  async ([visible, id]) => {
    if (visible && id) {
      activeField.value        = "subject";
      fieldRefs.value          = {};
      quillRefs.value          = {};
      quillLastSelection.value = {};
      await fetchTemplate(id);
    }
  }
);

// ── Methods ────────────────────────────────────────────────────────────────
const fetchTemplate = async (id) => {
  try {
    errorMsg.value   = "";
    successMsg.value = "";
    const response = await api.get(`/email-templates/${id}`);
    const data = response.data;

    subject.value  = data.subject ?? "";
    body.value     = data.body ?? "";
    isActive.value = data.is_active === true || data.is_active === 1 || data.is_active === "1";

    try {
      availableVariables.value = JSON.parse(data.available_variables || "[]");
    } catch {
      availableVariables.value = [];
    }

    let savedContent = {};
    try {
      savedContent = JSON.parse(data.content || "{}");
    } catch {
      savedContent = {};
    }

    const slug   = data.slug;
    const schema = getContentSchema(slug, savedContent, body.value);
    const built  = {};
    for (const field of schema) {
      built[field.key] = savedContent[field.key] ?? "";
    }
    content.value = built;

    templateData.value = data;
  } catch (error) {
    errorMsg.value = "Error loading template";
    console.error("Error fetching template:", error);
  }
};

const isReservationMessageTemplate = (slug, savedContent = {}) => {
  return RESERVATION_MESSAGE_TEMPLATE_SLUGS.has(slug) || Object.prototype.hasOwnProperty.call(savedContent, "message");
};

const getContentSchema = (slug, savedContent = {}, html = "") => {
  if (isReservationMessageTemplate(slug, savedContent)) {
    return MESSAGE_SCHEMA;
  }

  return CONTENT_SCHEMAS[slug] || extractSchemaFromBody(html);
};

const wrapReservationMessagePreview = (message) => {
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
              ${message}
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

// Fallback: extract {{content_*}} keys from body HTML for unknown slugs
const extractSchemaFromBody = (html) => {
  const pattern = /\{\{content_([a-z_]+)\}\}/g;
  const result  = [];
  const seen    = new Set();
  let match;
  while ((match = pattern.exec(html)) !== null) {
    const key = match[1];
    if (!seen.has(key)) {
      seen.add(key);
      result.push({
        key,
        label:     key.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase()),
        hint:      "",
        icon:      "bi bi-pencil",
        iconBg:    "bg-secondary-subtle",
        iconColor: "text-secondary",
        multiline: ["intro", "message", "important_note", "question_note", "security_reminder", "closing_note"].includes(key),
        rows:      3,
      });
    }
  }
  return result;
};

const insertIntoSubject = (variable) => {
  const tag = `{{${variable}}}`;
  const el  = subjectInput.value;
  if (el) {
    const start = el.selectionStart ?? subject.value.length;
    const end   = el.selectionEnd ?? start;
    subject.value = subject.value.substring(0, start) + tag + subject.value.substring(end);
    nextTick(() => {
      const pos = start + tag.length;
      el.selectionStart = pos;
      el.selectionEnd   = pos;
      el.focus();
    });
  } else {
    subject.value += tag;
  }
};

const insertIntoField = (key, variable, isMultiline) => {
  const tag = `{{${variable}}}`;

  if (isMultiline) {
    // Insert into Quill editor using saved selection position
    const quillComponent = quillRefs.value[key];
    if (quillComponent) {
      const quill = quillComponent.getQuill();
      const saved = quillLastSelection.value[key];
      const index = saved?.index ?? (quill.getLength() - 1);
      quill.insertText(index, tag, "user");
      quill.setSelection(index + tag.length);
      quill.focus();
    } else {
      content.value[key] = (content.value[key] || "") + tag;
    }
  } else {
    // Insert into plain input at cursor position
    const el = fieldRefs.value[key];
    if (el) {
      const start = el.selectionStart ?? (content.value[key]?.length ?? 0);
      const end   = el.selectionEnd ?? start;
      const text  = content.value[key] || "";
      content.value[key] = text.substring(0, start) + tag + text.substring(end);
      nextTick(() => {
        const pos = start + tag.length;
        el.selectionStart = pos;
        el.selectionEnd   = pos;
        el.focus();
      });
    } else {
      content.value[key] = (content.value[key] || "") + tag;
    }
  }
};

const closeModal = () => {
  templateData.value       = null;
  subject.value            = "";
  body.value               = "";
  content.value            = {};
  isActive.value           = true;
  availableVariables.value = [];
  fieldRefs.value          = {};
  quillRefs.value          = {};
  quillLastSelection.value = {};
  activeField.value        = "subject";
  successMsg.value         = "";
  errorMsg.value           = "";
  emit("close");
};

const submitForm = async () => {
  loading.value    = true;
  errorMsg.value   = "";
  successMsg.value = "";
  try {
    await api.put(`/email-templates/${props.templateId}`, {
      subject:   subject.value,
      content:   JSON.stringify(content.value),
      is_active: isActive.value,
    });
    emit("saved");
    closeModal();
  } catch (error) {
    errorMsg.value = error.response?.data?.message || "An error occurred while saving";
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
/* ── Layout ── */
.edit-panel {
  flex-shrink: 0;
}

.preview-panel {
  flex: 1;
}

.preview-topbar,
.preview-subject {
  flex-shrink: 0;
}

/* ── Field Cards ── */
.field-card {
  background: #fff;
  border: 1.5px solid #e5e7eb;
  border-radius: 10px;
  padding: 14px 16px;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.field-card--active {
  border-color: #0d6efd;
  box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.field-icon-label {
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.field-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  font-size: 0.95rem;
  flex-shrink: 0;
  margin-top: 1px;
}

.field-label {
  font-weight: 600;
  font-size: 0.92rem;
  color: #1f2937;
  line-height: 1.3;
}

.field-hint {
  font-size: 0.78rem;
  color: #6b7280;
  margin-top: 1px;
}

/* ── Variable Chips ── */
.var-chips {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 5px;
}

.var-chips-label {
  font-size: 0.75rem;
  color: #6b7280;
  flex-shrink: 0;
}

.var-chip {
  background: #f0f7ff;
  border: 1px solid #bfdbfe;
  color: #1d4ed8;
  border-radius: 20px;
  padding: 2px 10px;
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.12s;
  white-space: nowrap;
}

.var-chip:hover {
  background: #dbeafe;
  border-color: #93c5fd;
}

/* ── Preview ── */
.preview-topbar {
  font-size: 0.85rem;
}

.preview-subject {
  font-size: 0.85rem;
}

/* ── Quill Rich Text Editor ── */
:deep(.quill-editor-field .ql-toolbar.ql-snow) {
  border-color: #dee2e6;
  border-radius: 6px 6px 0 0;
  padding: 6px 8px;
  background: #f8f9fa;
  font-family: inherit;
}

:deep(.quill-editor-field .ql-container.ql-snow) {
  border-color: #dee2e6;
  border-radius: 0 0 6px 6px;
  font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
  font-size: 0.875rem;
}

:deep(.quill-editor-field .ql-editor) {
  min-height: 100px;
  padding: 8px 12px;
  color: #212529;
  line-height: 1.6;
}

:deep(.quill-editor-field .ql-editor.ql-blank::before) {
  font-style: normal;
  color: #9ca3af;
  font-size: 0.875rem;
}

:deep(.quill-editor-field .ql-editor p) {
  margin-bottom: 0.4em;
}

/* Active state border for Quill */
.field-card--active :deep(.ql-toolbar.ql-snow),
.field-card--active :deep(.ql-container.ql-snow) {
  border-color: #0d6efd;
}

/* Quill toolbar button styling */
:deep(.ql-toolbar .ql-stroke) {
  stroke: #495057;
}

:deep(.ql-toolbar .ql-fill) {
  fill: #495057;
}

:deep(.ql-toolbar button:hover .ql-stroke),
:deep(.ql-toolbar button.ql-active .ql-stroke) {
  stroke: #0d6efd;
}

:deep(.ql-toolbar button:hover .ql-fill),
:deep(.ql-toolbar button.ql-active .ql-fill) {
  fill: #0d6efd;
}

:deep(.ql-toolbar .ql-picker-label) {
  color: #495057;
}
</style>
