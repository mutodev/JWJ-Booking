<template>
  <aside class="jwj-sidebar" :class="{ 'is-collapsed': sidebarCollapsed }">
    <!-- Brand -->
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
              @click="toggleMenu(item.id, item.children)"
              :title="sidebarCollapsed ? item.name : undefined"
              :data-bs-toggle="sidebarCollapsed ? 'tooltip' : undefined"
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
                  :title="sidebarCollapsed ? child.name : undefined"
                  :data-bs-toggle="sidebarCollapsed ? 'tooltip' : undefined"
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
            :title="sidebarCollapsed ? item.name : undefined"
            :data-bs-toggle="sidebarCollapsed ? 'tooltip' : undefined"
            data-bs-placement="right"
          >
            <i :class="[item.icon, 'jwj-sidebar__link-icon']"></i>
            <span class="jwj-sidebar__link-label">{{ item.name }}</span>
          </router-link>

        </li>
      </ul>
    </nav>
  </aside>
</template>

<script setup>
import { ref, inject, onMounted, onUnmounted, watch } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();
const access = ref(JSON.parse(localStorage.getItem("access") || "[]"));
const openMenus = ref({});
const sidebarCollapsed = inject('sidebarCollapsed');
const manualOverride = ref(false);

const toggleMenu = (id, children) => {
  if (sidebarCollapsed.value && children?.length) {
    router.push(children[0].uri);
    return;
  }
  openMenus.value[id] = !openMenus.value[id];
};

const isOpen = (id) => !!openMenus.value[id];

const updateTooltips = () => {
  setTimeout(() => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
      window.bootstrap?.Tooltip?.getInstance(el)?.dispose();
    });
    if (sidebarCollapsed.value && window.bootstrap) {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        new window.bootstrap.Tooltip(el);
      });
    }
  }, 50);
};

const handleResize = () => {
  if (!manualOverride.value) {
    sidebarCollapsed.value = window.innerWidth <= 991;
    updateTooltips();
  }
};

watch(sidebarCollapsed, updateTooltips);

onMounted(() => {
  handleResize();
  window.addEventListener("resize", handleResize);
});

onUnmounted(() => {
  window.removeEventListener("resize", handleResize);
});
</script>
