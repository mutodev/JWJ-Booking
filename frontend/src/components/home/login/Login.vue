<template>
  <div class="d-flex align-items-center justify-content-center vh-100 bg-light">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5">
          <div class="card shadow-sm">
            <div class="card-body">
              <h4 class="card-title mb-4 text-center">Iniciar sesión</h4>
              <form @submit.prevent="onSubmit" novalidate>
                <div class="mb-3">
                  <label for="email" class="form-label"
                    >Correo electrónico</label
                  >
                  <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="form-control"
                    required
                  />
                </div>
                <div class="mb-3">
                  <label for="password" class="form-label">Contraseña</label>
                  <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="form-control"
                    required
                  />
                </div>
                <div class="d-grid mb-3">
                  <button
                    :disabled="loading"
                    type="submit"
                    class="btn btn-primary"
                  >
                    <span v-if="!loading">Entrar</span>
                    <span v-else class="d-inline-flex align-items-center gap-2">
                      <span
                        class="spinner-border spinner-border-sm"
                        role="status"
                        aria-hidden="true"
                      ></span>
                      Cargando...
                    </span>
                  </button>
                </div>
                <div class="text-center">
                  <a href="#">¿Olvidaste tu contraseña?</a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import api from "@/services/axios";
import { useRouter } from 'vue-router';

const router = useRouter();
const form = ref({ email: "", password: "" });
const loading = ref(false);

/**
 * Submit de inicio de sesión
 */
async function onSubmit() {
  loading.value = true;
  api
    .post("/auth/login", form.value)
    .then((response) => {        
      sessionStorage.setItem("token", response.data);
      router.replace("/admin");
    })
    .catch((error) => {
      loading.value = false;
    });
}
</script>

<style scoped></style>
