<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-pencil-square me-2"></i>Update Travel Fee
          </h5>
          <button type="button" class="btn-close" @click="close"></button>
        </div>

        <div class="modal-body">
          <p class="text-muted small mb-3">
            Updates the <strong>Travel Fee</strong> for all Jams in the selected city's county.
          </p>

          <!-- Area -->
          <div class="mb-3">
            <label class="form-label required">Area</label>
            <Multiselect
              v-model="selectedArea"
              :options="areas"
              label="name"
              track-by="id"
              placeholder="Choose an Area..."
              :searchable="true"
              :show-labels="false"
              :allow-empty="false"
              @select="onSelectArea"
            />
          </div>

          <!-- City (dependent on Area) -->
          <div class="mb-3">
            <label class="form-label required">City</label>
            <Multiselect
              v-model="selectedCity"
              :options="cities"
              label="name"
              track-by="id"
              placeholder="Choose a City..."
              :searchable="true"
              :show-labels="false"
              :allow-empty="false"
              :disabled="!selectedArea"
              @select="onSelectCity"
            />
            <small class="text-danger small">{{ errors.city }}</small>
          </div>

          <!-- Travel Fee -->
          <div class="mb-3">
            <label for="areaTravelFee" class="form-label required">New Travel Fee (USD)</label>
            <input
              type="number"
              class="form-control"
              id="areaTravelFee"
              v-model.number="travelFee"
              min="0"
              step="0.01"
              placeholder="0.00"
            />
            <small class="text-danger small">{{ errors.travel_fee }}</small>
          </div>

          <div v-if="successMessage" class="alert alert-success py-2 small mb-0">
            <i class="bi bi-check-circle me-1"></i>{{ successMessage }}
          </div>
          <div v-if="errors.general" class="alert alert-danger py-2 small mb-0">
            <i class="bi bi-exclamation-circle me-1"></i>{{ errors.general }}
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="close">
            <i class="bi bi-arrow-90deg-down"></i> Cancel
          </button>
          <button type="button" class="btn btn-primary" @click="submit" :disabled="loading || !selectedCity">
            <i class="bi bi-save"></i> Update
            <span v-if="loading" class="spinner-border spinner-border-sm ms-2"></span>
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
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";

const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
});

const areas = ref([]);
const cities = ref([]);
const selectedArea = ref(null);
const selectedCity = ref(null);
const travelFee = ref(null);
const errors = ref({});
const loading = ref(false);
const successMessage = ref("");

const fetchAreas = async () => {
  try {
    const response = await api.get("/metropolitan-areas/list-active");
    areas.value = response.data;
  } catch (error) {
    // handled by interceptor
  }
};

const fetchCities = async (areaId) => {
  try {
    const response = await api.get(`/cities/get-by-area/${areaId}`);
    cities.value = response.data;
  } catch (error) {
    cities.value = [];
  }
};

watch(() => props.show, (val) => {
  if (val) fetchAreas();
});

const onSelectArea = (area) => {
  selectedCity.value = null;
  travelFee.value = null;
  cities.value = [];
  successMessage.value = "";
  errors.value = {};
  fetchCities(area.id);
};

const onSelectCity = () => {
  travelFee.value = null;
  successMessage.value = "";
  errors.value = {};
};

const close = () => {
  selectedArea.value = null;
  selectedCity.value = null;
  cities.value = [];
  travelFee.value = null;
  successMessage.value = "";
  errors.value = {};
  emit("close");
};

const submit = async () => {
  errors.value = {};
  successMessage.value = "";

  if (!selectedCity.value) {
    errors.value.city = "Select a City";
    return;
  }
  if (travelFee.value === null || travelFee.value === "" || isNaN(travelFee.value) || travelFee.value < 0) {
    errors.value.travel_fee = "Enter a valid travel fee (min $0)";
    return;
  }

  try {
    loading.value = true;
    const response = await api.put("/service-prices/bulk-update-by-area", {
      county_id: selectedCity.value.county_id,
      travel_fee: travelFee.value,
    });

    const updated = response.data?.updated ?? 0;
    successMessage.value = `Done! Travel fee updated for ${updated} records.`;
    emit("saved");
  } catch (error) {
    errors.value.general = error.response?.data?.message || "An error occurred";
  } finally {
    loading.value = false;
  }
};
</script>
