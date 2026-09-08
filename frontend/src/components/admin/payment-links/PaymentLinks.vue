<template>
  <div class="row justify-content-end">
    <!-- Search -->
    <div class="col">
      <div class="input-group">
        <span class="input-group-text">
          <i class="bi bi-search"></i>
        </span>
        <input
          v-model="searchValue"
          type="text"
          class="form-control"
          placeholder="Search by description, customer..."
        />
      </div>
    </div>

    <!-- Actions -->
    <div class="col-md-auto pt-1 d-flex gap-2 justify-content-end">
      <button class="btn btn-sm btn-primary" @click="getData()">
        <i class="bi bi-arrow-clockwise"></i>
        Refresh
      </button>
      <button
        v-if="canCreate"
        class="btn btn-sm btn-primary"
        @click="createModal()"
      >
        <i class="bi bi-plus-lg"></i>
        New Payment Link
      </button>
    </div>
  </div>

  <!-- Table -->
  <div class="row mt-3">
    <div class="col-md-12">
      <EasyDataTable
        :headers="headers"
        :items="data"
        :search-field="searchField"
        :search-value="searchValue"
        table-class-name="table table-hover"
        header-text-direction="center"
        body-text-direction="center"
        :rows-per-page="10"
        :rows-per-page-options="[5, 10, 25, 50]"
        show-index
        index-column-text="#"
      >
        <!-- Status -->
        <template #item-status="{ status }">
          <span class="badge" :class="statusClass(status)">
            {{ statusLabel(status) }}
          </span>
        </template>

        <!-- Amount -->
        <template #item-amount="{ amount, currency }">
          <span class="fw-bold">{{ formatAmount(amount) }}</span>
          <small
            v-if="currency && currency.toLowerCase() !== 'usd'"
            class="text-muted ms-1"
          >{{ currency.toUpperCase() }}</small>
        </template>

        <!-- Description -->
        <template #item-description="{ description }">
          <span>{{ description }}</span>
        </template>

        <!-- Customer -->
        <template #item-customer_email="{ customer_email, customer_name }">
          <div>{{ customer_email }}</div>
          <small v-if="customer_name" class="text-muted">{{ customer_name }}</small>
        </template>

        <!-- Created -->
        <template #item-created_at="{ created_at }">
          {{ formatDateTime(created_at) }}
        </template>

        <!-- Actions -->
        <template #item-actions="item">
          <div class="d-flex gap-1 justify-content-center flex-wrap">
            <button
              v-if="item.payment_url"
              class="btn btn-sm btn-outline-primary"
              :title="'Copy payment URL'"
              @click="copyUrl(item)"
            >
              <i
                class="bi"
                :class="copiedId === item.id ? 'bi-check-lg' : 'bi-clipboard'"
              ></i>
              {{ copiedId === item.id ? 'Copied' : 'Copy URL' }}
            </button>

            <button
              class="btn btn-sm btn-outline-dark"
              :disabled="item.status === 'cancelled' || sendingId === item.id"
              :title="'Resend the payment link email'"
              @click="resendEmail(item)"
            >
              <i
                class="bi"
                :class="sendingId === item.id ? 'bi-hourglass-split' : 'bi-envelope-arrow-up'"
              ></i>
              Resend
            </button>

            <button
              v-if="canUpdate && item.status === 'pending'"
              class="btn btn-sm btn-outline-warning"
              :title="'Cancel this payment link'"
              @click="confirmCancel(item)"
            >
              <i class="bi bi-x-circle"></i>
              Cancel
            </button>

            <button
              v-if="canDelete"
              class="btn btn-sm btn-outline-danger"
              :title="'Delete this payment link'"
              @click="confirmDelete(item)"
            >
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </template>
      </EasyDataTable>
    </div>
  </div>

  <!-- Create modal -->
  <PaymentLinkCreate
    :show="modalCreateVisible"
    @close="modalCreateVisible = false"
    @saved="getData()"
  />

  <!-- Cancel confirmation -->
  <ConfirmModal
    :show="cancelModalVisible"
    title="Cancel Payment Link"
    message="You are about to cancel this payment link. The customer will no longer be able to pay it."
    confirmLabel="Cancel Link"
    @confirm="executeCancel"
    @cancel="cancelModalVisible = false"
  />

  <!-- Delete confirmation -->
  <ConfirmModal
    :show="deleteModalVisible"
    title="Delete Payment Link"
    message="You are about to delete this payment link. This action cannot be undone."
    confirmLabel="Delete Link"
    @confirm="executeDelete"
    @cancel="deleteModalVisible = false"
  />
</template>

<script setup>
import { inject, ref, onMounted } from "vue";
import { useToast } from "vue-toastification";
import api from "@/services/axios";
import PaymentLinkCreate from "./PaymentLinkCreate.vue";
import ConfirmModal from "@/components/admin/shared/ConfirmModal.vue";
import { useMenuPermissions } from "@/composables/useMenuPermissions";

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Payment Links", icon: "bi-link-45deg" });

const { canCreate, canUpdate, canDelete } = useMenuPermissions("/admin/payment-links");

const toast = useToast();

const data = ref([]);
const searchValue = ref("");

const modalCreateVisible = ref(false);
const copiedId = ref(null);
const sendingId = ref(null);

// Cancel / delete targets
const cancelModalVisible = ref(false);
const deleteModalVisible = ref(false);
const actionTarget = ref(null);

const headers = [
  { text: "Status", value: "status", sortable: true },
  { text: "Amount", value: "amount", sortable: true },
  { text: "Description", value: "description", sortable: true },
  { text: "Customer", value: "customer_email", sortable: true },
  { text: "Created", value: "created_at", sortable: true },
  { text: "Actions", value: "actions", sortable: false },
];

const searchField = ["description", "customer_email", "customer_name", "status"];

const createModal = () => {
  modalCreateVisible.value = true;
};

const getData = async () => {
  try {
    const response = await api.get("/payment-links");
    data.value = response.data || [];
  } catch (error) {
    console.error(error);
  }
};

const copyUrl = async (item) => {
  if (!item.payment_url) return;
  try {
    await navigator.clipboard.writeText(item.payment_url);
    copiedId.value = item.id;
    toast.success("Payment URL copied to clipboard");
    setTimeout(() => {
      if (copiedId.value === item.id) copiedId.value = null;
    }, 2000);
  } catch {
    toast.error("Could not copy the URL");
  }
};

const resendEmail = async (item) => {
  if (item.status === "cancelled") return;
  sendingId.value = item.id;
  try {
    await api.post(`/payment-links/${item.id}/send-email`);
    // success toast is emitted by the axios interceptor
  } catch (error) {
    console.error(error);
  } finally {
    sendingId.value = null;
  }
};

const confirmCancel = (item) => {
  actionTarget.value = item;
  cancelModalVisible.value = true;
};

const executeCancel = async () => {
  cancelModalVisible.value = false;
  if (!actionTarget.value) return;
  try {
    await api.post(`/payment-links/${actionTarget.value.id}/cancel`);
    await getData();
  } catch (error) {
    console.error(error);
  } finally {
    actionTarget.value = null;
  }
};

const confirmDelete = (item) => {
  actionTarget.value = item;
  deleteModalVisible.value = true;
};

const executeDelete = async () => {
  deleteModalVisible.value = false;
  if (!actionTarget.value) return;
  try {
    await api.delete(`/payment-links/${actionTarget.value.id}`);
    data.value = data.value.filter((l) => l.id !== actionTarget.value.id);
  } catch (error) {
    console.error(error);
  } finally {
    actionTarget.value = null;
  }
};

// Display helpers
const statusClass = (status) => {
  switch (status) {
    case "paid":
      return "bg-success";
    case "cancelled":
      return "bg-secondary";
    case "pending":
    default:
      return "bg-warning text-dark";
  }
};

const statusLabel = (status) => {
  if (!status) return "Pending";
  return status.charAt(0).toUpperCase() + status.slice(1);
};

const formatAmount = (amount) => {
  const value = Number(amount);
  if (Number.isNaN(value)) return "$0.00";
  return `$${value.toFixed(2)}`;
};

const formatDateTime = (datetime) => {
  if (!datetime) return "N/A";
  // La API serializa las fechas como objeto CodeIgniter\I18n\Time
  // ({ date, timezone_type, timezone }); `new Date(obj)` daría "Invalid Date".
  let value = datetime;
  if (typeof datetime === "object" && datetime.date) {
    value = String(datetime.date).replace(" ", "T");
  }
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return typeof datetime === "string" ? datetime : "N/A";
  return d.toLocaleString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

onMounted(() => {
  getData();
});
</script>

<style scoped></style>
