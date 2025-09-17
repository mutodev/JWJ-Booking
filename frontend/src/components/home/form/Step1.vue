<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-12 col-md-6">
        <!-- Título -->
        <h2 class="mb-4 text-center">Formulario de registro</h2>

        <!-- First name -->
        <div class="mb-3">
          <label for="firstName" class="form-label">
            First name <span class="text-danger">*</span>
          </label>
          <input
            v-model="form.firstName"
            type="text"
            class="form-control"
            id="firstName"
            placeholder="Elena"
            @blur="validateField('firstName')"
          />
          <div v-if="errors.firstName" class="text-danger small">
            {{ errors.firstName }}
          </div>
        </div>

        <!-- Last name -->
        <div class="mb-3">
          <label for="lastName" class="form-label">
            Last name <span class="text-danger">*</span>
          </label>
          <input
            v-model="form.lastName"
            type="text"
            class="form-control"
            id="lastName"
            placeholder="Guzmán"
            @blur="validateField('lastName')"
          />
          <div v-if="errors.lastName" class="text-danger small">
            {{ errors.lastName }}
          </div>
        </div>

        <!-- Email -->
        <div class="mb-3">
          <label for="email" class="form-label">
            Email <span class="text-danger">*</span>
          </label>
          <input
            v-model="form.email"
            type="email"
            class="form-control"
            id="email"
            placeholder="mail@example.com"
            @blur="validateField('email')"
          />
          <div v-if="errors.email" class="text-danger small">
            {{ errors.email }}
          </div>
        </div>

        <!-- Phone number -->
        <div class="mb-3">
          <label for="phone" class="form-label">
            Phone number <span class="text-danger">*</span>
          </label>
          <input
            v-model="form.phone"
            type="tel"
            class="form-control"
            id="phone"
            placeholder="+1 000 0000"
            @blur="validateField('phone')"
          />
          <div v-if="errors.phone" class="text-danger small">
            {{ errors.phone }}
          </div>
        </div>

        <!-- Location -->
        <div class="mb-3">
          <label for="location" class="form-label">Choose your location</label>
          <select
            v-model="form.location"
            id="location"
            class="form-select"
            @blur="validateField('location')"
          >
            <option value="">Please select</option>
            <option value="location1">Location 1</option>
            <option value="location2">Location 2</option>
            <option value="location3">Location 3</option>
          </select>
          <div v-if="errors.location" class="text-danger small">
            {{ errors.location }}
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
  firstName: "",
  lastName: "",
  email: "",
  phone: "",
  location: "",
});

// Errores
const errors = reactive({});

// Esquema Yup
const schema = yup.object({
  firstName: yup.string().required("First name is required"),
  lastName: yup.string().required("Last name is required"),
  email: yup.string().email("Invalid email").required("Email is required"),
  phone: yup
    .string()
    .matches(/^[0-9+\s-]{7,15}$/, "Invalid phone number")
    .required("Phone number is required"),
  location: yup.string().nullable(),
});

// Validar campo individual al salir de foco
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
.form-control,
.form-select {
  border-radius: 8px;
  font-size: 0.95rem;
  padding: 0.6rem 0.75rem;
}

.form-label {
  font-size: 0.9rem;
  font-weight: 500;
  color: #444;
}
</style>
