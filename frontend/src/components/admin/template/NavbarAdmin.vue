<template>
  <header class="jwj-navbar">
    <!-- Left: page info -->
    <div class="jwj-navbar__left">
      <div class="jwj-navbar__page">
        <span class="jwj-navbar__page-icon">
          <i :class="headerData.icon"></i>
        </span>
        <span class="jwj-navbar__page-title">{{ headerData.title }}</span>
      </div>
    </div>

    <!-- Right: user -->
    <div class="jwj-navbar__right">
      <div class="jwj-navbar__user" ref="dropdownEl" @click="toggleDropdown">
        <div class="jwj-navbar__avatar">{{ initials }}</div>
        <div class="d-none d-md-flex flex-column jwj-navbar__user-info">
          <span class="jwj-navbar__user-name">{{ user.name }}</span>
          <span class="jwj-navbar__user-role">{{ user.role }}</span>
        </div>
        <i class="bi bi-chevron-down jwj-navbar__chevron" :class="{ rotated: dropdownOpen }"></i>

        <!-- Dropdown -->
        <div class="jwj-navbar__dropdown" :class="{ 'is-open': dropdownOpen }">
          <div class="jwj-navbar__dropdown-header">
            <strong>{{ user.name }}</strong>
            <small>{{ user.role }}</small>
          </div>
          <div class="jwj-navbar__dropdown-divider"></div>
          <router-link class="jwj-navbar__dropdown-item" to="/admin/profile" @click="closeDropdown">
            <i class="bi bi-person-circle"></i> Profile
          </router-link>
          <div class="jwj-navbar__dropdown-divider"></div>
          <button class="jwj-navbar__dropdown-item jwj-navbar__dropdown-item--danger" @click="closeSession">
            <i class="bi bi-box-arrow-right"></i> Sign out
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { inject, ref, computed, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();
const headerData = inject("headerData");

const user = ref({
  name: `${localStorage.getItem("first_name") || ""} ${localStorage.getItem("last_name") || ""}`.trim(),
  role: localStorage.getItem("role_name") || "",
});

const initials = computed(() => {
  const parts = user.value.name.split(" ").filter(Boolean);
  return parts.slice(0, 2).map((p) => p[0].toUpperCase()).join("");
});

const dropdownOpen = ref(false);
const dropdownEl = ref(null);

const toggleDropdown = () => { dropdownOpen.value = !dropdownOpen.value; };
const closeDropdown = () => { dropdownOpen.value = false; };

const closeSession = () => {
  localStorage.clear();
  router.replace("/login");
};

const handleClickOutside = (e) => {
  if (dropdownEl.value && !dropdownEl.value.contains(e.target)) closeDropdown();
};

onMounted(() => document.addEventListener("click", handleClickOutside));
onUnmounted(() => document.removeEventListener("click", handleClickOutside));
</script>
