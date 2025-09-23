<template>
  <header class="navbar sticky-top navbar-light bg-white shadow-sm py-2">
    <div
      class="container-fluid d-flex align-items-center justify-content-between"
    >
      <!-- Logo o título -->
      <span class="navbar-brand mb-0 h4 text-dark">Admin JWJ</span>
      <span class="navbar-brand mb-0 h4 text-dark">
        <i :class="headerData.icon"></i> {{ headerData.title }}</span
      >

      <!-- Información y dropdown del usuario -->
      <div class="d-flex align-items-center">
        <!-- Nombre y cargo (desktop) -->
        <div class="d-none d-lg-flex flex-column text-end me-3">
          <span class="fw-semibold">{{ user.name }}</span>
          <small class="text-muted">{{ user.role }}</small>
        </div>

        <!-- Dropdown del usuario -->
        <div class="dropdown" ref="dropdownElement">
          <button
            class="btn p-0 border-0 bg-transparent d-flex align-items-center"
            type="button"
            @click="toggleDropdown"
            :aria-expanded="dropdownOpen"
          >
            <i class="bi bi-person-circle fs-3"></i>
          </button>
          <ul
            class="dropdown-menu dropdown-menu-end shadow"
            :class="{ show: dropdownOpen }"
          >
            <li>
              <router-link
                class="dropdown-item"
                to="/admin/profile"
                @click="closeDropdown"
              >
                <i class="bi bi-person-fill me-2"></i>
                Profile
              </router-link>
            </li>
            <li>
              <button
                class="dropdown-item text-danger"
                @click="closeSession"
              >
                <i class="bi bi-box-arrow-right me-2"></i>
                Log out
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { inject, ref, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();

const user = ref({
  name:
    sessionStorage.getItem("first_name") +
    " " +
    sessionStorage.getItem("last_name"),
  role: sessionStorage.getItem("role_name"),
});

const dropdownOpen = ref(false);
const dropdownElement = ref(null);

const toggleDropdown = () => {
  dropdownOpen.value = !dropdownOpen.value;
};

const closeDropdown = () => {
  dropdownOpen.value = false;
};

const closeSession = () => {
  sessionStorage.clear();
  router.replace("/login");
};

const handleClickOutside = (event) => {
  if (dropdownElement.value && !dropdownElement.value.contains(event.target)) {
    closeDropdown();
  }
};

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside);
});

const headerData = inject("headerData");
</script>

<style scoped>
.navbar {
  z-index: 1030;
  font-family: "Segoe UI", sans-serif;
}

.btn:focus {
  box-shadow: none;
}

.dropdown {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  left: auto;
  min-width: 180px;
  max-width: 200px;
  margin-top: 0.25rem;
  z-index: 1050;
}

.dropdown-menu-end {
  min-width: 180px;
}

@media (max-width: 576px) {
  .dropdown-menu {
    right: 0;
    left: auto;
    min-width: 160px;
  }
}

.dropdown-item.router-link-active,
.dropdown-item.router-link-exact-active {
  color: #212529 !important;
  background-color: #f8f9fa;
}

.dropdown-item.router-link-active:hover,
.dropdown-item.router-link-exact-active:hover {
  color: #1e2125 !important;
  background-color: #e9ecef;
}

.dropdown-item.router-link-active i,
.dropdown-item.router-link-exact-active i {
  color: #212529 !important;
}

.dropdown-item.router-link-active:hover i,
.dropdown-item.router-link-exact-active:hover i {
  color: #1e2125 !important;
}
</style>
