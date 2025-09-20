<template>
  <div class="container py-5 h-100">
    <div class="row justify-content-center">
      <div class="col-12 col-md-6">
        <!-- Título con ícono -->
        <div class="d-flex align-items-center mb-3">
          <h2 class="m-0"><i class="bi bi-pin"></i> Enter your zipcode</h2>
        </div>

        <!-- Campo Zipcode -->
        <div class="mb-3">
          <input
            v-model="form.zipcode"
            type="text"
            class="form-control"
            placeholder="zipcode number"
            @blur="validateField('zipcode')"
          />
          <div v-if="errors.zipcode" class="text-danger small mt-1">
            {{ errors.zipcode }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from "vue";
import * as yup from "yup";
import api from "@/services/axios";
const props = defineProps({
  city: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(["setData"]);
const form = reactive({
  zipcode: "",
});

const errors = reactive({});
const schema = yup.object({
  zipcode: yup
    .string()
    .matches(/^[0-9]{4,10}$/, "Invalid zipcode (4-10 digits)")
    .required("Zipcode is required"),
});
async function validateField(field) {
  try {
    await schema.validateAt(field, form);
    errors[field] = "";
  } catch (e) {
    errors[field] = e.message;
  }
}

let debounceTimer = null;
watch(
  () => form.zipcode,
  async (newZip) => {
    clearTimeout(debounceTimer);
    try {
      await schema.validateAt("zipcode", form);
      errors.zipcode = "";
    } catch (e) {
      errors.zipcode = e.message;
      return;
    }

    if (newZip && newZip.length >= 4 && newZip.length <= 10) {
      debounceTimer = setTimeout(async () => {
        try {
          const response = await api.get(`/home/zipcode/${props.city}/${newZip}`);
          emit("setData", { zipcode: response.data });
        } catch (error) {
          console.error('Error validating zipcode:', error);
          errors.zipcode = "Invalid zipcode";
        }
      }, 1000);
    }
  }
);
</script>

<style scoped>
.form-control {
  border-radius: 8px;
  font-size: 0.95rem;
  padding: 0.6rem 0.75rem;
}

h2 {
  font-size: 1.2rem;
  font-weight: 600;
  color: #222;
}
</style>
