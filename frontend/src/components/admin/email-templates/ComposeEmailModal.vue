<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog" style="z-index: 1055;">
    <div class="modal-dialog modal-fullscreen-lg-down modal-xl" role="document" style="z-index: 1056;">
      <div class="modal-content h-100">

        <!-- Header -->
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-0">
              <i class="bi bi-envelope-plus me-2 text-primary"></i>
              {{ reservation ? (selectedTemplateName || 'Compose Email') : 'Compose Email' }}
            </h5>
            <p v-if="reservation" class="text-muted small mb-0 mt-1">
              Edit the text of this email. The design and layout are preserved automatically.
            </p>
          </div>
          <button type="button" class="btn-close" @click="$emit('close')"></button>
        </div>

        <!-- Reservation template editor -->
        <div v-if="reservation" class="modal-body p-0">
          <div class="d-flex h-100" style="min-height: 70vh;">
            <div class="edit-panel border-end overflow-auto p-4" style="width: 45%; min-width: 340px;">
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
                  ref="subjectInput"
                  v-model="subject"
                  type="text"
                  class="form-control mt-2"
                  placeholder="Enter subject..."
                  @focus="activeField = 'subject'"
                  @click="activeField = 'subject'"
                />
                <div v-if="activeField === 'subject'" class="var-chips mt-2">
                  <span class="var-chips-label">Insert:</span>
                  <button
                    v-for="v in VARIABLES"
                    :key="v.key"
                    class="var-chip"
                    type="button"
                    @click="insertIntoSubject(v.key)"
                  >
                    {{ v.label }}
                  </button>
                </div>
              </div>

              <div
                class="field-card mb-3"
                :class="{ 'field-card--active': activeField === 'message' }"
              >
                <div class="field-icon-label">
                  <span class="field-icon bg-primary-subtle text-primary">
                    <i class="bi bi-pencil"></i>
                  </span>
                  <div>
                    <div class="field-label">Message</div>
                    <div class="field-hint">Edit the email content for this reservation</div>
                  </div>
                </div>
                <QuillEditor
                  ref="quillRef"
                  v-model:content="content"
                  contentType="html"
                  theme="snow"
                  :options="QUILL_OPTIONS"
                  class="quill-editor-field mt-2"
                  @focus="activeField = 'message'"
                  @selection-change="(range) => { if (range) quillLastSelection = range }"
                />
                <div v-if="activeField === 'message'" class="var-chips mt-2">
                  <span class="var-chips-label">Insert dynamic value:</span>
                  <button
                    v-for="v in VARIABLES"
                    :key="v.key"
                    class="var-chip"
                    type="button"
                    @mousedown.prevent="insertVariable(v.key)"
                  >
                    {{ v.label }}
                  </button>
                </div>
              </div>

              <div class="field-card mb-3">
                <div class="field-icon-label">
                  <span class="field-icon bg-success-subtle text-success">
                    <i class="bi bi-person-fill"></i>
                  </span>
                  <div>
                    <div class="field-label">Recipient</div>
                    <div class="field-hint">{{ lockedRecipient?.full_name || 'Customer' }} &lt;{{ lockedRecipient?.email || 'No email' }}&gt;</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="preview-panel d-flex flex-column flex-grow-1 overflow-auto bg-light">
              <div class="preview-topbar px-4 py-2 bg-white border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-eye-fill text-success"></i>
                <span class="fw-semibold small">Live Preview</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">
                  Updates as you type
                </span>
                <div class="ms-auto text-muted small">
                  <i class="bi bi-info-circle me-1"></i>
                  Dynamic values appear when the email is sent
                </div>
              </div>
              <div class="preview-subject px-4 py-2 bg-white border-bottom">
                <span class="text-muted small me-2">Subject:</span>
                <strong class="small">{{ subject || '(no subject)' }}</strong>
              </div>
              <div class="flex-grow-1 p-3">
                <iframe
                  :srcdoc="reservationPreviewBody"
                  class="w-100 h-100 border-0 rounded shadow-sm"
                  style="min-height: 500px; background: white;"
                ></iframe>
              </div>
            </div>
          </div>
        </div>

        <!-- Default compose body -->
        <div v-else class="modal-body p-4">

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
        <div class="modal-footer border-top">
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
import { ref, computed, watch, nextTick } from 'vue';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import api from '@/services/axios';
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps({
  show: { type: Boolean, default: false },
  // When opened from Reservations table — pre-selects and locks this customer
  lockedRecipient: { type: Object, default: null },
  reservation: { type: Object, default: null },
  templateId: { type: String, default: '' },
  initialSubject: { type: String, default: '' },
  initialContent: { type: String, default: '' },
  initialIsFullHtml: { type: Boolean, default: false },
  selectedTemplateName: { type: String, default: '' },
});

const emit = defineEmits(['close', 'sent']);

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
const isFullHtml = ref(false);
const subjectInput = ref(null);
const activeField = ref('subject');
const quillLastSelection = ref(null);

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
  { key: 'reservation_id', label: 'Reservation ID' },
  { key: 'event_date',    label: 'Event Date' },
  { key: 'event_time',    label: 'Entertainment Start Time' },
  { key: 'entertainment_start_time', label: 'Entertainment Start Time' },
  { key: 'event_address', label: 'Event Address' },
  { key: 'service_name',  label: 'Service' },
  { key: 'performers_count', label: 'Performers' },
  { key: 'performers_names', label: 'Performer Names' },
  { key: 'event_contact_name', label: 'Event Contact' },
  { key: 'event_contact_phone', label: 'Contact Phone' },
  { key: 'performer_venmo_handles', label: 'Performer Venmo' },
  { key: 'payment_url',   label: 'Payment URL' },
  { key: 'total_amount',  label: 'Total Amount' },
];

function insertVariable(key) {
  const editor = quillRef.value?.getQuill?.();
  if (!editor) return;
  const range = quillLastSelection.value || editor.getSelection(true);
  const index = range?.index ?? Math.max(editor.getLength() - 1, 0);
  editor.insertText(index, `{{${key}}}`, 'user');
  editor.setSelection(index + key.length + 4);
  editor.focus();
}

function insertIntoSubject(key) {
  const tag = `{{${key}}}`;
  const el = subjectInput.value;
  if (!el) {
    subject.value += tag;
    return;
  }

  const start = el.selectionStart ?? subject.value.length;
  const end = el.selectionEnd ?? start;
  subject.value = subject.value.substring(0, start) + tag + subject.value.substring(end);
  nextTick(() => {
    const pos = start + tag.length;
    el.selectionStart = pos;
    el.selectionEnd = pos;
    el.focus();
  });
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
function formatDate(value) {
  if (!value) return '';
  const dateValue = value?.date ?? value;
  const date = dateValue instanceof Date ? dateValue : new Date(dateValue);
  return Number.isNaN(date.getTime()) ? String(dateValue) : date.toLocaleDateString('en-US');
}

function formatCurrency(value) {
  const amount = Number(value || 0);
  return amount.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
}

function reservationVariables() {
  const reservation = props.reservation || {};
  const customerName = reservation.full_name || reservation.customer_name || props.lockedRecipient?.full_name || '';

  return {
    customer_name: customerName,
    reservation_id: reservation.id || '',
    service_name: reservation.service_name || '',
    event_date: formatDate(reservation.event_date),
    event_time: reservation.entertainment_start_time || reservation.event_time || '',
    entertainment_start_time: reservation.entertainment_start_time || '',
    event_address: reservation.event_address || '',
    children_count: reservation.children_count ?? '',
    performers_count: reservation.performers_count ?? '',
    performers_names: '',
    event_contact_name: '',
    event_contact_phone: '',
    performer_venmo_handles: '',
    total_amount: formatCurrency(reservation.total_amount),
    confirmation_url: reservation.confirmation_url || '',
    payment_url: reservation.payment_url || reservation.payment_link || '',
  };
}

function replaceReservationPlaceholders(value) {
  if (!props.reservation) return value;
  const variables = reservationVariables();
  return String(value || '').replace(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g, (match, key) => {
    return Object.prototype.hasOwnProperty.call(variables, key) ? variables[key] : match;
  });
}

function normalizeEditableContent(html) {
  const wrapper = document.createElement('div');
  wrapper.innerHTML = html || '';

  wrapper.querySelectorAll('table').forEach((table) => {
    const lines = Array.from(table.querySelectorAll('tr'))
      .map((row) => {
        const cells = Array.from(row.querySelectorAll('td, th')).map((cell) => {
          const link = cell.querySelector('a[href]');
          if (link) {
            const href = link.getAttribute('href') || '';
            const label = link.textContent.trim();
            return label ? `<a href="${href}">${label}</a>` : '';
          }

          return cell.textContent.trim();
        });
        if (cells.length >= 2) {
          return `<p><strong>${cells[0]}</strong>: ${cells.slice(1).join(' ')}</p>`;
        }
        return cells[0] ? `<p>${cells[0]}</p>` : '';
      })
      .filter(Boolean)
      .join('');

    const replacement = document.createElement('div');
    replacement.innerHTML = lines;
    table.replaceWith(...Array.from(replacement.childNodes));
  });

  wrapper.querySelectorAll('[style], [class], [role], [width], [cellpadding], [cellspacing], [align], [valign]').forEach((node) => {
    ['style', 'class', 'role', 'width', 'cellpadding', 'cellspacing', 'align', 'valign'].forEach((attr) => {
      node.removeAttribute(attr);
    });
  });

  return wrapper.innerHTML;
}

const canSend = computed(() => {
  const hasRecipients = sendToAll.value || selectedRecipients.value.length > 0;
  const hasRequiredTemplate = !props.reservation || props.templateId;
  return subject.value.trim() && content.value.trim() && hasRecipients && hasRequiredTemplate;
});

const reservationPreviewBody = computed(() => {
  if (isFullHtml.value) {
    return replaceReservationPlaceholders(content.value);
  }

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
              <img src="/img/logos/JWJ_logo-05.png" alt="Jam with Jamie" width="300" style="display:inline-block;max-width:300px;height:auto;">
            </td>
          </tr>
          <tr>
            <td style="padding:40px 40px 32px;font-size:15px;line-height:1.7;color:#374151;">
              ${content.value || ''}
            </td>
          </tr>
          <tr>
            <td style="padding:24px 40px 32px;border-top:1px solid #f0f0f0;">
              <p style="margin:0 0 4px;font-size:14px;color:#1F2937;">Best regards,</p>
              <p style="margin:0;font-size:14px;font-weight:600;color:#FF74B7;">The Jam with Jamie Team</p>
            </td>
          </tr>
          <tr>
            <td style="background-color:#FF74B7;padding:16px 40px;text-align:center;">
              <p style="margin:0;font-size:12px;color:rgba(0,0,0,0.6);">&copy; ${new Date().getFullYear()} Jam with Jamie LLC. All rights reserved.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>`;
});

// ── Send ───────────────────────────────────────────────────────────────────
async function send() {
  if (!canSend.value) return;
  sending.value = true;
  try {
    if (props.reservation) {
      await api.post('/reservations/send-template-email', {
        reservation_id: props.reservation.id,
        template_id: props.templateId,
        subject: replaceReservationPlaceholders(subject.value),
        body: replaceReservationPlaceholders(content.value),
        is_full_html: isFullHtml.value,
      });
      toast.success('Email sent successfully');
    } else {
      const payload = {
        subject: subject.value,
        html_content: content.value,
        send_to_all: sendToAll.value,
        recipient_ids: selectedRecipients.value.map(r => r.id),
        is_full_html: isFullHtml.value,
      };
      const res = await api.post('/email-templates/send', payload);
      const sent = res.data?.sent ?? res.sent ?? '?';
      toast.success(`Email sent to ${sent} recipient(s)!`);
    }
    emit('sent');
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
  isFullHtml.value = false;
  activeField.value = 'subject';
  quillLastSelection.value = null;
}

watch(() => props.show, (val) => {
  if (val) {
    reset();
    if (props.reservation) {
      subject.value = props.initialSubject;
      content.value = props.initialContent;
      isFullHtml.value = props.initialIsFullHtml;
    }
    if (props.lockedRecipient) {
      selectedRecipients.value = [props.lockedRecipient];
    }
  }
});

watch(() => [props.initialSubject, props.initialContent, props.initialIsFullHtml], () => {
  if (props.show && props.reservation) {
    subject.value = props.initialSubject;
    content.value = props.initialContent;
    isFullHtml.value = props.initialIsFullHtml;
  }
});

watch(() => props.lockedRecipient, (val) => {
  if (val && props.show) {
    selectedRecipients.value = [val];
  }
});
</script>

<style scoped>
.edit-panel {
  flex-shrink: 0;
}

.preview-panel {
  flex: 1;
}

.preview-topbar,
.preview-subject {
  flex-shrink: 0;
  font-size: 0.85rem;
}

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

.field-card--active :deep(.ql-toolbar.ql-snow),
.field-card--active :deep(.ql-container.ql-snow) {
  border-color: #0d6efd;
}

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
  min-height: 360px;
  padding: 8px 12px;
  color: #212529;
  line-height: 1.6;
}

:deep(.quill-editor-field .ql-editor.ql-blank::before) {
  font-style: normal;
  color: #9ca3af;
  font-size: 0.875rem;
}

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
