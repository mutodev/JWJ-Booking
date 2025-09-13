<template>
  <nav
    class="sidebar bg-dark text-white position-fixed h-100 shadow-sm"
    :class="{ collapsed: isCollapsed }"
  >
    <ul class="nav flex-column pt-5 mt-3">
      <li class="nav-item mb-2" v-for="value in access" :key="value.id">
        <!-- Si tiene hijos -->
        <div v-if="value.children && value.children.length > 0">
          <button
            class="nav-link text-white d-flex align-items-center sidebar-link w-100"
            @click="toggleCollapse(value.id)"
            :title="value.name"
            :data-bs-toggle="isCollapsed ? 'tooltip' : ''"
            data-bs-placement="right"
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
                :title="child.name"
                :data-bs-toggle="isCollapsed ? 'tooltip' : ''"
                data-bs-placement="right"
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
          :title="value.name"
          :data-bs-toggle="isCollapsed ? 'tooltip' : ''"
          data-bs-placement="right"
        >
          <i :class="value.icon + ' me-2'"></i>
          <span class="link-text">{{ value.name }}</span>
        </router-link>
      </li>
    </ul>
  </nav>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";

const access = ref(JSON.parse(sessionStorage.getItem("access") || "[]"));

// Estado de los colapsables
const openMenus = ref({});

// Estado del sidebar colapsado (solo por tamaño de pantalla)
const isCollapsed = ref(false);

// Toggle del collapse de menús
const toggleCollapse = (id) => {
  openMenus.value[id] = !openMenus.value[id];
};

// Saber si un collapse está abierto
const isOpen = (id) => !!openMenus.value[id];

// Detectar cambio de tamaño de pantalla
const handleResize = () => {
  const wasCollapsed = isCollapsed.value;
  isCollapsed.value = window.innerWidth <= 991;

  if (wasCollapsed !== isCollapsed.value) {
    updateTooltips();
  }
};

// Inicializar y actualizar tooltips
const updateTooltips = () => {
  setTimeout(() => {
    const existingTooltips = document.querySelectorAll(
      '[data-bs-toggle="tooltip"]'
    );
    existingTooltips.forEach((el) => {
      const tooltip = window.bootstrap?.Tooltip?.getInstance(el);
      if (tooltip) tooltip.dispose();
    });

    if (isCollapsed.value && window.bootstrap) {
      const tooltipTriggerList = document.querySelectorAll(
        '[data-bs-toggle="tooltip"]'
      );
      tooltipTriggerList.forEach((tooltipTriggerEl) => {
        new window.bootstrap.Tooltip(tooltipTriggerEl);
      });
    }
  }, 50);
};

onMounted(() => {
  handleResize();
  window.addEventListener("resize", handleResize);
  updateTooltips();
});

watch(isCollapsed, updateTooltips);
</script>

<style scoped>
.sidebar {
  width: 250px;
  background: #343a40;
  font-family: "Segoe UI", sans-serif;
  transition: width 0.3s ease;
  overflow-y: auto; /* 👈 habilita scroll si el contenido es largo */
}

/* Sidebar links */
.sidebar-link {
  padding: 12px 18px;
  border-radius: 8px;
  transition: background 0.2s ease, color 0.2s ease;
  border: none;
  background: transparent;
}

.sidebar-link:hover {
  background: #495057;
  color: #fff;
}

/* Submenu styling */
.collapse .nav-link {
  padding-left: 30px;
}

/* 👇 Alineación submenús igual que los padres cuando está colapsado */
.sidebar.collapsed .collapse .nav-link {
  padding-left: 0;
}

/* Icon rotate */
.rotate-180 {
  transform: rotate(180deg);
  transition: transform 0.3s;
}

/* Responsive: cuando se colapsa */
.sidebar.collapsed {
  width: 70px;
}

.sidebar.collapsed .link-text {
  display: none;
}

.sidebar.collapsed .bi-caret-down {
  display: none;
}

.sidebar.collapsed .nav-link {
  justify-content: center;
  padding: 12px 0;
}

.sidebar.collapsed .me-2 {
  margin-right: 0 !important;
}

/* 👇 ajuste: no ocultar los submenus abiertos */
.sidebar.collapsed .collapse:not(.show) {
  display: none;
}

/* Mobile: solo íconos */
@media (max-width: 991px) {
  .sidebar {
    width: 70px;
  }

  .link-text {
    display: none;
  }

  .bi-caret-down {
    display: none;
  }

  .nav-link {
    justify-content: center;
    padding: 12px 0;
  }
}

/* Active states */
.router-link-active {
  background: #007bff !important;
  color: #fff !important;
}

.router-link-active:hover {
  background: #0056b3 !important;
}
</style>
