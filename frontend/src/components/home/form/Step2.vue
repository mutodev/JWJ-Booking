<template>
  <div class="container py-5 h-100">
    <div class="row justify-content-center">
      <div class="col-12 col-md-6">
        <!-- Título con ícono -->
        <div class="d-flex align-items-center mb-3">
          <img
            src="https://cdn-icons-png.flaticon.com/512/616/616408.png"
            alt="cat-icon"
            class="me-2"
            width="28"
            height="28"
          />
          <h2 class="m-0">Enter your zipcode</h2>
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
import { reactive } from "vue";
import * as yup from "yup";

// Estado del formulario
const form = reactive({
  zipcode: "",
});

// Errores
const errors = reactive({});

// Esquema Yup para zipcode
const schema = yup.object({
  zipcode: yup
    .string()
    .matches(/^[0-9]{4,10}$/, "Invalid zipcode (4-10 digits)")
    .required("Zipcode is required"),
});

// Validación al perder foco
async function validateField(field) {
  try {
    await schema.validateAt(field, form);
    errors[field] = "";
  } catch (e) {
    errors[field] = e.message;
  }
}
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
