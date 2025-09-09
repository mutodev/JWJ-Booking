<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <!-- Header -->
        <div class="modal-header">
          <h5 class="modal-title">Create Client</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <!-- Full Name -->
            <div class="mb-3">
              <label for="fullName" class="form-label">Full Name</label>
              <input
                type="text"
                class="form-control"
                id="fullName"
                v-model="full_name"
                required
                placeholder="Enter full name"
              />
              <small class="text-danger small">{{ full_name_error }}</small>
            </div>

            <!-- Email -->
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input
                type="email"
                class="form-control"
                id="email"
                v-model="email"
                required
                placeholder="Enter email"
              />
              <small class="text-danger small">{{ email_error }}</small>
            </div>

            <!-- Phone -->
            <div class="mb-3">
              <label for="phone" class="form-label">Phone</label>
              <input
                type="text"
                class="form-control"
                id="phone"
                v-model="phone"
                required
                placeholder="Enter phone number"
              />
              <small class="text-danger small">{{ phone_error }}</small>
            </div>

            <!-- Billing Address -->
            <div class="mb-3">
              <label for="billingAddress" class="form-label"
                >Billing Address</label
              >
              <input
                type="text"
                class="form-control"
                id="billingAddress"
                v-model="billing_address"
                placeholder="Enter billing address"
              />
              <small class="text-danger small">{{ billing_address_error }}</small>
            </div>

            <!-- Segment -->
            <div class="mb-3">
              <label for="segment" class="form-label">Segment</label>
              <select
                class="form-select"
                id="segment"
                v-model="segment"
                required
              >
                <option value="" disabled>Select a segment</option>
                <option value="new">New</option>
                <option value="frequent">Frequent</option>
                <option value="vip">VIP</option>
              </select>
              <small class="text-danger small">{{ segment_error }}</small>
            </div>
          </form>
        </div>

        <!-- Footer -->
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
});

const schema = yup.object({
  full_name: yup.string().required("Full name is required").min(2).max(100),
  email: yup.string().required("Email is required").email("Invalid email"),
  phone: yup.string().required("Phone is required").min(7).max(20),
  billing_address: yup.string().max(255, "Maximum 255 characters"),
  segment: yup
    .string()
    .required("Segment is required")
    .oneOf(["new", "frequent", "vip"], "Invalid segment"),
});

const { handleSubmit, resetForm } = useForm({
  validationSchema: schema,
});

const { value: full_name, errorMessage: full_name_error } =
  useField("full_name");
const { value: email, errorMessage: email_error } = useField("email");
const { value: phone, errorMessage: phone_error } = useField("phone");
const { value: billing_address, errorMessage: billing_address_error } =
  useField("billing_address");
const { value: segment, errorMessage: segment_error } = useField("segment");

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      resetForm({
        values: {
          full_name: "",
          email: "",
          phone: "",
          billing_address: "",
          segment: "",
        },
      });
    }
  }
);

const closeModal = () => {
  emit("close");
};

const loading = ref(false);

const submitForm = handleSubmit(async (values) => {
  try {
    loading.value = true;
    await api.post(`/customers`, values);
    emit("saved", true);
    closeModal();
  } catch (error) {
    console.error(error);
  } finally {
    loading.value = false;
  }
});

onMounted(() => {
  if (props.show) {
    resetForm({
      values: {
        full_name: "",
        email: "",
        phone: "",
        billing_address: "",
        segment: "",
      },
    });
  }
});
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
