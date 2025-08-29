<template>
  <header class="navbar sticky-top navbar-light bg-white shadow-sm py-2">
    <div
      class="container-fluid d-flex align-items-center justify-content-between"
    >
      <!-- Logo o título -->
      <span class="navbar-brand mb-0 h4 text-dark">Admin JWJ</span>

      <!-- Información y dropdown del usuario -->
      <div class="d-flex align-items-center">
        <!-- Nombre y cargo (desktop) -->
        <div class="d-none d-lg-flex flex-column text-end me-3">
          <span class="fw-semibold">{{ user.name }}</span>
          <small class="text-muted">{{ user.role }}</small>
        </div>

        <!-- Dropdown del usuario -->
        <div class="dropdown">
          <button
            class="btn p-0 border-0 bg-transparent d-flex align-items-center"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >
            <i class="bi bi-person-circle fs-3"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow">
            <li>
              <router-link class="dropdown-item" to="/profile"
                >Editar perfil</router-link
              >
            </li>
            <li>
              <button class="dropdown-item text-danger" v-on:click="closeSession()">
                Cerrar sesión
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();

const user = ref({
  name:
    sessionStorage.getItem("first_name") +
    " " +
    sessionStorage.getItem("last_name"),
  role: sessionStorage.getItem("role_name"),
});

const closeSession = () => {
  sessionStorage.clear();
  router.replace("/login");
};
</script>

<style scoped>
.navbar {
  z-index: 1030;
  font-family: "Segoe UI", sans-serif;
}

.btn:focus {
  box-shadow: none;
}

.dropdown-menu-end {
  min-width: 180px;
}
</style>
