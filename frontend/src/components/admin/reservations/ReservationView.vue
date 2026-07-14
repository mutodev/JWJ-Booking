<template>
  <div v-if="show" class="admin-modal admin-form modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <div class="d-flex align-items-center">
            <i class="bi bi-calendar-event fs-4 me-3"></i>
            <div>
              <h5 class="modal-title mb-0">Reservation Details</h5>
              <small class="text-muted">ID: {{ data.id || 'N/A' }}</small>
            </div>
          </div>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <ul class="nav nav-tabs reservation-tabs mb-4">
            <li class="nav-item">
              <button
                class="nav-link"
                :class="{ active: activeTab === 'details' }"
                type="button"
                @click="activeTab = 'details'"
              >
                Reservation Details
              </button>
            </li>
            <li class="nav-item">
              <button
                class="nav-link"
                :class="{ active: activeTab === 'history' }"
                type="button"
                @click="openHistoryTab"
              >
                Email History
              </button>
            </li>
          </ul>

          <div v-if="activeTab === 'details'">
            <div class="alert alert-light border mb-4">
              <div class="row align-items-center">
                <div class="col-md-8">
                  <div class="d-flex align-items-center">
                    <i class="bi" :class="getStatusIcon()" style="font-size: 1.5rem; margin-right: 12px;"></i>
                    <div>
                      <h6 class="mb-1 fw-bold">Status: {{ getStatusLabel(data.status) }}</h6>
                      <div class="d-flex gap-3">
                        <span class="badge" :class="data.is_paid ? 'bg-success' : 'bg-danger'">
                          <i class="bi bi-credit-card me-1"></i>
                          {{ data.is_paid ? 'PAID' : 'UNPAID' }}
                        </span>
                        <span class="badge" :class="data.is_invoiced ? 'bg-info' : 'bg-secondary'">
                          <i class="bi bi-receipt me-1"></i>
                          {{ data.is_invoiced ? 'INVOICED' : 'NOT INVOICED' }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-4 text-end">
                  <div class="total-amount">
                    <small class="text-muted d-block">Total Amount</small>
                    <h4 class="mb-0 fw-bold">{{ formatCurrency(totalWithGratuity) }}</h4>
                  </div>
                </div>
              </div>
            </div>

            <div class="segment mb-3">
              <h6 class="segment-title">Basic Information</h6>
              <div class="row g-3">
                <DetailField label="Event Address" :value="data.event_address" class="col-md-6" />
                <DetailField label="Event Date" :value="formatDate(data.event_date)" class="col-md-3" />
                <DetailField label="Event Start Time" :value="data.event_time" class="col-md-3" />
                <DetailField label="Entertainment Start Time" :value="data.entertainment_start_time" class="col-md-6" />
              </div>
            </div>

            <div class="segment mb-3">
              <h6 class="segment-title">Event Details</h6>
              <div class="row g-3">
                <DetailField label="Total Children" :value="childrenValue" class="col-md-3" />
                <DetailField label="Age Range" :value="data.children_age_range" class="col-md-3" />
                <DetailField label="Performers" :value="data.performers_count" class="col-md-3" />
                <DetailField label="Duration (hours)" :value="data.duration_hours" class="col-md-3" />
                <DetailField label="Birthday Child's Name" :value="data.birthday_child_name" class="col-md-4" />
                <DetailField label="Age They Are Turning" :value="data.birthday_child_age" class="col-md-4" />
                <DetailField label="Happy Birthday Song?" :value="happyBirthdayLabel" class="col-md-4" />
              </div>
            </div>

            <div class="segment mb-3">
              <h6 class="segment-title">Status & Payments</h6>
              <div class="row g-3">
                <DetailField label="Status" :value="getStatusLabel(data.status)" class="col-md-4" />
                <DetailField label="Payment Received" :value="data.is_paid ? 'Yes' : 'No'" class="col-md-4" />
                <DetailField label="Invoice Sent" :value="data.is_invoiced ? 'Yes' : 'No'" class="col-md-4" />
              </div>
            </div>

            <div class="segment mb-3">
              <h6 class="segment-title">Notes & Instructions</h6>
              <div class="row g-3">
                <DetailField label="Arrival/Parking Instructions" :value="data.arrival_parking_instructions" class="col-md-6" multiline />
                <DetailField label="Song Requests" :value="data.song_requests" class="col-md-6" multiline />
                <DetailField label="Customer Notes" :value="data.customer_notes || data.additional_notes" class="col-md-6" multiline />
                <DetailField label="Internal Notes" :value="data.internal_notes" class="col-md-6" multiline />
              </div>
            </div>

            <div class="segment mb-3">
              <h6 class="segment-title">Promo Code</h6>
              <div class="row g-3">
                <DetailField label="Promo Code" :value="data.promo_code" class="col-md-3" />
                <DetailField label="Discount" :value="formatCurrency(data.discount_amount)" class="col-md-3" />
                <DetailField label="Total Amount" :value="formatCurrency(totalWithGratuity)" class="col-md-3" />
                <DetailField label="Gratuity / Tip" :value="formatCurrency(data.gratuity_amount)" class="col-md-3" />
              </div>
            </div>

            <div class="segment mb-3">
              <h6 class="segment-title">Read-Only Information</h6>
              <div class="row g-3">
                <DetailField label="Client" :value="data.customer_name || data.full_name" class="col-md-4" />
                <DetailField label="Email" :value="data.email" class="col-md-4" />
                <DetailField label="Service" :value="data.service_name" class="col-md-4" />
                <DetailField label="Location" :value="locationLabel" class="col-md-4" />
                <DetailField label="County" :value="data.county_name" class="col-md-4" />
                <DetailField label="Zip Code" :value="data.zipcode" class="col-md-4" />
                <DetailField label="Base Service" :value="formatCurrency(data.base_price)" class="col-md-3" />
                <DetailField label="Add-ons" :value="formatCurrency(data.addons_total)" class="col-md-3" />
                <DetailField label="Number of Children" :value="formatCurrency(data.extra_children_fee)" class="col-md-3" />
                <DetailField v-if="displayTravelFee > 0" label="Travel Fee" :value="formatCurrency(displayTravelFee)" class="col-md-3" />
                <DetailField v-if="displayExpediteFee > 0" label="Expedite Fee" :value="formatCurrency(displayExpediteFee)" class="col-md-3" />
              </div>
            </div>
          </div>

          <div v-else>
            <div class="history-toolbar mb-3">
              <div>
                <h6 class="mb-1 fw-bold">Email History</h6>
                <small class="text-muted">Emails sent from this reservation.</small>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary" @click="loadEmailHistory" :disabled="historyLoading">
                <span v-if="historyLoading" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-arrow-clockwise me-1"></i>
                Refresh
              </button>
            </div>

            <EasyDataTable
              :headers="historyHeaders"
              :items="emailHistory"
              :loading="historyLoading"
              table-class-name="customize-table"
              :rows-per-page="10"
            >
              <template #item-sent_at="{ sent_at }">
                {{ formatDateTime(sent_at) }}
              </template>
              <template #item-status="{ status }">
                <span class="badge" :class="status === 'Sent' ? 'bg-success' : 'bg-danger'">{{ status }}</span>
              </template>
              <template #item-actions="item">
                <button class="btn btn-sm btn-outline-primary" type="button" @click="viewHistoryItem(item)">
                  <i class="bi bi-eye me-1"></i>
                  View
                </button>
              </template>
            </EasyDataTable>
          </div>
        </div>

        <div class="modal-footer">
          <div class="d-flex justify-content-between align-items-center w-100">
            <small class="text-muted">
              <i class="bi bi-calendar-plus me-1"></i>
              Created: {{ formatDate(data.created_at) }}
              <span class="mx-2">-</span>
              <i class="bi bi-pencil me-1"></i>
              Updated: {{ formatDate(data.updated_at) }}
            </small>
            <div>
              <button type="button" class="btn btn-secondary me-2" @click="closeModal">
                <i class="bi bi-x-lg me-1"></i> Close
              </button>
              <button v-if="canUpdate" type="button" class="btn btn-primary" @click="editReservation">
                <i class="bi bi-pencil me-1"></i> Edit Reservation
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show"></div>

    <div v-if="selectedHistory" class="modal fade show d-block history-detail-modal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-envelope-open me-2"></i>Email Sent</h5>
            <button type="button" class="btn-close" @click="selectedHistory = null"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3 mb-3">
              <DetailField label="Template" :value="selectedHistory.template_name" class="col-md-6" />
              <DetailField label="Subject" :value="selectedHistory.email_subject" class="col-md-6" />
              <DetailField label="Recipient" :value="selectedHistory.recipient_email" class="col-md-6" />
              <DetailField label="Sent By" :value="selectedHistory.sent_by" class="col-md-6" />
              <DetailField label="Date Sent" :value="formatDateTime(selectedHistory.sent_at)" class="col-md-6" />
              <DetailField label="Status" :value="selectedHistory.status" class="col-md-6" />
            </div>
            <label class="form-label">Body</label>
            <iframe class="history-body-frame" :srcdoc="selectedHistory.email_body"></iframe>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="selectedHistory = null">Close</button>
          </div>
        </div>
      </div>
      <div class="modal-backdrop fade show"></div>
    </div>
  </div>
</template>

<script setup>
import { computed, defineComponent, h, ref, watch } from "vue";
import api from "@/services/axios";
import { useMenuPermissions } from "@/composables/useMenuPermissions";

const data = ref({});
const activeTab = ref("details");
const emailHistory = ref([]);
const historyLoading = ref(false);
const historyLoaded = ref(false);
const selectedHistory = ref(null);
const { canUpdate } = useMenuPermissions("/admin/reservations");

const emit = defineEmits(["close", "saved", "edit"]);
const props = defineProps({
  show: Boolean,
  data: {
    type: Object,
    default: () => ({}),
  },
});

const DetailField = defineComponent({
  props: {
    label: { type: String, required: true },
    value: { type: [String, Number, Boolean], default: "" },
    multiline: { type: Boolean, default: false },
  },
  setup(fieldProps, { attrs }) {
    return () => h("div", attrs, [
      h("label", { class: "form-label" }, fieldProps.label),
      h(
        "div",
        { class: ["detail-value", fieldProps.multiline ? "detail-value-multiline" : ""] },
        fieldProps.value === null || fieldProps.value === undefined || fieldProps.value === "" ? "N/A" : String(fieldProps.value)
      ),
    ]);
  },
});

const historyHeaders = [
  { text: "Date Sent", value: "sent_at" },
  { text: "Template", value: "template_name" },
  { text: "Sent By", value: "sent_by" },
  { text: "Recipient", value: "recipient_email" },
  { text: "Status", value: "status" },
  { text: "Actions", value: "actions" },
];

const statusLabels = {
  new: "New",
  checking_availability: "Checking Availability",
  availability_confirmed: "Availability Confirmed",
  follow_up: "Follow-up",
  ready_for_payment_link: "Ready for Payment Link",
  payment_link_sent: "Payment Link Sent",
  payment_reminder: "Payment Reminder",
  booked: "Booked",
  get_ready_to_jam: "Get Ready to Jam",
  thank_you_for_jamming: "Thank you for jamming",
  cancelled: "Cancelled",
  under_review: "Under Review",
  confirmed: "Confirmed",
};

watch(
  () => props.data,
  (newData) => {
    data.value = { ...newData };
  },
  { deep: true, immediate: true }
);

watch(
  () => props.show,
  (visible) => {
    if (visible) {
      activeTab.value = "details";
      selectedHistory.value = null;
      historyLoaded.value = false;
      emailHistory.value = [];
    }
  }
);

const totalWithGratuity = computed(() => (
  (parseFloat(data.value.total_amount) || 0) + (parseFloat(data.value.gratuity_amount) || 0)
));

const childrenValue = computed(() => {
  const count = data.value.children_count ?? 0;
  return `${count} children`;
});

const happyBirthdayLabel = computed(() => {
  if (data.value.sing_happy_birthday === true || data.value.sing_happy_birthday === 1 || data.value.sing_happy_birthday === "1") {
    return "Yes";
  }

  return "No";
});

const locationLabel = computed(() => {
  const cityCounty = `${data.value.city_name || ""}, ${data.value.county_name || ""}`.replace(/^, |, $/g, "");
  return cityCounty || data.value.location || data.value.zipcode || "N/A";
});

const displayTravelFee = computed(() => {
  const travelFee = parseFloat(data.value.travel_fee ?? 0) || 0;
  const expediteFee = parseFloat(data.value.expedite_fee ?? 0) || 0;
  const legacyFee = parseFloat(data.value.expedition_fee ?? 0) || 0;

  if (travelFee > 0) return travelFee;
  if (legacyFee > 0 && expediteFee === 0) return legacyFee;
  return 0;
});

const displayExpediteFee = computed(() => parseFloat(data.value.expedite_fee ?? 0) || 0);

const closeModal = () => {
  emit("close");
};

const openHistoryTab = async () => {
  activeTab.value = "history";
  if (!historyLoaded.value) {
    await loadEmailHistory();
  }
};

const loadEmailHistory = async () => {
  if (!data.value.id) return;

  historyLoading.value = true;
  try {
    const response = await api.get(`/reservations/${data.value.id}/email-history`);
    emailHistory.value = response.data?.data ?? response.data ?? [];
    historyLoaded.value = true;
  } catch {
    emailHistory.value = [];
  } finally {
    historyLoading.value = false;
  }
};

const viewHistoryItem = (item) => {
  selectedHistory.value = item;
};

const formatDate = (date) => {
  if (!date) return "N/A";
  const raw = date?.date ?? date;
  const d = new Date(raw);
  if (Number.isNaN(d.getTime())) return "N/A";

  return d.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

const formatDateTime = (date) => {
  if (!date) return "N/A";
  const raw = date?.date ?? date;
  const d = new Date(raw);
  if (Number.isNaN(d.getTime())) return "N/A";

  return d.toLocaleString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

const formatCurrency = (amount) => {
  const value = parseFloat(amount) || 0;
  return value.toLocaleString("en-US", { style: "currency", currency: "USD" });
};

const getStatusLabel = (status) => statusLabels[status] || status || "UNKNOWN";

const getStatusIcon = () => {
  const status = data.value.status;
  switch (status) {
    case "new":
    case "checking_availability":
    case "follow_up":
      return "bi-hourglass-split";
    case "availability_confirmed":
    case "booked":
    case "thank_you_for_jamming":
      return "bi-check-circle-fill";
    case "cancelled":
      return "bi-x-circle-fill";
    default:
      return "bi-question-circle";
  }
};

const editReservation = () => {
  emit("edit", data.value);
};
</script>

<style scoped>
.total-amount {
  text-align: right;
}

.reservation-tabs .nav-link {
  color: #4b5563;
  font-weight: 700;
}

.reservation-tabs .nav-link.active {
  color: #111827;
}

.segment {
  padding: 18px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
}

.segment-title {
  margin-bottom: 14px;
  color: #374151;
  font-size: 0.9rem;
  font-weight: 700;
}

.form-label {
  display: block;
  margin-bottom: 6px;
  color: #4b5563;
  font-size: 0.84rem;
  font-weight: 700;
}

.detail-value {
  min-height: 42px;
  padding: 9px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: #f9fafb;
  color: #111827;
  font-size: 0.92rem;
}

.detail-value-multiline {
  min-height: 92px;
  white-space: pre-wrap;
}

.history-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.history-detail-modal {
  background: rgba(17, 24, 39, 0.35);
  z-index: 1060;
}

.history-detail-modal .modal-backdrop {
  z-index: -1;
}

.history-body-frame {
  width: 100%;
  min-height: 520px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
}

@media (max-width: 768px) {
  .modal-dialog {
    margin: 0;
    max-width: 100%;
    height: 100vh;
  }

  .modal-content {
    height: 100vh;
    border-radius: 0;
  }

  .total-amount {
    text-align: center;
    margin-top: 1rem;
  }

  .history-toolbar {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
