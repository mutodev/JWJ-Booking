<template>
  <div class="d-flex align-items-center justify-content-center vh-100 bg-light">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5">
          <div class="card shadow-sm">
            <div class="card-body">
              <h4 class="card-title mb-4 text-center">
                <i class="bi bi-shield-lock-fill"></i>
                Reset password
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

                <button type="submit" class="btn btn-primary w-100">
                  <i class="bi bi-send"></i>
                  Send
                </button>
              </form>
            </div>
            <div class="card-footer text-center">
              <router-link to="/login" class="text-decoration-none">
                Back to Sign In
              </router-link>
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

const schema = yup.object({
  email: yup.string().email("Invalid email").required("Email is required"),
});

const { handleSubmit } = useForm({ validationSchema: schema });
const { value: email, errorMessage: emailError } = useField("email");

const submitForm = handleSubmit(async (values) => {
  api
    .post("/auth/reset-password", values)
    .then((response) => {
      router.replace("/login");
    })
    .catch((error) => {});
});
</script>
