<template>
  <nav class="sidebar bg-dark text-white position-fixed h-100 shadow-sm">
    <ul class="nav flex-column pt-3">
      <li class="nav-item mb-2" v-for="value in access" :key="value.id">
        <!-- Si tiene hijos -->
        <div v-if="value.children && value.children.length > 0">
          <button
            class="nav-link text-white d-flex align-items-center sidebar-link w-100"
            @click="toggleCollapse(value.id)"
          >
            <i :class="value.icon + ' me-2'"></i>
            <span class="link-text">{{ value.name }}</span>
            <i
              class="bi bi-caret-down ms-auto"
              :class="{ 'rotate-180': isOpen(value.id) }"
            ></i>
          </button>

          <ul class="collapse ps-4" :class="{ show: isOpen(value.id) }">
            <li
              class="nav-item mb-1"
              v-for="child in value.children"
              :key="child.id"
            >
              <router-link
                :to="child.uri"
                class="nav-link text-white d-flex align-items-center sidebar-link"
              >
                <i :class="child.icon + ' me-2'"></i>
                <span class="link-text">{{ child.name }}</span>
              </router-link>
            </li>
          </ul>
        </div>

        <!-- Si no tiene hijos -->
        <router-link
          v-else
          :to="value.uri"
          class="nav-link text-white d-flex align-items-center sidebar-link"
        >
          <i :class="value.icon + ' me-2'"></i>
          <span class="link-text">{{ value.name }}</span>
        </router-link>
      </li>
    </ul>
  </nav>
</template>

<script setup>
import { ref } from "vue";

const access = ref(JSON.parse(sessionStorage.getItem("access") || "[]"));

// Estado de los colapsables
const openMenus = ref({});

// Toggle del collapse
const toggleCollapse = (id) => {
  openMenus.value[id] = !openMenus.value[id];
};

// Saber si un collapse está abierto
const isOpen = (id) => !!openMenus.value[id];
</script>

<style scoped>
.sidebar {
  width: 250px;
  background: #343a40;
  font-family: "Segoe UI", sans-serif;
}

/* Sidebar links */
.sidebar-link {
  padding: 12px 18px;
  border-radius: 8px;
  transition: background 0.2s ease, color 0.2s ease;
}
.sidebar-link:hover {
  background: #495057;
  color: #fff;
}

/* Submenu styling */
.collapse .nav-link {
  padding-left: 30px;
}

/* Icon rotate */
.rotate-180 {
  transform: rotate(180deg);
  transition: transform 0.3s;
}

/* Mobile: solo íconos */
@media (max-width: 991px) {
  .sidebar {
    width: 70px;
  }
  .link-text {
    display: none;
  }
  .nav-link {
    justify-content: center;
    padding: 12px 0;
  }
  .nav-link .me-2 {
    margin-right: 0 !important;
  }
}
</style>
