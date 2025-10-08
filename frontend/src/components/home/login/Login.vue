<template>
  <div class="d-flex align-items-center justify-content-center vh-100 bg-light">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5">
          <div class="card shadow-sm">
            <div class="card-body">
              <h4 class="card-title mb-4 text-center">
                <i class="bi bi-person-fill"></i>
                Sign In
              </h4>
              <!-- Usamos handleSubmit para validar -->
              <form @submit.prevent="submitForm">
                <div class="mb-3">
                  <input
                    type="email"
                    v-model="email"
                    class="form-control"
                    placeholder="Email"
                  />
                  <small class="text-danger small">{{ emailError }}</small>
                </div>

                <div class="mb-3">
                  <input
                    type="password"
                    v-model="password"
                    class="form-control"
                    placeholder="Password"
                  />
                  <small class="text-danger small">{{ passError }}</small>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                  <i class="bi bi-box-arrow-in-right"></i>
                  Sign In
                </button>
              </form>
            </div>
            <div class="card-footer text-center">
              <router-link to="/reset-password" class="text-decoration-none"
                >Forgot your password?</router-link
              >
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm, useField } from "vee-validate";
import * as yup from "yup";
import api from "@/services/axios";
import { useRouter } from "vue-router";

const router = useRouter();

// Esquema de validación con Yup
const schema = yup.object({
  email: yup
    .string()
    .email("Invalid email")
    .required("Email is required"),
  password: yup
    .string()
    .min(6, "Minimum 6 characters")
    .required("A password is required"),
});

// Configurar el form con validación
const { handleSubmit } = useForm({ validationSchema: schema });

// Campos de formulario
const { value: email, errorMessage: emailError } = useField("email");
const { value: password, errorMessage: passError } = useField("password");

/**
 * Submit validado con vee-validate
 */
const submitForm = handleSubmit(async (values) => {
  try {
    const response = await api.post("/auth/login", values);

    // El token se guarda automáticamente en el interceptor de axios
    // Solo verificamos que el token exista antes de redirigir
    const token = sessionStorage.getItem("token");

    if (token) {
      console.log("Login successful, token saved");
      router.replace("/admin");
    } else {
      console.error("Token not saved after login");
    }
  } catch (error) {
    console.error("Login error:", error);
  }
});
</script>

<style scoped></style>
