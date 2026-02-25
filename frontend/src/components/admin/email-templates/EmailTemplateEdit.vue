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
            Edit Email Template: {{ templateData?.name }}
          </h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body" v-if="templateData">
          <form @submit.prevent="submitForm">
            <!-- Subject -->
            <div class="mb-3">
              <label for="subject" class="form-label fw-bold">Subject</label>
              <input
                type="text"
                class="form-control"
                id="subject"
                v-model="subject"
                required
                placeholder="Email subject..."
              />
            </div>

            <!-- Available Variables -->
            <div class="mb-3">
              <label class="form-label fw-bold">Available Variables</label>
              <p class="text-muted small mb-2">
                Click a variable to insert it at the cursor position in the
                editor
              </p>
              <div class="d-flex flex-wrap gap-1">
                <button
                  v-for="variable in availableVariables"
                  :key="variable"
                  type="button"
                  class="btn btn-sm btn-outline-primary"
                  @click="insertVariable(variable)"
                >
                  {{ "{{" + variable + "}}" }}
                </button>
              </div>
            </div>

            <!-- Body Editor -->
            <div class="mb-3">
              <label class="form-label fw-bold">Body (HTML)</label>
              <QuillEditor
                ref="quillEditor"
                v-model:content="body"
                content-type="html"
                theme="snow"
                :toolbar="toolbarOptions"
                style="min-height: 300px"
              />
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
          </form>
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
import { ref, watch } from "vue";
import api from "@/services/axios";
import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.snow.css";

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
const quillEditor = ref(null);

const toolbarOptions = [
  ["bold", "italic", "underline", "strike"],
  [{ header: [1, 2, 3, false] }],
  [{ color: [] }, { background: [] }],
  [{ align: [] }],
  ["link", "image"],
  [{ list: "ordered" }, { list: "bullet" }],
  ["blockquote", "code-block"],
  ["clean"],
];

// Watch for templateId changes to fetch fresh data
watch(
  () => props.templateId,
  async (newId) => {
    if (newId && props.show) {
      await fetchTemplate(newId);
    }
  }
);

watch(
  () => props.show,
  async (visible) => {
    if (visible && props.templateId) {
      await fetchTemplate(props.templateId);
    }
  }
);

const fetchTemplate = async (id) => {
  try {
    const response = await api.get(`/email-templates/${id}`);
    templateData.value = response.data;
    subject.value = response.data.subject;
    body.value = response.data.body;
    isActive.value = response.data.is_active;

    // Parse available variables from JSON string
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

  // Try to insert at Quill cursor position
  if (quillEditor.value) {
    const quill = quillEditor.value.getQuill();
    const range = quill.getSelection();
    if (range) {
      quill.insertText(range.index, tag);
      quill.setSelection(range.index + tag.length);
      return;
    }
  }

  // Fallback: append to subject
  subject.value += tag;
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
:deep(.ql-container) {
  min-height: 250px;
  font-size: 14px;
}

:deep(.ql-editor) {
  min-height: 250px;
}

:deep(.ql-toolbar) {
  border-radius: 0.375rem 0.375rem 0 0;
}

:deep(.ql-container) {
  border-radius: 0 0 0.375rem 0.375rem;
}
</style>
