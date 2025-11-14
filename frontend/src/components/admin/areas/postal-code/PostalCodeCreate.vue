<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create Zip code</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <div class="mb-3">
              <label for="zipcode" class="form-label">zipcode</label>
              <input
                type="text"
                class="form-control"
                id="zipcode"
                v-model="zipcode"
                required
                placeholder="Enter zipcode"
              />
              <small class="text-danger small">{{ zipcode_error }}</small>
            </div>

            <div class="mb-3">
              <label for="city_id" class="form-label">City</label>
              <select
                class="form-select"
                id="city_id"
                v-model="city_id"
                required
              >
                <option value="" disabled>Select a city</option>
                <option
                  v-for="item in dataCities"
                  :key="item.id"
                  :value="item.id"
                >
                  {{ item.name }}
                </option>
              </select>
              <small class="text-danger small">{{
                city_id_error
              }}</small>
            </div>

            <div class="mb-3">
              <label for="zone_type" class="form-label">Zone Type</label>
              <select
                class="form-select"
                id="zone_type"
                v-model="zone_type"
                required
              >
                <option value="" disabled>Select a zone type</option>
                <option value="standard">Standard - No additional fees</option>
                <option value="travel_fee">Travel Fee - Additional charges apply</option>
                <option value="minimum_2h">Minimum 2 Hours - Two hour minimum required</option>
                <option value="not_available">Not Available - Service not offered</option>
              </select>
              <small class="text-danger small">{{ zone_type_error }}</small>
            </div>

            <div v-if="zone_type === 'travel_fee'" class="mb-3">
              <label for="travel_fee_1_performer" class="form-label">Travel Fee (1 Performer)</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  class="form-control"
                  id="travel_fee_1_performer"
                  v-model="travel_fee_1_performer"
                  placeholder="0.00"
                />
              </div>
              <small class="text-danger small">{{ travel_fee_1_performer_error }}</small>
            </div>

            <div v-if="zone_type === 'travel_fee'" class="mb-3">
              <label for="travel_fee_2_performers" class="form-label">Travel Fee (2 Performers)</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  class="form-control"
                  id="travel_fee_2_performers"
                  v-model="travel_fee_2_performers"
                  placeholder="0.00"
                />
              </div>
              <small class="text-danger small">{{ travel_fee_2_performers_error }}</small>
            </div>
          </form>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            <i class="bi bi-arrow-90deg-down"></i>
            Back
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="submitForm"
            :disabled="loading"
          >
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
import { useForm, useField } from "vee-validate";
import { watch, ref, onMounted } from "vue";
import api from "@/services/axios";
import * as yup from "yup";

const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
  cities: {
    type: Array,
    default: () => [],
  },
});

const dataCities = ref([]);
const loading = ref(false);

watch(
  () => props.cities,
  (newData) => {
    dataCities.value = [...newData];
  },
  { deep: true, immediate: true }
);

const schema = yup.object({
  zipcode: yup
    .string()
    .required("Zipcode is required")
    .matches(/^\d{5}(-\d{4})?$/, "Zipcode must be valid (5 or 9 digits)")
    .max(10, "Zipcode must not exceed 10 characters"),
  city_id: yup
    .string()
    .required("Is required"),
  zone_type: yup
    .string()
    .required("Zone type is required")
    .oneOf(['standard', 'travel_fee', 'minimum_2h', 'not_available'], "Invalid zone type"),
  travel_fee_1_performer: yup
    .number()
    .nullable()
    .transform((value, originalValue) => originalValue === '' ? null : value)
    .min(0, "Travel fee must be positive")
    .when('zone_type', {
      is: 'travel_fee',
      then: (schema) => schema.required("Travel fee for 1 performer is required when zone type is 'travel_fee'"),
      otherwise: (schema) => schema.nullable()
    }),
  travel_fee_2_performers: yup
    .number()
    .nullable()
    .transform((value, originalValue) => originalValue === '' ? null : value)
    .min(0, "Travel fee must be positive")
    .when('zone_type', {
      is: 'travel_fee',
      then: (schema) => schema.required("Travel fee for 2 performers is required when zone type is 'travel_fee'"),
      otherwise: (schema) => schema.nullable()
    }),
});

const { handleSubmit, resetForm } = useForm({
  validationSchema: schema,
});

const { value: zipcode, errorMessage: zipcode_error } = useField("zipcode");
const { value: city_id, errorMessage: city_id_error } = useField("city_id");
const { value: zone_type, errorMessage: zone_type_error } = useField("zone_type");
const { value: travel_fee_1_performer, errorMessage: travel_fee_1_performer_error } = useField("travel_fee_1_performer");
const { value: travel_fee_2_performers, errorMessage: travel_fee_2_performers_error } = useField("travel_fee_2_performers");

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      // Resetear el formulario cuando la modal se abre
      resetForm({
        values: {
          zipcode: "",
          city_id: "",
          zone_type: "",
          travel_fee_1_performer: null,
          travel_fee_2_performers: null
        },
      });
    }
  }
);

// Watch zone_type to clear travel fees when not 'travel_fee'
watch(zone_type, (newValue) => {
  if (newValue !== 'travel_fee') {
    travel_fee_1_performer.value = null;
    travel_fee_2_performers.value = null;
  }
});

const closeModal = () => {
  emit("close");
};

const submitForm = handleSubmit(async (values) => {
  loading.value = true;
  try {
    await api.post(`/zipcodes`, values);
    emit("saved", true);
    closeModal();
  } finally {
    loading.value = false;
  }
});

onMounted(() => {
  if (props.show) {
    resetForm({
      values: {
        zipcode: "",
        city_id: "",
        zone_type: "",
        travel_fee_1_performer: null,
        travel_fee_2_performers: null
      },
    });
  }
});
</script>
