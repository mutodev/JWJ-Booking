<template>
  <aside class="jwj-sidebar" :class="{ 'is-collapsed': isCollapsed }">
    <!-- Brand header -->
    <div class="jwj-sidebar__brand">
      <div class="jwj-sidebar__brand-icon">
        <i class="bi bi-music-note-beamed"></i>
      </div>
      <span class="jwj-sidebar__brand-name">JWJ Admin</span>
    </div>

    <!-- Navigation -->
    <nav class="jwj-sidebar__nav">
      <ul class="jwj-sidebar__list">
        <li v-for="item in access" :key="item.id" class="jwj-sidebar__item">
          <!-- Parent with children -->
          <template v-if="item.children && item.children.length > 0">
            <button
              class="jwj-sidebar__link jwj-sidebar__link--parent"
              :class="{ 'is-open': isOpen(item.id) }"
              @click="toggleCollapse(item.id)"
              :title="isCollapsed ? item.name : undefined"
              :data-bs-toggle="isCollapsed ? 'tooltip' : undefined"
              data-bs-placement="right"
            >
              <i :class="[item.icon, 'jwj-sidebar__link-icon']"></i>
              <span class="jwj-sidebar__link-label">{{ item.name }}</span>
              <i class="bi bi-chevron-right jwj-sidebar__chevron"></i>
            </button>
            <ul class="jwj-sidebar__submenu" :class="{ 'is-open': isOpen(item.id) }">
              <li v-for="child in item.children" :key="child.id">
                <router-link
                  :to="child.uri"
                  class="jwj-sidebar__link jwj-sidebar__link--child"
                  :title="isCollapsed ? child.name : undefined"
                  :data-bs-toggle="isCollapsed ? 'tooltip' : undefined"
                  data-bs-placement="right"
                >
                  <i :class="[child.icon, 'jwj-sidebar__link-icon']"></i>
                  <span class="jwj-sidebar__link-label">{{ child.name }}</span>
                </router-link>
              </li>
            </ul>
          </template>

          <!-- Simple link -->
          <router-link
            v-else
            :to="item.uri"
            class="jwj-sidebar__link"
            :title="isCollapsed ? item.name : undefined"
            :data-bs-toggle="isCollapsed ? 'tooltip' : undefined"
            data-bs-placement="right"
          >
            <i :class="[item.icon, 'jwj-sidebar__link-icon']"></i>
            <span class="jwj-sidebar__link-label">{{ item.name }}</span>
          </router-link>
        </li>
      </ul>
    </nav>

    <!-- Collapse toggle button -->
    <button class="jwj-sidebar__toggle" @click="manualToggle" :title="isCollapsed ? 'Expand' : 'Collapse'">
      <i :class="isCollapsed ? 'bi bi-layout-sidebar' : 'bi bi-layout-sidebar-inset'"></i>
      <span class="jwj-sidebar__link-label">Collapse</span>
    </button>
  </aside>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";

const access = ref(JSON.parse(localStorage.getItem("access") || "[]"));
const openMenus = ref({});
const isCollapsed = ref(false);
const manualOverride = ref(false);

const toggleCollapse = (id) => {
  openMenus.value[id] = !openMenus.value[id];
};

const isOpen = (id) => !!openMenus.value[id];

const manualToggle = () => {
  manualOverride.value = true;
  isCollapsed.value = !isCollapsed.value;
  updateTooltips();
};

const handleResize = () => {
  if (!manualOverride.value) {
    isCollapsed.value = window.innerWidth <= 991;
    updateTooltips();
  }
};

const updateTooltips = () => {
  setTimeout(() => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
      window.bootstrap?.Tooltip?.getInstance(el)?.dispose();
    });
    if (isCollapsed.value && window.bootstrap) {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        new window.bootstrap.Tooltip(el);
      });
    }
  }, 50);
};

onMounted(() => {
  handleResize();
  window.addEventListener("resize", handleResize);
});

onUnmounted(() => {
  window.removeEventListener("resize", handleResize);
});
</script>
