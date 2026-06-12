<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Zip code</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <div class="mb-3">
              <label class="form-label">Zipcode</label>
              <input
                type="text"
                class="form-control"
                :value="zipcode?.zipcode"
                disabled
              />
            </div>

            <div class="mb-3">
              <label for="edit_city_id" class="form-label">City</label>
              <select class="form-select" id="edit_city_id" v-model="form.city_id" required>
                <option value="" disabled>Select a city</option>
                <option v-for="item in cities" :key="item.id" :value="item.id">
                  {{ item.name }}
                </option>
              </select>
            </div>

            <div class="mb-3">
              <label for="edit_zone_type" class="form-label">Zone Type</label>
              <select class="form-select" id="edit_zone_type" v-model="form.zone_type" required>
                <option value="" disabled>Select a zone type</option>
                <option value="standard">Standard - No additional fees</option>
                <option value="travel_fee">Travel Fee - Additional charges apply</option>
                <option value="minimum_2h">Minimum 2 Hours - Two hour minimum required</option>
                <option value="not_available">Not Available - Service not offered</option>
              </select>
            </div>

            <div v-if="form.zone_type === 'travel_fee'" class="mb-3">
              <label for="edit_travel_fee_1" class="form-label">Travel Fee (1 Performer)</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" step="0.01" min="0" class="form-control" id="edit_travel_fee_1" v-model="form.travel_fee_1_performer" placeholder="0.00" />
              </div>
            </div>

            <div v-if="form.zone_type === 'travel_fee'" class="mb-3">
              <label for="edit_travel_fee_2" class="form-label">Travel Fee (2 Performers)</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" step="0.01" min="0" class="form-control" id="edit_travel_fee_2" v-model="form.travel_fee_2_performers" placeholder="0.00" />
              </div>
            </div>

            <div class="mb-3">
              <label for="edit_override_county" class="form-label">
                Price Override County
                <span class="text-muted small">(optional — uses this county's pricing instead of the real one)</span>
              </label>
              <select class="form-select" id="edit_override_county" v-model="form.override_county_id">
                <option value="">No override — use real county pricing</option>
                <option v-for="item in counties" :key="item.id" :value="item.id">
                  {{ item.name }}
                </option>
              </select>
            </div>
          </form>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            <i class="bi bi-arrow-90deg-down"></i>
            Back
          </button>
          <button type="button" class="btn btn-primary" @click="submitForm" :disabled="loading">
            <i class="bi bi-save"></i>
            Save
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
  zipcode: { type: Object, default: null },
  cities: { type: Array, default: () => [] },
  counties: { type: Array, default: () => [] },
});

const loading = ref(false);
const form = ref({
  city_id: "",
  zone_type: "",
  travel_fee_1_performer: null,
  travel_fee_2_performers: null,
  override_county_id: "",
});

watch(
  () => props.zipcode,
  (val) => {
    if (val) {
      form.value = {
        city_id: val.city_id ?? "",
        zone_type: val.zone_type ?? "",
        travel_fee_1_performer: val.travel_fee_1_performer ?? null,
        travel_fee_2_performers: val.travel_fee_2_performers ?? null,
        override_county_id: val.override_county_id ?? "",
      };
    }
  },
  { immediate: true }
);

watch(
  () => form.value.zone_type,
  (val) => {
    if (val !== "travel_fee") {
      form.value.travel_fee_1_performer = null;
      form.value.travel_fee_2_performers = null;
    }
  }
);

const closeModal = () => emit("close");

const submitForm = async () => {
  loading.value = true;
  try {
    const payload = { ...form.value };
    if (!payload.override_county_id) payload.override_county_id = null;
    await api.put(`/zipcodes/${props.zipcode.id}`, payload);
    emit("saved");
    closeModal();
  } finally {
    loading.value = false;
  }
};
</script>
