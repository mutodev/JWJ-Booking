<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create Service Price</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <!-- 🔹 Service -->
            <div class="mb-3">
              <label for="service_id" class="form-label">Service</label>
              <select
                id="service_id"
                class="form-select"
                v-model="form.service_id"
                required
              >
                <option value="">-- Select Service --</option>
                <option v-for="srv in services" :key="srv.id" :value="srv.id">
                  {{ srv.name }}
                </option>
              </select>
              <small class="text-danger">{{ errors.service_id }}</small>
            </div>

            <!-- 🔹 County -->
            <div class="mb-3">
              <label for="county_id" class="form-label">County</label>
              <select
                id="county_id"
                class="form-select"
                v-model="form.county_id"
                required
              >
                <option value="">-- Select County --</option>
                <option v-for="c in counties" :key="c.id" :value="c.id">
                  {{ c.name }}
                </option>
              </select>
              <small class="text-danger">{{ errors.county_id }}</small>
            </div>

            <!-- 🔹 Performers Count -->
            <div class="mb-3">
              <label for="performers_count" class="form-label"
                >Performers Count</label
              >
              <input
                type="number"
                class="form-control"
                id="performers_count"
                v-model="form.performers_count"
                min="1"
                required
              />
              <small class="text-danger">{{ errors.performers_count }}</small>
            </div>

            <!-- 🔹 Price Type -->
            <div class="mb-3">
              <label for="price_type" class="form-label">Price Type</label>
              <select
                id="price_type"
                class="form-select"
                v-model="form.price_type"
                required
              >
                <option value="">-- Select Price Type --</option>
                <option value="standard">Standard</option>
                <option value="jukebox">Jukebox</option>
              </select>
              <small class="text-danger">{{ errors.price_type }}</small>
            </div>

            <!-- 🔹 Amount -->
            <div class="mb-3">
              <label for="amount" class="form-label">Amount (USD)</label>
              <input
                type="number"
                class="form-control"
                id="amount"
                v-model="form.amount"
                min="0"
                step="0.01"
                required
              />
              <small class="text-danger">{{ errors.amount }}</small>
            </div>

            <!-- 🔹 Min Duration -->
            <div class="mb-3">
              <label for="min_duration_hours" class="form-label"
                >Min Duration (Hours)</label
              >
              <input
                type="number"
                class="form-control"
                id="min_duration_hours"
                v-model="form.min_duration_hours"
                min="1"
                required
              />
              <small class="text-danger">{{ errors.min_duration_hours }}</small>
            </div>

            <!-- 🔹 Notes -->
            <div class="mb-3">
              <label for="notes" class="form-label">Notes</label>
              <textarea
                class="form-control"
                id="notes"
                v-model="form.notes"
                placeholder="Optional notes"
              ></textarea>
              <small class="text-danger">{{ errors.notes }}</small>
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

const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
  services: {
    type: Array,
    default: () => [],
  },
  counties: {
    type: Array,
    default: () => [],
  },
});

const form = ref({
  service_id: "",
  county_id: "",
  performers_count: 1,
  price_type: "",
  amount: 0,
  min_duration_hours: 1,
  notes: "",
  is_available: true, // 👈 siempre en true
});

const errors = ref({});
const loading = ref(false);

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      resetForm();
    }
  }
);

const resetForm = () => {
  form.value = {
    service_id: "",
    county_id: "",
    performers_count: 1,
    price_type: "",
    amount: 0,
    min_duration_hours: 1,
    notes: "",
    is_available: true,
  };
  errors.value = {};
};

const closeModal = () => {
  emit("close");
};

const submitForm = async () => {
  try {
    loading.value = true;
    await api.post("/service-prices", form.value);
    emit("saved", true);
    closeModal();
  } catch (error) {
    console.error(error);
    errors.value = error.response?.data || {};
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
}
.modal-backdrop {
  z-index: 1040;
}
.modal-dialog {
  z-index: 1050;
}
</style>
