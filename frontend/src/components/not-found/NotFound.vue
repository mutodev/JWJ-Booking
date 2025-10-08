<template>
  <div
    class="d-flex flex-column justify-content-center align-items-center vh-100 bg-light text-center"
  >
    <h1 class="display-1 fw-bold text-primary">404</h1>
    <p class="fs-4 text-muted">
      Oops! The page you are looking for doesn’t exist.
    </p>
    <button class="btn btn-primary mt-3 px-4" @click="goBack">
      <i class="bi bi-arrow-left-circle me-2"></i>
      Go Back
    </button>
  </div>
</template>

<script setup>
import { useRouter } from "vue-router";

const router = useRouter();

const goBack = () => {
  const token = localStorage.getItem("token");
  if (token) {
    router.push("/admin/dashboard");
  } else {
    router.push("/");
  }
};
</script>
