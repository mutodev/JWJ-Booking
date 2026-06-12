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

                <textarea
                  v-if="field.multiline"
                  class="form-control mt-2"
                  :rows="field.rows || 3"
                  v-model="content[field.key]"
                  @focus="activeField = field.key"
                  @click="activeField = field.key"
                  :ref="el => { if (el) fieldRefs[field.key] = el }"
                  :placeholder="field.placeholder || ''"
                ></textarea>
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
                    @click="insertIntoField(field.key, v)"
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
import api from "@/services/axios";

const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
  templateId: String,
});

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
      rows: 3,
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
      rows: 2,
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
      rows: 3,
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
      rows: 2,
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
      rows: 3,
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
      rows: 2,
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
      rows: 3,
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
      rows: 2,
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
      rows: 3,
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
      rows: 2,
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
  birthday_child_name: "Birthday Child",
  total_amount:        "Total Amount",
  description:         "Description",
  confirmation_url:    "Payment Link",
  password:            "Password",
};

const getVarLabel = (key) => VAR_LABELS[key] || key.replace(/_/g, " ");

// ── State ──────────────────────────────────────────────────────────────────
const templateData       = ref(null);
const subject            = ref("");
const body               = ref("");
const content            = ref({});
const isActive           = ref(true);
const availableVariables = ref([]);
const loading            = ref(false);

const subjectInput = ref(null);
const fieldRefs    = ref({});
const activeField  = ref("subject");
const successMsg   = ref("");
const errorMsg     = ref("");

// ── Computed ───────────────────────────────────────────────────────────────
const contentSchema = computed(() => {
  const slug = templateData.value?.slug;
  return CONTENT_SCHEMAS[slug] || extractSchemaFromBody(body.value);
});

// Replace {{content_*}} placeholders with current values for live preview
const previewBody = computed(() => {
  let html = body.value;
  for (const [key, val] of Object.entries(content.value)) {
    const displayed = (val ?? "").replace(/\n/g, "<br>");
    html = html.replaceAll(`{{content_${key}}}`, displayed);
  }
  return html;
});

// ── Watchers ───────────────────────────────────────────────────────────────
watch(
  [() => props.show, () => props.templateId],
  async ([visible, id]) => {
    if (visible && id) {
      activeField.value = "subject";
      fieldRefs.value   = {};
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

    // Load saved content; fall back to empty strings for missing keys
    let savedContent = {};
    try {
      savedContent = JSON.parse(data.content || "{}");
    } catch {
      savedContent = {};
    }

    const slug   = data.slug;
    const schema = CONTENT_SCHEMAS[slug] || extractSchemaFromBody(body.value);
    const built  = {};
    for (const field of schema) {
      built[field.key] = savedContent[field.key] ?? "";
    }
    content.value = built;

    // Set templateData LAST so v-if="templateData" shows the form only when everything is ready
    templateData.value = data;
  } catch (error) {
    errorMsg.value = "Error loading template";
    console.error("Error fetching template:", error);
  }
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
        multiline: ["intro", "important_note", "question_note", "security_reminder"].includes(key),
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

const insertIntoField = (key, variable) => {
  const tag = `{{${variable}}}`;
  const el  = fieldRefs.value[key];
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
};

const closeModal = () => {
  templateData.value       = null;
  subject.value            = "";
  body.value               = "";
  content.value            = {};
  isActive.value           = true;
  availableVariables.value = [];
  fieldRefs.value          = {};
  activeField.value        = "subject";
  successMsg.value         = "";
  errorMsg.value           = "";
  emit("close");
};

const submitForm = async () => {
  loading.value  = true;
  errorMsg.value = "";
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
</style>
