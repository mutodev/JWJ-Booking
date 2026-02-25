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
            <i class="bi bi-pencil-square me-2"></i>
            Edit: {{ templateData?.name }}
          </h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body" v-if="templateData">
          <!-- Subject -->
          <div class="mb-3">
            <label for="subject" class="form-label fw-bold">
              <i class="bi bi-envelope me-1"></i> Subject
            </label>
            <input
              type="text"
              class="form-control"
              :class="{ 'border-warning border-2': activeField === 'subject' }"
              id="subject"
              v-model="subject"
              ref="subjectInput"
              required
              placeholder="Email subject..."
              @focus="activeField = 'subject'"
              @click="activeField = 'subject'"
            />
          </div>

          <!-- Available Variables -->
          <div class="card border-primary mb-3">
            <div class="card-header bg-primary bg-opacity-10 py-2">
              <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-braces fs-5 text-primary"></i>
                  <span class="fw-bold">Variables</span>
                </div>
                <span
                  class="badge"
                  :class="
                    activeField === 'subject'
                      ? 'bg-warning text-dark'
                      : 'bg-primary'
                  "
                >
                  <i
                    class="bi"
                    :class="
                      activeField === 'subject'
                        ? 'bi-envelope'
                        : 'bi-body-text'
                    "
                  ></i>
                  {{
                    activeField === "subject"
                      ? "Click to insert in Subject"
                      : "Click to insert in Body"
                  }}
                </span>
              </div>
            </div>
            <div class="card-body py-2">
              <div class="d-flex flex-wrap gap-1">
                <button
                  v-for="variable in availableVariables"
                  :key="variable"
                  type="button"
                  class="btn btn-sm btn-outline-primary variable-chip"
                  @click="insertVariable(variable)"
                  :title="'Insert in ' + activeField"
                  v-text="'{{' + variable + '}}'"
                ></button>
              </div>
              <small class="text-muted d-block mt-1">
                <i class="bi bi-info-circle"></i>
                Click Subject or Body first, then click a variable to insert it
                at the cursor.
              </small>
            </div>
          </div>

          <!-- Body: Tabs Editor / Preview -->
          <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <label class="form-label fw-bold mb-0">
                <i class="bi bi-code-slash me-1"></i> Body (HTML)
              </label>
              <ul class="nav nav-pills nav-sm">
                <li class="nav-item">
                  <button
                    class="nav-link py-1 px-3"
                    :class="{ active: viewMode === 'editor' }"
                    @click="viewMode = 'editor'"
                  >
                    <i class="bi bi-code-slash me-1"></i> Editor
                  </button>
                </li>
                <li class="nav-item">
                  <button
                    class="nav-link py-1 px-3"
                    :class="{ active: viewMode === 'preview' }"
                    @click="viewMode = 'preview'"
                  >
                    <i class="bi bi-eye me-1"></i> Preview
                  </button>
                </li>
                <li class="nav-item">
                  <button
                    class="nav-link py-1 px-3"
                    :class="{ active: viewMode === 'split' }"
                    @click="viewMode = 'split'"
                  >
                    <i class="bi bi-layout-split me-1"></i> Split
                  </button>
                </li>
              </ul>
            </div>

            <!-- Editor Only -->
            <div v-if="viewMode === 'editor'">
              <textarea
                class="form-control font-monospace source-editor"
                :class="{ 'border-primary border-2': activeField === 'body' }"
                v-model="body"
                rows="20"
                ref="bodyTextarea"
                @focus="activeField = 'body'"
                @click="activeField = 'body'"
              ></textarea>
            </div>

            <!-- Preview Only -->
            <div
              v-else-if="viewMode === 'preview'"
              class="border rounded preview-container"
            >
              <iframe
                ref="previewIframe"
                class="w-100 border-0"
                style="min-height: 500px"
                :srcdoc="body"
              ></iframe>
            </div>

            <!-- Split View -->
            <div v-else class="row g-2">
              <div class="col-6">
                <textarea
                  class="form-control font-monospace source-editor"
                  :class="{
                    'border-primary border-2': activeField === 'body',
                  }"
                  v-model="body"
                  rows="20"
                  ref="bodySplitTextarea"
                  @focus="activeField = 'body'"
                  @click="activeField = 'body'"
                ></textarea>
              </div>
              <div class="col-6">
                <div class="border rounded preview-container h-100">
                  <iframe
                    class="w-100 h-100 border-0"
                    style="min-height: 488px"
                    :srcdoc="body"
                  ></iframe>
                </div>
              </div>
            </div>
          </div>

          <!-- Active Toggle -->
          <div class="mb-3 form-check form-switch">
            <input
              type="checkbox"
              class="form-check-input"
              id="isActive"
              v-model="isActive"
            />
            <label class="form-check-label" for="isActive">Active</label>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            <i class="bi bi-arrow-90deg-down"></i> Back
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="submitForm"
            :disabled="loading"
          >
            <i class="bi bi-save"></i> Save
          </button>
        </div>
      </div>
    </div>

    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick } from "vue";
import api from "@/services/axios";

const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
  templateId: String,
});

const templateData = ref(null);
const subject = ref("");
const body = ref("");
const isActive = ref(true);
const availableVariables = ref([]);
const loading = ref(false);

const subjectInput = ref(null);
const bodyTextarea = ref(null);
const bodySplitTextarea = ref(null);

const activeField = ref("body");
const viewMode = ref("split");

watch(
  () => props.show,
  async (visible) => {
    if (visible && props.templateId) {
      activeField.value = "body";
      viewMode.value = "split";
      await fetchTemplate(props.templateId);
    }
  }
);

watch(
  () => props.templateId,
  async (newId) => {
    if (newId && props.show) {
      await fetchTemplate(newId);
    }
  }
);

const fetchTemplate = async (id) => {
  try {
    const response = await api.get(`/email-templates/${id}`);
    templateData.value = response.data;
    subject.value = response.data.subject;
    body.value = response.data.body;
    isActive.value =
      response.data.is_active === true ||
      response.data.is_active === 1 ||
      response.data.is_active === "1";

    try {
      availableVariables.value = JSON.parse(
        response.data.available_variables || "[]"
      );
    } catch {
      availableVariables.value = [];
    }
  } catch (error) {
    console.error("Error fetching template:", error);
  }
};

const insertVariable = (variable) => {
  const tag = `{{${variable}}}`;

  if (activeField.value === "subject") {
    insertAtCursor(subjectInput, subject, tag);
  } else {
    const textarea = viewMode.value === "split" ? bodySplitTextarea : bodyTextarea;
    insertAtCursor(textarea, body, tag);
  }
};

const insertAtCursor = (inputRef, modelRef, tag) => {
  const el = inputRef.value;
  if (el) {
    const start = el.selectionStart ?? modelRef.value.length;
    const end = el.selectionEnd ?? start;
    const text = modelRef.value;
    modelRef.value = text.substring(0, start) + tag + text.substring(end);
    nextTick(() => {
      const newPos = start + tag.length;
      el.selectionStart = newPos;
      el.selectionEnd = newPos;
      el.focus();
    });
  } else {
    modelRef.value += tag;
  }
};

const closeModal = () => {
  templateData.value = null;
  emit("close");
};

const submitForm = async () => {
  loading.value = true;
  try {
    await api.put(`/email-templates/${props.templateId}`, {
      subject: subject.value,
      body: body.value,
      is_active: isActive.value,
    });
    emit("saved");
    closeModal();
  } catch (error) {
    console.error("Error updating template:", error);
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.variable-chip {
  font-family: monospace;
  font-size: 0.8rem;
  transition: all 0.15s ease;
}

.variable-chip:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

.source-editor {
  font-size: 0.82rem;
  line-height: 1.5;
  tab-size: 2;
  resize: vertical;
  white-space: pre;
}

.preview-container {
  background-color: #fff;
  overflow: hidden;
}

.nav-pills .nav-link {
  font-size: 0.85rem;
  border-radius: 0.375rem;
}

.nav-pills .nav-link:not(.active) {
  color: var(--bs-body-color);
}
</style>
