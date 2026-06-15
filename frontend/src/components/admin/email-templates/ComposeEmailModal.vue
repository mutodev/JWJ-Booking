<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog" style="z-index: 1055;">
    <div class="modal-dialog modal-xl" role="document" style="z-index: 1056;">
      <div class="modal-content">

        <!-- Header -->
        <div class="modal-header">
          <h5 class="modal-title fw-bold mb-0">
            <i class="bi bi-envelope-plus me-2 text-primary"></i>
            Compose Email
          </h5>
          <button type="button" class="btn-close" @click="$emit('close')"></button>
        </div>

        <!-- Body -->
        <div class="modal-body p-4">

          <!-- Subject -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
            <input
              v-model="subject"
              type="text"
              class="form-control"
              placeholder="Enter email subject..."
            />
          </div>

          <!-- Recipients -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Recipients <span class="text-danger">*</span></label>

            <!-- All Customers toggle -->
            <div class="mb-2 d-flex align-items-center gap-3">
              <button
                class="btn btn-sm"
                :class="sendToAll ? 'btn-primary' : 'btn-outline-primary'"
                :disabled="!!lockedRecipient"
                @click="toggleAllCustomers"
              >
                <i class="bi bi-people-fill me-1"></i>
                All Customers
                <i v-if="sendToAll" class="bi bi-check-lg ms-1"></i>
              </button>
              <span class="text-muted small">Send to every customer in the system</span>
            </div>

            <!-- Search (hidden when sendToAll or locked) -->
            <template v-if="!sendToAll && !lockedRecipient">
              <el-select
                v-model="searchModel"
                filterable
                remote
                :remote-method="searchCustomers"
                :loading="searching"
                placeholder="Search by name or email..."
                style="width: 100%"
                no-match-text="No customers found"
                value-key="id"
                @change="addRecipient"
                clearable
              >
                <el-option
                  v-for="c in searchResults"
                  :key="c.id"
                  :label="`${c.full_name} — ${c.email}`"
                  :value="c"
                  :disabled="isAlreadySelected(c.id)"
                />
              </el-select>
            </template>

            <!-- Selected chips -->
            <div class="mt-2">
              <template v-if="selectedRecipients.length">
                <span
                  v-for="r in selectedRecipients"
                  :key="r.id"
                  class="badge bg-primary me-1 mb-1 recipient-chip d-inline-flex align-items-center gap-1"
                  style="font-size: 0.85rem; padding: 6px 10px;"
                >
                  <i class="bi bi-person-fill"></i>
                  {{ r.full_name }}
                  <button
                    v-if="!lockedRecipient"
                    class="btn-close btn-close-white ms-1"
                    style="font-size: 0.6rem;"
                    @click="removeRecipient(r.id)"
                  ></button>
                </span>
              </template>
              <span v-else-if="!sendToAll" class="text-muted small">No recipients selected yet.</span>
              <span v-else class="badge bg-info text-dark" style="font-size:0.85rem;padding:6px 10px;">
                <i class="bi bi-people-fill me-1"></i> All Customers
              </span>
            </div>
          </div>

          <!-- Variable chips -->
          <div class="mb-3">
            <label class="form-label fw-semibold small text-muted">Insert dynamic value:</label>
            <div>
              <button
                v-for="v in VARIABLES"
                :key="v.key"
                class="btn btn-sm btn-outline-secondary me-1 mb-1"
                @mousedown.prevent="insertVariable(v.key)"
              >
                {{ v.label }}
              </button>
            </div>
          </div>

          <!-- Quill editor -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Content <span class="text-danger">*</span></label>
            <div class="quill-wrapper">
              <QuillEditor
                ref="quillRef"
                v-model:content="content"
                contentType="html"
                theme="snow"
                :options="QUILL_OPTIONS"
                style="min-height: 220px;"
              />
            </div>
          </div>

        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="$emit('close')">Cancel</button>
          <button
            class="btn btn-primary"
            @click="send"
            :disabled="sending || !canSend"
          >
            <span v-if="sending" class="spinner-border spinner-border-sm me-2" role="status"></span>
            {{ sending ? 'Sending...' : 'Send Email' }}
          </button>
        </div>

      </div>
    </div>
    <div class="modal-backdrop fade show" style="z-index: 1054;" @click="$emit('close')"></div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import api from '@/services/axios';
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps({
  show: { type: Boolean, default: false },
  // When opened from Reservations table — pre-selects and locks this customer
  lockedRecipient: { type: Object, default: null },
});

const emit = defineEmits(['close']);

// ── State ─────────────────────────────────────────────────────────────────
const subject = ref('');
const content = ref('');
const sendToAll = ref(false);
const selectedRecipients = ref([]);
const searchModel = ref(null);
const searchResults = ref([]);
const searching = ref(false);
const sending = ref(false);
const quillRef = ref(null);

// ── Quill ──────────────────────────────────────────────────────────────────
const QUILL_OPTIONS = {
  modules: {
    toolbar: [
      ['bold', 'italic', 'underline'],
      [{ list: 'ordered' }, { list: 'bullet' }],
      ['link'],
      ['clean'],
    ],
  },
};

// ── Variables ──────────────────────────────────────────────────────────────
const VARIABLES = [
  { key: 'customer_name', label: 'Customer Name' },
  { key: 'event_date',    label: 'Event Date' },
  { key: 'event_time',    label: 'Event Time' },
  { key: 'event_address', label: 'Event Address' },
  { key: 'service_name',  label: 'Service' },
  { key: 'total_amount',  label: 'Total Amount' },
];

function insertVariable(key) {
  const editor = quillRef.value?.getQuill?.();
  if (!editor) return;
  const range = editor.getSelection(true);
  editor.insertText(range?.index ?? 0, `{{${key}}}`);
}

// ── Recipients ─────────────────────────────────────────────────────────────
function toggleAllCustomers() {
  sendToAll.value = !sendToAll.value;
  if (sendToAll.value) selectedRecipients.value = [];
}

async function searchCustomers(query) {
  if (!query || query.length < 2) { searchResults.value = []; return; }
  searching.value = true;
  try {
    const res = await api.get(`/customers/search/${encodeURIComponent(query)}`);
    searchResults.value = Array.isArray(res.data) ? res.data : (res.data?.data ?? []);
  } catch {
    searchResults.value = [];
  } finally {
    searching.value = false;
  }
}

function addRecipient(customer) {
  if (!customer || isAlreadySelected(customer.id)) return;
  sendToAll.value = false;
  selectedRecipients.value.push(customer);
  searchModel.value = null;
  searchResults.value = [];
}

function removeRecipient(id) {
  selectedRecipients.value = selectedRecipients.value.filter(r => r.id !== id);
}

function isAlreadySelected(id) {
  return selectedRecipients.value.some(r => r.id === id);
}

// ── Computed ───────────────────────────────────────────────────────────────
const canSend = computed(() => {
  const hasRecipients = sendToAll.value || selectedRecipients.value.length > 0;
  return subject.value.trim() && content.value.trim() && hasRecipients;
});

// ── Send ───────────────────────────────────────────────────────────────────
async function send() {
  if (!canSend.value) return;
  sending.value = true;
  try {
    const payload = {
      subject: subject.value,
      html_content: content.value,
      send_to_all: sendToAll.value,
      recipient_ids: selectedRecipients.value.map(r => r.id),
    };
    const res = await api.post('/email-templates/send', payload);
    const sent = res.data?.sent ?? res.sent ?? '?';
    toast.success(`Email sent to ${sent} recipient(s)!`);
    emit('close');
    reset();
  } catch (err) {
    toast.error(err?.response?.data?.message ?? 'Failed to send email');
  } finally {
    sending.value = false;
  }
}

// ── Reset & watch ──────────────────────────────────────────────────────────
function reset() {
  subject.value = '';
  content.value = '';
  sendToAll.value = false;
  selectedRecipients.value = [];
  searchModel.value = null;
  searchResults.value = [];
}

watch(() => props.show, (val) => {
  if (val) {
    reset();
    if (props.lockedRecipient) {
      selectedRecipients.value = [props.lockedRecipient];
    }
  }
});

watch(() => props.lockedRecipient, (val) => {
  if (val && props.show) {
    selectedRecipients.value = [val];
  }
});
</script>

<style scoped>
.quill-wrapper :deep(.ql-container) {
  min-height: 180px;
  font-size: 15px;
}
.quill-wrapper :deep(.ql-toolbar.ql-snow) {
  border-radius: 6px 6px 0 0;
  border-color: #dee2e6;
}
.quill-wrapper :deep(.ql-container.ql-snow) {
  border-radius: 0 0 6px 6px;
  border-color: #dee2e6;
}
.recipient-chip {
  font-weight: 500;
  letter-spacing: 0.01em;
}
</style>
